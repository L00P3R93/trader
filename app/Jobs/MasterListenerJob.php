<?php

namespace App\Jobs;

use App\Models\CopySetting;
use App\Models\DerivConnection;
use App\Services\DerivApiService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class MasterListenerJob implements ShouldQueue
{
    use Queueable;

    /** Never time out — this job runs for as long as the session is active. */
    public int $timeout = 0;

    /** Single attempt; the scheduler restarts it if it dies. */
    public int $tries = 1;

    public function __construct(
        public readonly int $connectionId,
    ) {
        $this->onQueue('listener');
    }

    public function handle(): void
    {
        $connection = DerivConnection::find($this->connectionId);

        if (! $connection) {
            Log::error("MasterListenerJob: connection #{$this->connectionId} not found.");

            return;
        }

        if (! $this->hasActiveFollowers()) {
            Log::info("MasterListenerJob: no active followers for connection #{$this->connectionId} — exiting.");

            return;
        }

        Log::info("MasterListenerJob starting for connection #{$this->connectionId}");
        $this->updateHeartbeat();

        $masterAccountId = $this->resolveMasterAccountId($connection);

        Log::info("MasterListenerJob listening on account {$masterAccountId}");

        $wsUrl = app(DerivApiService::class)->getOtpUrl($connection, $masterAccountId);

        $this->runSession($wsUrl);
    }

    // ─── WebSocket session ─────────────────────────────────────────────────────

    private function runSession(string $wsUrl): void
    {
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

        // 30-second read timeout — lets us check heartbeat / stop condition periodically
        stream_set_timeout($socket, 30);

        $this->sendWsHandshake($socket, $wsUrl);

        // OTP session is pre-authenticated; no authorize message required
        $this->sendWsMessage($socket, ['transaction' => 1, 'subscribe' => 1, 'req_id' => 2]);

        Log::info("MasterListenerJob: subscribed to transaction stream for connection #{$this->connectionId}");

        // Pending map: req_id => transaction_data (awaiting proposal_open_contract)
        $pending = [];
        $nextReqId = 50;

        while (true) {
            $message = $this->receiveWsMessage($socket);

            if ($message === null) {
                $meta = stream_get_meta_data($socket);

                if ($meta['timed_out'] ?? false) {
                    $this->updateHeartbeat();

                    if (! $this->hasActiveFollowers()) {
                        Log::info('MasterListenerJob: no active followers — shutting down cleanly.');
                        fclose($socket);

                        return;
                    }

                    $this->sendWsMessage($socket, ['ping' => 1, 'req_id' => 99]);

                    continue;
                }

                fclose($socket);
                throw new \RuntimeException('Connection closed by server');
            }

            $this->handleMessage($socket, $message, $pending, $nextReqId);
        }
    }

    private function handleMessage($socket, array $message, array &$pending, int &$nextReqId): void
    {
        $msgType = $message['msg_type'] ?? '';

        if ($msgType === 'transaction') {
            $transaction = $message['transaction'] ?? [];

            if (($transaction['action'] ?? '') !== 'buy') {
                return;
            }

            $contractId = $transaction['contract_id'] ?? null;
            $symbol = $transaction['symbol'] ?? $transaction['underlying'] ?? '?';
            $contractType = $transaction['contract_type'] ?? '?';

            Log::info("MasterListenerJob: buy detected — {$symbol} {$contractType} (contract #{$contractId})");

            if ($contractId) {
                $reqId = $nextReqId++;
                $pending[$reqId] = $transaction;

                $this->sendWsMessage($socket, [
                    'proposal_open_contract' => 1,
                    'contract_id' => $contractId,
                    'req_id' => $reqId,
                ]);
            } else {
                CopyTradeJob::dispatch($this->connectionId, $transaction);
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

            $enriched = array_merge($baseTrade, [
                'duration' => $contract['duration'] ?? 1,
                'duration_unit' => $contract['duration_unit'] ?? 't',
                'contract_type' => $contract['contract_type'] ?? ($baseTrade['contract_type'] ?? 'CALL'),
                'symbol' => $contract['underlying'] ?? $baseTrade['symbol'] ?? $baseTrade['underlying'] ?? 'R_50',
            ]);

            Log::info("MasterListenerJob: dispatching CopyTradeJob for connection #{$this->connectionId}");

            CopyTradeJob::dispatch($this->connectionId, $enriched);
        }
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    private function hasActiveFollowers(): bool
    {
        return CopySetting::query()
            ->where('master_connection_id', $this->connectionId)
            ->where('is_running', true)
            ->exists();
    }

    /** Cache key other processes use to confirm this listener is alive. */
    public static function heartbeatKey(int $connectionId): string
    {
        return "deriv:listener:heartbeat:{$connectionId}";
    }

    private function updateHeartbeat(): void
    {
        // TTL 90 s. EnsureListenersRunning (every minute) restarts if this expires.
        Cache::put(self::heartbeatKey($this->connectionId), now()->toIso8601String(), 90);
    }

    private function resolveMasterAccountId(DerivConnection $connection): string
    {
        $stored = CopySetting::query()
            ->where('master_connection_id', $this->connectionId)
            ->whereNotNull('master_account_id')
            ->value('master_account_id');

        if ($stored) {
            return $stored;
        }

        $accounts = app(DerivApiService::class)->getAccounts($connection);
        $first = $accounts[0]['account_id'] ?? null;

        if (! $first) {
            throw new \RuntimeException('No Deriv accounts found for this connection.');
        }

        return $first;
    }

    // ─── Raw WebSocket framing ─────────────────────────────────────────────────

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
