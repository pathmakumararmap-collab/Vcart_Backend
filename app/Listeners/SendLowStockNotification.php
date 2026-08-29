<?php

namespace App\Listeners;

use App\Events\LowStockDetected;
use App\Models\User;
use App\Notifications\LowStockNotification;
use Illuminate\Contracts\Queue\ShouldQueue;

class SendLowStockNotification implements ShouldQueue
{
    public function handle(LowStockDetected $event): void
    {
        $recipients = User::query()
            ->whereHas('roles', fn ($query) => $query->whereIn('name', ['admin', 'warehouse_manager']))
            ->get();

        foreach ($recipients as $recipient) {
            $recipient->notify(new LowStockNotification($event->alert));
        }
    }
}
