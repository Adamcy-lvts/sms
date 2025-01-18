<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TestReverbConnection implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $message;

    public function __construct()
    {
        $this->message = "Test connection at " . now();
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('test-channel')
        ];
    }

    public function broadcastAs(): string
    {
        return 'test-event';
    }
}