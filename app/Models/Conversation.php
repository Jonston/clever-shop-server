<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class Conversation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'session_id',
        'title',
        'status',
        'metadata',
        'last_message_at',
    ];

    protected $casts = [
        'metadata' => 'array',
        'last_message_at' => 'datetime',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function lastMessage(): HasMany
    {
        return $this->hasMany(Message::class)->latest('created_at')->limit(1);
    }

    // Helper methods
    public function isGuest(): bool
    {
        return $this->user_id === null && $this->session_id !== null;
    }

    public static function generateSessionId(): string
    {
        return Str::uuid()->toString();
    }

    public static function findOrCreateForOwner(?int $userId, ?string $sessionId): self
    {
        if ($userId) {
            // For authenticated users, find the latest active conversation or create new
            return static::firstOrCreate(
                ['user_id' => $userId, 'status' => 'active'],
                ['title' => null]
            );
        }

        if ($sessionId) {
            // For guests, find or create by session_id
            return static::firstOrCreate(
                ['session_id' => $sessionId, 'status' => 'active'],
                ['title' => null]
            );
        }

        // Create new guest conversation
        $newSessionId = static::generateSessionId();
        return static::create([
            'session_id' => $newSessionId,
            'status' => 'active',
        ]);
    }

    public static function getForOwner(?int $userId, ?string $sessionId): Collection
    {
        $query = static::where('status', '!=', 'deleted');

        if ($userId) {
            $query->where('user_id', $userId);
        } elseif ($sessionId) {
            $query->where('session_id', $sessionId);
        } else {
            return collect();
        }

        return $query->orderBy('last_message_at', 'desc')->get();
    }

    public function generateTitle(): void
    {
        if ($this->title) {
            return;
        }

        $firstUserMessage = $this->messages()
            ->where('role', 'user')
            ->oldest()
            ->first();

        if ($firstUserMessage) {
            $title = Str::limit($firstUserMessage->content, 50);
            $this->update(['title' => $title]);
        }
    }

    public function getContext(int $limit = 20): array
    {
        $messages = $this->messages()
            ->whereIn('role', ['user', 'assistant'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values();

        return $messages->map(function ($message) {
            return [
                'role' => $message->role === 'assistant' ? 'model' : 'user',
                'content' => $message->content,
            ];
        })->toArray();
    }
}
