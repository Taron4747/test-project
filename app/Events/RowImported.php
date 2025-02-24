<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RowImported implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $row;

    public function __construct(array $row)
    {
        $this->row = $row;
    }

    public function broadcastOn()
    {
        return new Channel('import-channel'); // Канал, на который подписывается фронтенд
    }

    public function broadcastAs()
    {
        return 'row.imported'; // Имя события
    }

    public function broadcastWith()
    {
        return [
            'row' => $this->row
        ];
    }
}
