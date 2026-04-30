<?php

namespace App\Services;

use App\Exceptions\DerivApiException;
use App\Models\DerivConnection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use WebSocket\Client;

class DerivApiService
{
    /** New REST API — reliable, no WebSocket needed. */
    private const REST_BASE = 'https://api.derivws.com/trading/v1/options';

    /** Legacy WebSocket API — for balance, trades, portfolio. */
    private const WS_URL = 'wss://ws.binaryws.com/websockets/v3';

    // ─── REST API ────────────────────────────────────────────────────────────

    /**
     * Get all accounts (demo + real) for this token.
     * Uses the new REST API — no WebSocket needed.
     */
    public function getAccounts(DerivConnection $connection): array
    {
        return $this->cachedCall($connection, 'accounts', 3600, function () use ($connection) {
            $response = Http::withHeaders($this->restHeaders($connection->access_token))
                ->get(self::REST_BASE.'/accounts');

            if ($response->status() === 401) {
                throw new DerivApiException('Token expired or invalid. Please reconnect your Deriv account.');
            }

            if ($response->failed()) {
                $msg = $response->json('errors.0.message', 'Failed to load accounts');
                throw new DerivApiException($msg);
            }

            return $response->json() ?? [];
        });
    }

    /**
     * Get all CFD (MT5) accounts for this token.
     * Returns normalized account data matching the Options account shape.
     */
    public function getCfdAccounts(DerivConnection $connection): array
    {
        return $this->cachedCall($connection, 'cfd_accounts', 3600, function () use ($connection) {
            $response = $this->wsCall($connection->access_token, [
                'trading_platform_accounts' => 1,
                'platform' => 'mt5',
            ]);

            return array_map(fn (array $a) => [
                'account_id' => $a['login'] ?? '—',
                'currency' => strtoupper($a['currency'] ?? 'USD'),
                'is_demo' => ($a['account_type'] ?? '') === 'demo',
                'balance' => isset($a['balance']) ? (float) $a['balance'] : null,
                'landing_company_name' => 'MT5 '.ucfirst($a['market_type'] ?? 'CFD'),
                'product_type' => 'cfd',
            ], $response['trading_platform_accounts'] ?? []);
        });
    }

    /**
     * Reset a demo account balance back to $10,000.
     * Only works for demo accounts.
     */
    public function resetDemoBalance(DerivConnection $connection, string $accountId): void
    {
        $response = Http::withHeaders($this->restHeaders($connection->access_token))
            ->post(self::REST_BASE."/accounts/{$accountId}/reset-demo-balance");

        if ($response->status() === 401) {
            throw new DerivApiException('Token expired or invalid. Please reconnect your Deriv account.');
        }

        if ($response->status() === 400) {
            $msg = $response->json('errors.0.message', 'Only demo accounts can be reset.');
            throw new DerivApiException($msg);
        }

        if ($response->failed()) {
            $msg = $response->json('errors.0.message', 'Failed to reset demo balance.');
            throw new DerivApiException($msg);
        }

        Cache::forget("deriv:conn:{$connection->id}:accounts");
    }

    // ─── WebSocket API ────────────────────────────────────────────────────────

    /** Get the current account balance. Cached 30 s. */
    public function getBalance(DerivConnection $connection): array
    {
        return $this->cachedCall($connection, 'balance', 30, fn () => $this->wsCall($connection->access_token, ['balance' => 1]));
    }

    /**
     * Get profit/loss table (closed positions). Cached 5 min.
     *
     * @param  int  $limit  Max 500 per page.
     * @param  int  $offset  Pagination offset.
     */
    public function getProfitTable(DerivConnection $connection, int $limit = 25, int $offset = 0): array
    {
        return $this->cachedCall($connection, "profit_table:{$limit}:{$offset}", 300, fn () => $this->wsCall($connection->access_token, [
            'profit_table' => 1,
            'limit' => $limit,
            'offset' => $offset,
            'sort' => 'DESC',
        ]));
    }

    /**
     * Get account statement (deposits, withdrawals, trades). Cached 5 min.
     */
    public function getStatement(DerivConnection $connection, int $limit = 25, int $offset = 0): array
    {
        return $this->cachedCall($connection, "statement:{$limit}:{$offset}", 300, fn () => $this->wsCall($connection->access_token, [
            'statement' => 1,
            'limit' => $limit,
            'offset' => $offset,
        ]));
    }

    /** Get open/active positions (portfolio). Cached 30 s. */
    public function getPortfolio(DerivConnection $connection): array
    {
        return $this->cachedCall($connection, 'portfolio', 30, fn () => $this->wsCall($connection->access_token, ['portfolio' => 1]));
    }

    /** Flush all cached data for this connection. */
    public function clearCache(DerivConnection $connection): void
    {
        $keys = ['accounts', 'cfd_accounts', 'balance', 'portfolio'];

        foreach ($keys as $key) {
            Cache::forget("deriv:conn:{$connection->id}:{$key}");
        }

        foreach ([25, 50, 100] as $limit) {
            foreach ([0, 25, 50, 75, 100] as $offset) {
                Cache::forget("deriv:conn:{$connection->id}:profit_table:{$limit}:{$offset}");
                Cache::forget("deriv:conn:{$connection->id}:statement:{$limit}:{$offset}");
            }
        }
    }

    // ─── Internals ────────────────────────────────────────────────────────────

    private function restHeaders(string $token): array
    {
        return [
            'Deriv-App-ID' => (string) config('deriv.app_id'),
            'Authorization' => 'Bearer '.$token,
            'Accept' => 'application/json',
        ];
    }

    private function cachedCall(DerivConnection $connection, string $key, int $ttl, callable $fn): array
    {
        return Cache::remember("deriv:conn:{$connection->id}:{$key}", $ttl, $fn);
    }

    private function wsCall(string $token, array $request): array
    {
        $url = self::WS_URL.'?app_id='.config('deriv.app_id');

        // Disable SSL peer verification — required on Windows where the
        // PHP CA bundle may not trust binaryws.com's certificate chain.
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
            ],
        ]);

        try {
            $client = new Client($url, [
                'timeout' => 15,
                'context' => $context,
            ]);

            // Authorize first
            $this->wsSend($client, ['authorize' => $token]);
            $auth = $this->wsReceive($client);

            if (isset($auth['error'])) {
                $client->close();
                throw new DerivApiException($auth['error']['message'] ?? 'Authorization failed');
            }

            // Make the actual request
            $this->wsSend($client, $request);
            $response = $this->wsReceive($client);
            $client->close();

            if (isset($response['error'])) {
                throw new DerivApiException($response['error']['message'] ?? 'API call failed');
            }

            return $response;
        } catch (DerivApiException $e) {
            throw $e;
        } catch (\Throwable $e) {
            throw new DerivApiException('Deriv WebSocket unavailable: '.$e->getMessage());
        }
    }

    private function wsSend(Client $client, array $data): void
    {
        $json = json_encode($data);

        if (method_exists($client, 'text')) {
            $client->text($json);
        } else {
            $client->send($json);
        }
    }

    private function wsReceive(Client $client): array
    {
        $raw = $client->receive();

        $content = match (true) {
            is_string($raw) => $raw,
            method_exists($raw, 'getContent') => $raw->getContent(),
            default => (string) $raw,
        };

        return json_decode($content, true) ?? [];
    }
}
