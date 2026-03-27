<?php

namespace App\Notifications;

use App\Models\Practice;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class LowStockNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        private Practice $practice,
        private array $lowStockProducts,
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $message = (new MailMessage)
            ->subject("Low Stock Alert - {$this->practice->name}")
            ->greeting("Low Stock Alert!")
            ->line("The following products in {$this->practice->name} are running low on inventory:");

        foreach ($this->lowStockProducts as $product) {
            $message->line("• **{$product->name}**: {$product->stock_quantity} remaining (threshold: {$product->low_stock_threshold})");
        }

        return $message
            ->action('View Inventory', url('/admin/inventory-products'))
            ->line('Please restock these items as soon as possible.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'practice_id' => $this->practice->id,
            'practice_name' => $this->practice->name,
            'product_count' => count($this->lowStockProducts),
            'products' => $this->lowStockProducts->map(fn($p) => [
                'id' => $p->id,
                'name' => $p->name,
                'stock' => $p->stock_quantity,
                'threshold' => $p->low_stock_threshold,
            ])->toArray(),
        ];
    }
}
