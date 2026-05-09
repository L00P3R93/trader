<?php

namespace App\Console\Commands;

use App\Jobs\CopyTradeJob;
use App\Models\CopySetting;
use App\Models\DerivConnection;
use App\Services\DerivApiService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ListenMasterAccount extends Command
{
    protected $signature = 'deriv:listen-master {connection_id : The master DerivConnection ID}';

    protected $description = 'Listen to a master account WebSocket stream and fan out copy trades to followers';

    public function handle(): int
    {
        $connectionId = (int) $this->argument('connection_id');

        /** @var DerivConnection|null $connection */
        $connection = DerivConnection::find($connectionId);

        if (! $connection) {
            $this->error("No connection found with ID {$connectionId}.");

            return self::FAILURE;
        }

        $this->info("Starting listener for connection #{$connectionId}...");

        while (true) {
            $connection = $connection->fresh() ?? $connection;

            try {
                $this->runSession($connection, $connectionId);
            } catch (\Throwable $e) {
                Log::error("Master listener error on connection #{$connectionId}: {$e->getMessage()}");
                $this->warn("Connection error: {$e->getMessage()}. Reconnecting in 5s...");
            }

            sleep(5);
        }

        return self::SUCCESS; // @phpstan-ignore-line
    }

    private function runSession(DerivConnection $connection, int $connectionId): void
    {
        $masterAccountId = $this->resolveMasterAccountId($connection, $connectionId);

        $this->info("Resolved master account: {$masterAccountId}");

        $wsUrl = app(DerivApiService::class)->getOtpUrl($connection, $masterAccountId);

        $host = (string) parse_url($wsUrl, PHP_URL_HOST);

        $context = stream_context_create([
            'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
        ]);

        $socket = @stream_socket_client(
            "ssl://{$host}:443",
            $errno,
            $errstr,
            30,
            STREAM_CLIENT_CONNECT,
            $context,
        );

        if (! $socket) {
            throw new \RuntimeException("WebSocket connection failed: {$errstr}");
        }

        // 45-second read timeout — allows keepalive ping when the stream is quiet
        stream_set_timeout($socket, 45);

        $this->sendWsHandshake($socket, $wsUrl);

        // OTP session is pre-authenticated — no authorize message needed
        $this->sendWsMessage($socket, ['transaction' => 1, 'subscribe' => 1, 'req_id' => 2]);
        $this->info("Subscribed to transaction stream for {$masterAccountId}. Waiting for trades...");

        // Tracks buy transactions waiting for their proposal_open_contract response
        // keyed by req_id => transaction_data
        $pending = [];
        $nextReqId = 50;

        while (true) {
            $message = $this->receiveWsMessage($socket);

            if ($message === null) {
                $meta = stream_get_meta_data($socket);

                if ($meta['timed_out'] ?? false) {
                    $this->sendWsMessage($socket, ['ping' => 1, 'req_id' => 99]);

                    continue;
                }

                fclose($socket);
                throw new \RuntimeException('Connection closed by server');
            }

            $this->handleMessage($socket, $message, $connectionId, $pending, $nextReqId);
        }
    }

    /**
     * Determine which Deriv account ID to listen on.
     * Prefers the master_account_id stored in a CopySetting for this connection;
     * falls back to the first account returned by the REST API.
     */
    private function resolveMasterAccountId(DerivConnection $connection, int $connectionId): string
    {
        $stored = CopySetting::query()
            ->where('master_connection_id', $connectionId)
            ->whereNotNull('master_account_id')
            ->value('master_account_id');

        if ($stored) {
            return $stored;
        }

        $accounts = app(DerivApiService::class)->getAccounts($connection);

        if (empty($accounts)) {
            throw new \RuntimeException('No Deriv accounts found for this connection.');
        }

        $first = $accounts[0]['account_id'] ?? null;

        if (! $first) {
            throw new \RuntimeException('Could not determine account ID from Deriv API response.');
        }

        $this->warn("No master_account_id found in copy settings — defaulting to first account: {$first}");

        return $first;
    }

    private function handleMessage($socket, array $message, int $connectionId, array &$pending, int &$nextReqId): void
    {
        $msgType = $message['msg_type'] ?? '';

        if ($msgType === 'transaction') {
            $transaction = $message['transaction'] ?? [];
            $action = $transaction['action'] ?? '';

            if ($action !== 'buy') {
                return;
            }

            $contractId = $transaction['contract_id'] ?? null;
            $symbol = $transaction['symbol'] ?? $transaction['underlying'] ?? 'unknown';
            $contractType = $transaction['contract_type'] ?? '?';

            $this->info("Master trade detected: {$symbol} {$contractType} (contract #{$contractId})");
            Log::info("Master trade detected on connection #{$connectionId}", $transaction);

            if ($contractId) {
                // Fetch full contract details (duration, etc.) before dispatching
                $reqId = $nextReqId++;
                $pending[$reqId] = $transaction;

                $this->sendWsMessage($socket, [
                    'proposal_open_contract' => 1,
                    'contract_id' => $contractId,
                    'req_id' => $reqId,
                ]);
            } else {
                // No contract_id — dispatch with defaults
                CopyTradeJob::dispatch($connectionId, $transaction);
            }

            return;
        }

        if ($msgType === 'proposal_open_contract') {
            $reqId = $message['req_id'] ?? 0;

            if (! isset($pending[$reqId])) {
                return;
            }

            $baseTrade = $pending[$reqId];
            unset($pending[$reqId]);

            $contract = $message['proposal_open_contract'] ?? [];

            // Enrich the original transaction data with duration from the contract
            Log::debug('proposal_open_contract response', array_intersect_key($contract, array_flip([
                'contract_type', 'underlying', 'duration', 'duration_unit', 'barrier', 'last_digit',
            ])));

            $enriched = array_merge($baseTrade, [
                'duration' => $contract['duration'] ?? 1,
                'duration_unit' => $contract['duration_unit'] ?? 't',
                'contract_type' => $contract['contract_type'] ?? ($baseTrade['contract_type'] ?? 'CALL'),
                'symbol' => $contract['underlying'] ?? $baseTrade['symbol'] ?? $baseTrade['underlying'] ?? 'R_50',
                'barrier' => $contract['barrier'] ?? $contract['last_digit'] ?? $baseTrade['barrier'] ?? null,
            ]);

            $this->info('Contract details fetched — dispatching copy trade job.');
            Log::info("Dispatching CopyTradeJob for connection #{$connectionId}", $enriched);

            CopyTradeJob::dispatch($connectionId, $enriched);
        }
    }

    private function sendWsHandshake($socket, string $wsUrl): void
    {
        $host = parse_url($wsUrl, PHP_URL_HOST);
        $path = parse_url($wsUrl, PHP_URL_PATH).'?'.parse_url($wsUrl, PHP_URL_QUERY);
        $key = base64_encode(random_bytes(16));

        $handshake = "GET {$path} HTTP/1.1\r\n"
            ."Host: {$host}\r\n"
            ."Upgrade: websocket\r\n"
            ."Connection: Upgrade\r\n"
            ."Sec-WebSocket-Key: {$key}\r\n"
            ."Sec-WebSocket-Version: 13\r\n\r\n";

        fwrite($socket, $handshake);
        stream_get_line($socket, 1024, "\r\n\r\n");
    }

    private function sendWsMessage($socket, array $data): void
    {
        $json = json_encode($data);
        $length = strlen($json);
        $frame = chr(0x81);

        if ($length <= 125) {
            $frame .= chr(0x80 | $length);
        } elseif ($length <= 65535) {
            $frame .= chr(0xFE).pack('n', $length);
        } else {
            $frame .= chr(0xFF).pack('J', $length);
        }

        $mask = random_bytes(4);
        $frame .= $mask;

        for ($i = 0; $i < $length; $i++) {
            $frame .= $json[$i] ^ $mask[$i % 4];
        }

        fwrite($socket, $frame);
    }

    private function receiveWsMessage($socket): ?array
    {
        $header = fread($socket, 2);

        if (strlen($header) < 2) {
            return null;
        }

        $isMasked = (ord($header[1]) & 0x80) !== 0;
        $payloadLength = ord($header[1]) & 0x7F;

        if ($payloadLength === 126) {
            $payloadLength = unpack('n', fread($socket, 2))[1];
        } elseif ($payloadLength === 127) {
            $payloadLength = unpack('J', fread($socket, 8))[1];
        }

        $mask = $isMasked ? fread($socket, 4) : null;
        $payload = fread($socket, $payloadLength);

        if ($mask) {
            for ($i = 0; $i < $payloadLength; $i++) {
                $payload[$i] = $payload[$i] ^ $mask[$i % 4];
            }
        }

        return json_decode($payload, true);
    }
}
