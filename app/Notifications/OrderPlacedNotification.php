<?php

namespace App\Notifications;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderPlacedNotification extends Notification
{
    use Queueable;

    public function __construct(public readonly Order $order) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Order confirmation — {$this->order->order_no}")
            ->line("Thank you! Your order {$this->order->order_no} has been received.")
            ->line("Total: {$this->order->currency} ".number_format((float) $this->order->total_amount, 2))
            ->action('View order', url("/orders/{$this->order->id}"));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'order_placed',
            'order_id' => $this->order->id,
            'order_no' => $this->order->order_no,
            'total_amount' => $this->order->total_amount,
        ];
    }
}
