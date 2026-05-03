<?php

namespace App\Models;

use Database\Factories\CopySettingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CopySetting extends Model
{
    /** @use HasFactory<CopySettingFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'master_connection_id',
        'follower_account_id',
        'follower_pattern',
        'pattern_enabled',
        'stake',
        'follow_master_stake',
        'safe_mode',
        'stake_multiplier',
        'take_profit',
        'stop_loss',
        'max_compound',
        'do_martingale_at',
        'max_martingale',
        'if_hit_max_martingale',
        'wait_for_loss',
        'only_use_1x_wait_for_loss',
        'filter_markets',
        'synthetic_indices',
        'forex_pairs',
        'is_active',
        'is_running',
        'start_balance',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'is_running' => 'boolean',
            'pattern_enabled' => 'boolean',
            'follow_master_stake' => 'boolean',
            'safe_mode' => 'boolean',
            'only_use_1x_wait_for_loss' => 'boolean',
            'stake' => 'decimal:2',
            'stake_multiplier' => 'decimal:2',
            'take_profit' => 'decimal:2',
            'stop_loss' => 'decimal:2',
            'start_balance' => 'decimal:2',
            'filter_markets' => 'array',
            'synthetic_indices' => 'array',
            'forex_pairs' => 'array',
        ];
    }

    /** Check if the given outcome history matches the follower pattern. */
    public function matchesPattern(array $recentOutcomes): bool
    {
        if (! $this->pattern_enabled || empty($this->follower_pattern)) {
            return true;
        }

        $pattern = $this->follower_pattern;
        $length = strlen($pattern);

        if (count($recentOutcomes) < $length) {
            return false;
        }

        $slice = array_slice($recentOutcomes, -$length);

        foreach ($slice as $index => $outcome) {
            if ((string) $outcome !== $pattern[$index]) {
                return false;
            }
        }

        return true;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function masterConnection(): BelongsTo
    {
        return $this->belongsTo(DerivConnection::class, 'master_connection_id');
    }
}
