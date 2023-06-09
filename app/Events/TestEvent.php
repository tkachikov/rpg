<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;

class TestEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        private readonly User   $user,
        private readonly string $message = 'test',
        private readonly null|array|string $img = null,
        private readonly ?float $start = null,
    ) {
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('test-'.$this->user->id),
        ];
    }

    public function broadcastWith()
    {
        return [
            'uuid' => Str::uuid(),
            'time' => now()->format('H:i:s.u'),
            'microtime' => $now = (int) (microtime(true) * 1000),
            'exec' => $now - (int) ($this->start * 1000),
            'message' => $this->message,
            'render' => (bool) $this->img,
            'img' => is_string($this->img) && $this->img
                ? [$this->img]
                : $this->img,
        ];
    }
}
