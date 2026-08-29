<?php

namespace App\Listeners;

use App\Events\OrderPlaced;
use App\Notifications\OrderPlacedNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendOrderPlacedNotification implements ShouldQueue
{
    public function handle(OrderPlaced $event): void
    {
        $event->order->user?->notify(new OrderPlacedNotification($event->order));
    }
}
