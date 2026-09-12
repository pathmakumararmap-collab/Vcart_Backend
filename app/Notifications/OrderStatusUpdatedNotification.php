<?php

namespace App\Notifications;

use App\Models\Order;
use App\Notifications\Channels\NotifyLkChannel;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderStatusUpdatedNotification extends Notification
{
    use Queueable;

    private const STATUS_LABELS = [
        'shipped' => 'shipped',
        'delivered' => 'delivered',
        'cancelled' => 'cancelled',
    ];

    public function __construct(public readonly Order $order) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        $channels = ['mail', NotifyLkChannel::class];

        if ($notifiable instanceof \App\Models\User) {
            $channels[] = 'database';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $label = self::STATUS_LABELS[$this->order->status] ?? $this->order->status;

        return (new MailMessage)
            ->subject("Order {$this->order->order_no} — {$label}")
            ->line("Your order {$this->order->order_no} is now {$label}.")
            ->action('View order', url("/orders/{$this->order->id}"));
    }

    public function toNotifyLk(object $notifiable): string
    {
        $label = self::STATUS_LABELS[$this->order->status] ?? $this->order->status;

        return "Vcart: Your order {$this->order->order_no} is now {$label}.";
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'order_status_updated',
            'order_id' => $this->order->id,
            'order_no' => $this->order->order_no,
            'status' => $this->order->status,
        ];
    }
}
