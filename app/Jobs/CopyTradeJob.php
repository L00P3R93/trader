<?php

namespace App\Jobs;

use App\Models\CopySetting;
use App\Models\CopyTrade;
use App\Models\DerivConnection;
use App\Services\DerivApiService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class CopyTradeJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 30;

    public function __construct(
        public readonly int $masterConnectionId,
        public readonly array $masterTrade,
    ) {}

    public function handle(DerivApiService $deriv): void
    {
        $masterConnection = DerivConnection::find($this->masterConnectionId);

        if (! $masterConnection) {
            return;
        }

        $followers = CopySetting::query()
            ->where('master_connection_id', $this->masterConnectionId)
            ->where('is_active', true)
            ->where('is_running', true)
            ->with('user.derivConnection')
            ->get();

        foreach ($followers as $setting) {
            $followerConnection = $setting->user?->derivConnection;

            if (! $followerConnection || $followerConnection->isExpired()) {
                continue;
            }

            $recentOutcomes = $this->getRecentOutcomes($this->masterConnectionId);

            if (! $setting->matchesPattern($recentOutcomes)) {
                Log::debug("Pattern not matched for user {$setting->user_id}, skipping.", ['master_outcomes' => $recentOutcomes, 'pattern' => $setting->follower_pattern]);

                continue;
            }

            if ($this->shouldFilterTrade($setting)) {
                Log::debug("Trade filtered by market/symbol settings for user {$setting->user_id}.");

                continue;
            }

            $stake = $setting->follow_master_stake
                ? ($this->masterTrade['buy_price'] ?? $setting->stake)
                : $setting->stake;

            try {
                $symbol = $this->masterTrade['symbol'] ?? $this->masterTrade['underlying'] ?? 'R_50';

                $result = $deriv->buyContract($followerConnection, [
                    'contract_type' => $this->masterTrade['contract_type'] ?? 'CALL',
                    'symbol' => $symbol,
                    'duration' => $this->masterTrade['duration'] ?? 1,
                    'duration_unit' => $this->masterTrade['duration_unit'] ?? 't',
                    'stake' => $stake,
                    'basis' => 'stake',
                    'barrier' => $this->masterTrade['barrier'] ?? null,
                    'follower_account_id' => $setting->follower_account_id,
                ]);

                $followerContractId = (string) ($result['buy']['contract_id'] ?? '');

                $copyTrade = CopyTrade::create([
                    'user_id' => $setting->user_id,
                    'master_connection_id' => $this->masterConnectionId,
                    'follower_trx_id' => $result['buy']['transaction_id'] ?? null,
                    'follower_contract_id' => $followerContractId ?: null,
                    'master_trx_id' => $this->masterTrade['transaction_id'] ?? null,
                    'symbol' => $symbol,
                    'contract_type' => $this->masterTrade['contract_type'] ?? null,
                    'duration' => ($this->masterTrade['duration'] ?? '').($this->masterTrade['duration_unit'] ?? ''),
                    'barrier' => $this->masterTrade['barrier'] ?? null,
                    'stake' => $stake,
                    'traded_at' => now(),
                ]);

                if ($followerContractId) {
                    SettleCopyTradeJob::dispatch($copyTrade->id, $followerConnection->id, $followerContractId)
                        ->delay(now()->addSeconds(5));
                }
            } catch (\Throwable $e) {
                Log::error("CopyTradeJob failed for user {$setting->user_id}: {$e->getMessage()}");
            }
        }
    }

    private function getRecentOutcomes(int $masterConnectionId): array
    {
        // Read master's actual trade outcomes from Redis (recorded by ListenMasterAccount on 'sell' events).
        // LPUSH stores newest-first, so reverse to get chronological order for pattern matching.
        $raw = Redis::lrange("master_outcomes:{$masterConnectionId}", 0, 19);

        return array_reverse(array_map('intval', $raw));
    }

    private function shouldFilterTrade(CopySetting $setting): bool
    {
        $symbol = $this->masterTrade['underlying'] ?? '';

        if (! empty($setting->filter_markets) && ! in_array($symbol, $setting->filter_markets)) {
            return true;
        }

        return false;
    }
}
