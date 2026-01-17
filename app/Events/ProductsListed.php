<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProductsListed implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public array $products,
        public ?string $category = null
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel('assistant'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'products.listed';
    }

    public function broadcastWith(): array
    {
        return [
            'count' => count($this->products),
            'category' => $this->category,
        ];
    }
}
