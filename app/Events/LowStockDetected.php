<?php

namespace App\Events;

use App\Models\LowStockAlert;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LowStockDetected
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly LowStockAlert $alert) {}
}
