<?php

namespace App\Models;

use Database\Factories\CopyTradeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CopyTrade extends Model
{
    /** @use HasFactory<CopyTradeFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'master_connection_id',
        'follower_trx_id',
        'master_trx_id',
        'symbol',
        'contract_type',
        'duration',
        'barrier',
        'stake',
        'payout',
        'profit',
        'sell_at_market',
        'is_win',
        'traded_at',
    ];

    protected function casts(): array
    {
        return [
            'stake' => 'decimal:2',
            'payout' => 'decimal:2',
            'profit' => 'decimal:2',
            'sell_at_market' => 'boolean',
            'is_win' => 'boolean',
            'traded_at' => 'datetime',
        ];
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
