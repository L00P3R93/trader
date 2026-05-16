<?php

namespace App\Jobs;

use App\Models\CopySetting;
use App\Models\CopyTrade;
use App\Models\DerivConnection;
use App\Services\DerivApiService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Cache;
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

        $recentOutcomes = $this->getRecentOutcomes($this->masterConnectionId);

        foreach ($followers as $setting) {
            $followerConnection = $setting->user?->derivConnection;

            if (! $followerConnection || $followerConnection->isExpired()) {
                continue;
            }

            // One trade per pattern detection — cleared when master next sells
            if ($setting->pattern_enabled && ! empty($setting->follower_pattern)) {
                if ($this->isPatternConsumed($setting->user_id)) {
                    Log::debug("CopyTradeJob: pattern already consumed for user {$setting->user_id}, waiting for master sell.");

                    continue;
                }
            }

            if (! $setting->matchesPattern($recentOutcomes)) {
                Log::debug("CopyTradeJob: pattern not matched for user {$setting->user_id}.", [
                    'master_outcomes' => $recentOutcomes,
                    'pattern' => $setting->follower_pattern,
                ]);

                continue;
            }

            if ($this->shouldFilterTrade($setting)) {
                Log::debug("CopyTradeJob: trade filtered by market settings for user {$setting->user_id}.");

                continue;
            }

            $sessionTrades = $this->getSettledSessionTrades($setting);

            if ($this->isSessionLimitReached($setting, $sessionTrades)) {
                continue;
            }

            if ($this->shouldWaitForLoss($setting, $recentOutcomes)) {
                Log::debug("CopyTradeJob: wait_for_loss condition not met for user {$setting->user_id}.");

                continue;
            }

            $stake = $this->calculateStake($setting, $sessionTrades);

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

                // Lock pattern — next buy for this user is blocked until master sells
                if ($setting->pattern_enabled && ! empty($setting->follower_pattern)) {
                    $this->markPatternConsumed($setting->user_id);
                }

                // Record that wait_for_loss trigger was used (for only_use_1x mode)
                if ($setting->wait_for_loss > 0 && $setting->only_use_1x_wait_for_loss) {
                    Cache::put($this->waitTriggerUsedKey($setting->user_id), true, now()->addHours(24));
                }

                if ($followerContractId) {
                    SettleCopyTradeJob::dispatch($copyTrade->id, $followerConnection->id, $followerContractId)
                        ->delay(now()->addSeconds(5));
                }

                Log::info("CopyTradeJob: trade copied for user {$setting->user_id} — {$symbol} stake={$stake}");
            } catch (\Throwable $e) {
                Log::error("CopyTradeJob failed for user {$setting->user_id}: {$e->getMessage()}");
            }
        }
    }

    // ─── Pattern-consumed lock ─────────────────────────────────────────────────

    private function isPatternConsumed(int $userId): bool
    {
        return Cache::has($this->patternConsumedKey($userId));
    }

    private function markPatternConsumed(int $userId): void
    {
        Cache::put($this->patternConsumedKey($userId), true, now()->addMinutes(10));
        // Record offset so the ticker resets and shows only outcomes after this trade
        $len = Redis::llen("master_outcomes:{$this->masterConnectionId}");
        Redis::setex("master_outcomes_offset:{$this->masterConnectionId}:{$userId}", 600, $len);
    }

    public static function clearAllPatternConsumed(int $masterConnectionId): void
    {
        CopySetting::query()
            ->where('master_connection_id', $masterConnectionId)
            ->where('is_running', true)
            ->pluck('user_id')
            ->each(fn ($uid) => Cache::forget("copy:pattern_consumed:{$masterConnectionId}:{$uid}"));
    }

    private function patternConsumedKey(int $userId): string
    {
        return "copy:pattern_consumed:{$this->masterConnectionId}:{$userId}";
    }

    // ─── Wait-trigger tracking ─────────────────────────────────────────────────

    public static function waitTriggerUsedKeyFor(int $masterConnectionId, int $userId): string
    {
        return "copy:wait_trigger_used:{$masterConnectionId}:{$userId}";
    }

    private function waitTriggerUsedKey(int $userId): string
    {
        return self::waitTriggerUsedKeyFor($this->masterConnectionId, $userId);
    }

    // ─── Session trades ────────────────────────────────────────────────────────

    /** Settled trades only — needed for martingale/compound/limit checks. */
    private function getSettledSessionTrades(CopySetting $setting): Collection
    {
        $query = CopyTrade::query()
            ->where('user_id', $setting->user_id)
            ->whereNotNull('is_win');

        if ($setting->session_started_at) {
            $query->where('traded_at', '>=', $setting->session_started_at);
        }

        return $query->orderBy('traded_at')->get();
    }

    // ─── Session limits (take_profit / stop_loss / max_martingale stop) ────────

    private function isSessionLimitReached(CopySetting $setting, Collection $sessionTrades): bool
    {
        $totalProfit = (float) $sessionTrades->sum('profit');

        if ($setting->take_profit !== null && $totalProfit >= (float) $setting->take_profit) {
            Log::info("CopyTradeJob: take profit reached for user {$setting->user_id} (profit={$totalProfit})");
            $setting->update([
                'is_running' => false,
                'stop_reason' => 'take_profit',
                'stopped_at_profit' => $totalProfit,
            ]);

            return true;
        }

        if ($setting->stop_loss !== null && $totalProfit <= -(float) $setting->stop_loss) {
            Log::info("CopyTradeJob: stop loss reached for user {$setting->user_id} (profit={$totalProfit})");
            $setting->update([
                'is_running' => false,
                'stop_reason' => 'stop_loss',
                'stopped_at_profit' => $totalProfit,
            ]);

            return true;
        }

        // Max martingale with stop action
        if (
            $setting->max_martingale > 0
            && $setting->if_hit_max_martingale === 'stop'
            && ! $setting->safe_mode
        ) {
            $consecutiveLosses = $this->getConsecutiveLosses($sessionTrades);

            if ($consecutiveLosses >= (int) $setting->do_martingale_at + (int) $setting->max_martingale) {
                Log::info("CopyTradeJob: max martingale stop triggered for user {$setting->user_id}");
                $setting->update([
                    'is_running' => false,
                    'stop_reason' => 'max_martingale',
                    'stopped_at_profit' => $totalProfit,
                ]);

                return true;
            }
        }

        return false;
    }

    // ─── Stake calculation ─────────────────────────────────────────────────────

    private function calculateStake(CopySetting $setting, Collection $sessionTrades): float
    {
        if ($setting->follow_master_stake) {
            return max(0.35, (float) ($this->masterTrade['buy_price'] ?? $setting->stake));
        }

        $baseStake = (float) $setting->stake;

        if ($setting->safe_mode) {
            return $baseStake;
        }

        $multiplier = max(1.0, (float) $setting->stake_multiplier);

        // Martingale: multiply stake after do_martingale_at consecutive losses
        if ($setting->max_martingale > 0 && $multiplier > 1.0) {
            $consecutiveLosses = $this->getConsecutiveLosses($sessionTrades);
            $threshold = (int) $setting->do_martingale_at;

            if ($consecutiveLosses >= $threshold) {
                $level = min($consecutiveLosses - $threshold + 1, (int) $setting->max_martingale);

                return round($baseStake * ($multiplier ** $level), 2);
            }
        }

        // Compound: multiply stake after consecutive wins (up to max_compound)
        if ($setting->max_compound > 0 && $multiplier > 1.0) {
            $consecutiveWins = $this->getConsecutiveWins($sessionTrades);

            if ($consecutiveWins > 0) {
                $level = min($consecutiveWins, (int) $setting->max_compound);

                return round($baseStake * ($multiplier ** $level), 2);
            }
        }

        return $baseStake;
    }

    // ─── Wait for loss ─────────────────────────────────────────────────────────

    private function shouldWaitForLoss(CopySetting $setting, array $recentOutcomes): bool
    {
        if ($setting->wait_for_loss <= 0) {
            return false;
        }

        // If only_use_1x mode and the trigger already fired this session, skip the wait
        if ($setting->only_use_1x_wait_for_loss && Cache::has($this->waitTriggerUsedKey($setting->user_id))) {
            return false;
        }

        // Count trailing master losses (0s) in chronological outcomes
        $trailingLosses = 0;
        foreach (array_reverse($recentOutcomes) as $outcome) {
            if ($outcome === 0) {
                $trailingLosses++;
            } else {
                break;
            }
        }

        return $trailingLosses < (int) $setting->wait_for_loss;
    }

    // ─── Market filter ─────────────────────────────────────────────────────────

    private function shouldFilterTrade(CopySetting $setting): bool
    {
        $symbol = $this->masterTrade['underlying'] ?? $this->masterTrade['symbol'] ?? '';

        $allowed = array_merge(
            $setting->filter_markets ?? [],
            $setting->synthetic_indices ?? [],
            $setting->forex_pairs ?? [],
        );

        if (empty($allowed)) {
            return false;
        }

        return ! in_array($symbol, $allowed);
    }

    // ─── Consecutive outcome helpers ───────────────────────────────────────────

    private function getConsecutiveLosses(Collection $trades): int
    {
        $count = 0;
        foreach ($trades->sortByDesc('traded_at') as $trade) {
            if ($trade->is_win === false) {
                $count++;
            } else {
                break;
            }
        }

        return $count;
    }

    private function getConsecutiveWins(Collection $trades): int
    {
        $count = 0;
        foreach ($trades->sortByDesc('traded_at') as $trade) {
            if ($trade->is_win === true) {
                $count++;
            } else {
                break;
            }
        }

        return $count;
    }

    // ─── Redis outcomes ────────────────────────────────────────────────────────

    private function getRecentOutcomes(int $masterConnectionId): array
    {
        $raw = Redis::lrange("master_outcomes:{$masterConnectionId}", 0, 19);

        return array_reverse(array_map('intval', $raw));
    }
}
