<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AssistantProcessing implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $action,
        public string $message
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('assistant'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'assistant.processing';
    }

    public function broadcastWith(): array
    {
        return [
            'action' => $this->action,
            'message' => $this->message,
        ];
    }
}
