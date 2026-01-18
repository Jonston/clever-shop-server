<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Message extends Model
{
    protected $fillable = [
        'conversation_id',
        'role',
        'content',
        'metadata',
        'parent_message_id',
        'tokens_used',
        'processing_time_ms',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    // Relationships
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Message::class, 'parent_message_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Message::class, 'parent_message_id');
    }

    public function functionExecutions(): HasMany
    {
        return $this->hasMany(FunctionExecution::class);
    }

    // Static helpers
    public static function createUserMessage(int $conversationId, string $content, ?array $metadata = null): self
    {
        return static::create([
            'conversation_id' => $conversationId,
            'role' => 'user',
            'content' => $content,
            'metadata' => $metadata,
        ]);
    }

    public static function createAssistantMessage(
        int $conversationId,
        string $content,
        ?int $tokensUsed = null,
        ?int $processingTime = null,
        ?array $metadata = null
    ): self {
        return static::create([
            'conversation_id' => $conversationId,
            'role' => 'assistant',
            'content' => $content,
            'tokens_used' => $tokensUsed,
            'processing_time_ms' => $processingTime,
            'metadata' => $metadata,
        ]);
    }
}
