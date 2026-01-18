<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AssistantIterationComplete implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $conversationId,
        public int $iteration,
        public string $functionName,
        public int $timeMs
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel("conversation.{$this->conversationId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'iteration.complete';
    }

    public function broadcastWith(): array
    {
        return [
            'iteration' => $this->iteration,
            'function_name' => $this->functionName,
            'time_ms' => $this->timeMs,
        ];
    }
}
