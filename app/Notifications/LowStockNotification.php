<?php

namespace App\Notifications;

use App\Models\LowStockAlert;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LowStockNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly LowStockAlert $alert) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $productName = $this->alert->variant?->product?->name ?? $this->alert->product->name;

        return (new MailMessage)
            ->subject("Low stock alert: {$productName}")
            ->line("\"{$productName}\" at {$this->alert->warehouse->name} has dropped to {$this->alert->current_quantity} units (threshold {$this->alert->threshold}).")
            ->action('Review stock', url('/admin/inventory/low-stock'));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'low_stock',
            'product_id' => $this->alert->product_id,
            'product_variant_id' => $this->alert->product_variant_id,
            'warehouse_id' => $this->alert->warehouse_id,
            'current_quantity' => $this->alert->current_quantity,
            'threshold' => $this->alert->threshold,
        ];
    }
}
