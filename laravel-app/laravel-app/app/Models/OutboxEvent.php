<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OutboxEvent extends Model
{
    protected $fillable = [
        'saga_id',
        'event_type',
        'payload',
        'status',
        'target_service',
        'retry_count',
        'max_retries',
        'scheduled_at',
        'processed_at',
        'failed_at',
        'error_message'
    ];

    protected $casts = [
        'payload' => 'array',
        'scheduled_at' => 'datetime',
        'processed_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_SENT = 'sent';
    const STATUS_FAILED = 'failed';
    const STATUS_RETRY = 'retry';

    const EVENT_CREATE_ACCOUNT = 'create_account';
    const EVENT_DELETE_ACCOUNT = 'delete_account';
    const EVENT_COMPENSATE_USER = 'compensate_user';
    const EVENT_ACCOUNT_CREATED = 'account_created';
    const EVENT_ACCOUNT_CREATION_FAILED = 'account_creation_failed';
}