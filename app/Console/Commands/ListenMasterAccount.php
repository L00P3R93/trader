<?php

namespace App\Console\Commands;

use App\Jobs\CopyTradeJob;
use App\Models\DerivConnection;
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

        if (! $connection || ! $connection->isMaster()) {
            $this->error("No master connection found with ID {$connectionId}.");

            return self::FAILURE;
        }

        $this->info("Listening to master account (connection #{$connectionId})...");

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
        $appId = config('deriv.app_id');
        $wsUrl = "wss://ws.binaryws.com/websockets/v3?app_id={$appId}";
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

        $this->sendWsMessage($socket, ['authorize' => $connection->access_token, 'req_id' => 1]);
        $auth = $this->receiveWsMessage($socket);

        if (! $auth || isset($auth['error'])) {
            fclose($socket);
            throw new \RuntimeException($auth['error']['message'] ?? 'Authorization failed');
        }

        $this->sendWsMessage($socket, ['transaction' => 1, 'subscribe' => 1, 'req_id' => 2]);
        $this->info('Subscribed to transaction stream. Waiting for trades...');

        while (true) {
            $message = $this->receiveWsMessage($socket);

            if ($message === null) {
                $meta = stream_get_meta_data($socket);

                if ($meta['timed_out'] ?? false) {
                    // No data for 45 s — send keepalive ping and keep looping
                    $this->sendWsMessage($socket, ['ping' => 1, 'req_id' => 99]);

                    continue;
                }

                fclose($socket);
                throw new \RuntimeException('Connection closed by server');
            }

            $this->handleMessage($message, $connectionId);
        }
    }

    private function handleMessage(array $message, int $connectionId): void
    {
        if (($message['msg_type'] ?? '') !== 'transaction') {
            return;
        }

        $transaction = $message['transaction'] ?? [];
        $action = $transaction['action'] ?? '';

        if ($action !== 'buy') {
            return;
        }

        $this->info("Master trade detected: {$transaction['symbol']} {$transaction['contract_type']}");
        Log::info("Master trade detected on connection #{$connectionId}", $transaction);

        CopyTradeJob::dispatch($connectionId, $transaction);
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
