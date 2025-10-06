<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SagaTransaction extends Model
{
    protected $fillable = [
        'saga_id',
        'transaction_type',
        'status',
        'user_data',
        'account_data',
        'compensation_data',
        'started_at',
        'completed_at',
        'failed_at',
        'error_message',
        'retry_count',
        'next_step'
    ];

    protected $casts = [
        'user_data' => 'array',
        'account_data' => 'array',
        'compensation_data' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'failed_at' => 'datetime',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_USER_CREATED = 'user_created';
    const STATUS_ACCOUNT_CREATING = 'account_creating';
    const STATUS_ACCOUNT_CREATED = 'account_created';
    const STATUS_COMPLETED = 'completed';
    const STATUS_COMPENSATING = 'compensating';
    const STATUS_COMPENSATED = 'compensated';
    const STATUS_FAILED = 'failed';

    const TYPE_CREATE_USER = 'create_user';
    const TYPE_DELETE_USER = 'delete_user';
}