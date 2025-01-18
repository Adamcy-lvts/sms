<?php

namespace App\Events;

use App\Models\Feedback;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class OpenFeedbackModal
{
    use Dispatchable, InteractsWithSockets, SerializesModels;
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $feedbackId;

    public function __construct(Feedback $feedback)
    {
        $this->feedbackId = $feedback->id;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('feedback')
        ];
    }

    public function broadcastAs(): string
    {
        return 'open-modal';
    }
}
