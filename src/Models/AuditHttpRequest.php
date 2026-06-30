<?php

declare(strict_types=1);

namespace LaraArabDev\Recordkeeper\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AuditHttpRequest extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'audit_id',
        'method',
        'url',
        'status_code',
        'duration_ms',
        'failed',
        'exception',
        'request_headers',
        'response_headers',
        'response_body',
        'created_at',
    ];

    protected $casts = [
        'failed' => 'boolean',
        'request_headers' => 'array',
        'response_headers' => 'array',
        'created_at' => 'datetime',
    ];

    public function audit(): BelongsTo
    {
        return $this->belongsTo(Audit::class);
    }
}
