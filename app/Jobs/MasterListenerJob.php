<?php

namespace App\Jobs;

use App\Models\CopySetting;
use App\Models\DerivConnection;
use App\Services\DerivApiService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

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

    /** Cache key for deduplication — prevents multiple instances for the same connection. */
    public static function runningKey(int $connectionId): string
    {
        return "deriv:listener:running:{$connectionId}";
    }

    public function handle(): void
    {
        // Atomic mutex: exit immediately if another instance is already running for this connection.
        // TTL 60 s; refreshed every ~30 s via updateHeartbeat() so it never expires while healthy.
        // If the process is killed without reaching the finally block, the key naturally expires
        // and the next startBot() dispatch or EnsureListenersRunning tick can proceed.
        if (! Cache::add(self::runningKey($this->connectionId), true, 60)) {
            Log::info("MasterListenerJob: duplicate instance for connection #{$this->connectionId} — exiting.");

            return;
        }

        try {
            $this->runListener();
        } finally {
            Cache::forget(self::runningKey($this->connectionId));
        }
    }

    private function runListener(): void
    {
        $connection = DerivConnection::find($this->connectionId);

        if (! $connection) {
            Log::error("MasterListenerJob: connection #{$this->connectionId} not found.");

            return;
        }

        $masterAccountId = $this->resolveMasterAccountId($connection);

        Log::info("MasterListenerJob starting for connection #{$this->connectionId}, account {$masterAccountId}");

        $stopping = false;

        if (extension_loaded('pcntl')) {
            pcntl_async_signals(true);
            pcntl_signal(SIGTERM, function () use (&$stopping) {
                $stopping = true;
            });
            pcntl_signal(SIGINT, function () use (&$stopping) {
                $stopping = true;
            });
        }

        // Self-healing loop: reconnects automatically if the WebSocket drops or
        // Deriv closes the session. Exits only when there are no active followers.
        while (! $stopping) {
            if (! $this->hasActiveFollowers()) {
                Log::info("MasterListenerJob: no active followers for connection #{$this->connectionId} — exiting.");

                return;
            }

            $connection = $connection->fresh() ?? $connection;
            $this->updateHeartbeat();

            try {
                $wsUrl = app(DerivApiService::class)->getOtpUrl($connection, $masterAccountId);
                Log::info("MasterListenerJob: opening session for connection #{$this->connectionId}");
                $this->runSession($wsUrl, $stopping);
            } catch (\Throwable $e) {
                Log::warning("MasterListenerJob: session ended for connection #{$this->connectionId} — {$e->getMessage()}. Reconnecting in 5s...");
                sleep(5);
            }
        }
    }

    // ─── WebSocket session ─────────────────────────────────────────────────────

    private function runSession(string $wsUrl, bool &$stopping): void
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

        // 30-second read timeout — lets us check stop condition periodically when idle
        stream_set_timeout($socket, 30);

        $this->sendWsHandshake($socket, $wsUrl);

        // OTP session is pre-authenticated; no authorize message required
        $this->sendWsMessage($socket, ['transaction' => 1, 'subscribe' => 1, 'req_id' => 2]);

        Log::info("MasterListenerJob: subscribed to transaction stream for connection #{$this->connectionId}");

        // Pending map: req_id => transaction_data (awaiting proposal_open_contract)
        $pending = [];
        $nextReqId = 50;
        $lastHeartbeat = 0;

        while (true) {
            $message = $this->receiveWsMessage($socket);

            if ($message === null) {
                $meta = stream_get_meta_data($socket);

                if ($meta['timed_out'] ?? false) {
                    $this->updateHeartbeat();
                    $lastHeartbeat = time();

                    if ($stopping || ! $this->hasActiveFollowers()) {
                        Log::info('MasterListenerJob: shutting down cleanly.');
                        fclose($socket);

                        return;
                    }

                    $this->sendWsMessage($socket, ['ping' => 1, 'req_id' => 99]);

                    continue;
                }

                fclose($socket);
                throw new \RuntimeException('Connection closed by server');
            }

            // Refresh heartbeat during active trading so it doesn't expire
            // when the socket never idles long enough to trigger the timeout branch.
            if (time() - $lastHeartbeat >= 30) {
                $this->updateHeartbeat();
                $lastHeartbeat = time();
            }

            $this->handleMessage($socket, $message, $pending, $nextReqId);
        }
    }

    private function handleMessage($socket, array $message, array &$pending, int &$nextReqId): void
    {
        $msgType = $message['msg_type'] ?? '';

        if ($msgType === 'error') {
            $error = $message['error'] ?? [];
            Log::error("MasterListenerJob: Deriv API error for connection #{$this->connectionId}", [
                'code' => $error['code'] ?? 'unknown',
                'message' => $error['message'] ?? 'unknown',
            ]);
            throw new \RuntimeException('Deriv API error: '.($error['message'] ?? 'unknown'));
        }

        if ($msgType === 'transaction') {
            $transaction = $message['transaction'] ?? [];
            $action = $transaction['action'] ?? '';

            if ($action === 'sell') {
                $amount = (float) ($transaction['amount'] ?? 0);
                $isWin = $amount > 0 ? 1 : 0;
                $key = "master_outcomes:{$this->connectionId}";
                Redis::lpush($key, $isWin);
                Redis::ltrim($key, 0, 49);
                Redis::incr("master_outcomes_count:{$this->connectionId}");

                // Reset per-user pattern-consumed locks so the next buy can trigger a fresh trade
                CopyTradeJob::clearAllPatternConsumed($this->connectionId);

                Log::debug("MasterListenerJob: sell recorded for connection #{$this->connectionId}", ['is_win' => $isWin]);

                return;
            }

            if ($action !== 'buy') {
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

            Log::debug('MasterListenerJob: proposal_open_contract', array_intersect_key($contract, array_flip([
                'contract_type', 'underlying', 'duration', 'duration_unit', 'barrier', 'last_digit',
            ])));

            $enriched = array_merge($baseTrade, [
                'duration' => $contract['duration'] ?? 1,
                'duration_unit' => $contract['duration_unit'] ?? 't',
                'contract_type' => $contract['contract_type'] ?? ($baseTrade['contract_type'] ?? 'CALL'),
                'symbol' => $contract['underlying'] ?? $baseTrade['symbol'] ?? $baseTrade['underlying'] ?? 'R_50',
                'barrier' => $contract['barrier'] ?? $contract['last_digit'] ?? $baseTrade['barrier'] ?? null,
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
        Cache::put(self::heartbeatKey($this->connectionId), now()->toIso8601String(), 90);
        Cache::put(self::runningKey($this->connectionId), true, 60);
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
        $header = $this->readExactly($socket, 2);

        if (strlen($header) < 2) {
            return null;
        }

        $isMasked = (ord($header[1]) & 0x80) !== 0;
        $payloadLength = ord($header[1]) & 0x7F;

        if ($payloadLength === 126) {
            $payloadLength = (int) unpack('n', $this->readExactly($socket, 2))[1];
        } elseif ($payloadLength === 127) {
            $payloadLength = (int) unpack('J', $this->readExactly($socket, 8))[1];
        }

        $mask = $isMasked ? $this->readExactly($socket, 4) : null;
        $payload = $this->readExactly($socket, $payloadLength);

        if ($mask) {
            for ($i = 0; $i < $payloadLength; $i++) {
                $payload[$i] = $payload[$i] ^ $mask[$i % 4];
            }
        }

        return json_decode($payload, true);
    }

    private function readExactly($socket, int $length): string
    {
        $data = '';
        $remaining = $length;

        while ($remaining > 0) {
            $chunk = fread($socket, $remaining);

            if ($chunk === false || $chunk === '') {
                break;
            }

            $data .= $chunk;
            $remaining -= strlen($chunk);
        }

        return $data;
    }
}
