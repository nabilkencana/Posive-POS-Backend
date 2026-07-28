<?php

namespace App\Events;

use App\Models\Table;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TableStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Table $table;

    public function __construct(Table $table)
    {
        $this->table = $table;
    }

    public function broadcastOn(): array
    {
        return [
            new Channel('tables'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'table.updated';
    }
}
