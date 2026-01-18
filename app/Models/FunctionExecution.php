<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FunctionExecution extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'message_id',
        'function_name',
        'arguments',
        'result',
        'execution_time_ms',
        'status',
        'error_message',
    ];

    protected $casts = [
        'arguments' => 'array',
        'result' => 'array',
        'created_at' => 'datetime',
    ];

    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }
}
