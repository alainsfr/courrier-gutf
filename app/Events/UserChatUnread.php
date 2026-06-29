<?php

namespace App\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserChatUnread implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public function __construct(public int $userId, public int $unreadTotal)
    {
    }

    public function broadcastOn(): PrivateChannel
    {
        return new PrivateChannel('users.' . $this->userId);
    }

    public function broadcastAs(): string
    {
        return 'chat.unread';
    }

    public function broadcastWith(): array
    {
        return [
            'user_id' => $this->userId,
            'unread_total' => $this->unreadTotal,
        ];
    }
}
