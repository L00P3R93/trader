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
