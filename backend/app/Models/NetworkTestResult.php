<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NetworkTestResult extends Model
{
    protected $fillable = [
        'latency_ms', 'jitter_ms', 'download_mbps', 'upload_mbps', 'connection_quality',
        'effective_type', 'reported_downlink_mbps', 'reported_rtt_ms', 'user_agent_family', 'tested_at',
    ];

    protected function casts(): array
    {
        return [
            'latency_ms' => 'float', 'jitter_ms' => 'float', 'download_mbps' => 'float', 'upload_mbps' => 'float',
            'reported_downlink_mbps' => 'float', 'reported_rtt_ms' => 'integer', 'tested_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
