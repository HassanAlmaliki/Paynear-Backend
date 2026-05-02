<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FcmNotification;
use Illuminate\Support\Facades\Log;

class BalanceUpdatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $amount;
    public $type;
    public $newBalance;
    public $message;

    /**
     * Create a new notification instance.
     */
    public function __construct($amount, $type, $newBalance, $message = null)
    {
        $this->amount = $amount;
        $this->type = $type;
        $this->newBalance = $newBalance;
        
        $this->message = $message ?? ($type === 'deposit' || $type === 'incoming' 
            ? "تم إيداع {$amount} إلى حسابك" 
            : "تم خصم {$amount} ريال من رصيدك.");
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $title = $this->type === 'deposit' || $this->type === 'incoming' ? 'إيداع رصيد' : 'عملية مالية';
        
        // Send FCM Notification manually since there's no native channel in this package
        if (!empty($notifiable->fcm_token)) {
            try {
                $messaging = app(Messaging::class);
                $message = CloudMessage::withTarget('token', $notifiable->fcm_token)
                    ->withNotification(FcmNotification::create($title, $this->message))
                    ->withData([
                        'type' => 'balance_update',
                        'amount' => (string) $this->amount,
                        'new_balance' => (string) $this->newBalance,
                    ]);
                $messaging->send($message);
            } catch (\Exception $e) {
                Log::error("FCM Send Error: " . $e->getMessage());
            }
        }

        return [
            'title' => $title,
            'body' => $this->message,
            'amount' => $this->amount,
            'type' => $this->type,
            'new_balance' => $this->newBalance,
        ];
    }
}
