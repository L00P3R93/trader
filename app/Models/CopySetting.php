<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CopySetting extends Model
{
    protected $fillable = [
        'user_id',
        'master_connection_id',
        'min_consecutive_wins',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'min_consecutive_wins' => 'integer',
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
