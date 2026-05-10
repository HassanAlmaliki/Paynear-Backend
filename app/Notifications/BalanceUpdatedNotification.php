<?php

namespace App\Notifications;


use Illuminate\Notifications\Notification;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification as FcmNotification;
use Illuminate\Support\Facades\Log;

class BalanceUpdatedNotification extends Notification
{

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
        // Send FCM push notification separately (not inside toArray)
        $this->sendFcmPush($notifiable);

        return ['database'];
    }

    /**
     * Send FCM push notification to the device.
     * Separated from toArray() to avoid side effects during data serialization.
     */
    private function sendFcmPush(object $notifiable): void
    {
        if (empty($notifiable->fcm_token)) {
            Log::info("FCM Skip: No fcm_token for {$notifiable->getMorphClass()} #{$notifiable->id}");
            return;
        }

        $title = $this->type === 'deposit' || $this->type === 'incoming' ? 'إيداع رصيد' : 'عملية مالية';

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
            Log::info("FCM Sent: to {$notifiable->getMorphClass()} #{$notifiable->id}");
        } catch (\Kreait\Firebase\Exception\Messaging\NotFound $e) {
            // Token is expired or invalid — clear it so the app registers a fresh one on next login
            Log::warning("FCM Token expired for {$notifiable->getMorphClass()} #{$notifiable->id}, clearing stale token");
            $notifiable->update(['fcm_token' => null]);
        } catch (\Exception $e) {
            Log::error("FCM Send Error for {$notifiable->getMorphClass()} #{$notifiable->id}: " . $e->getMessage());
        }
    }

    /**
     * Get the array representation of the notification.
     * This is stored in the database 'data' column as JSON.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        $title = $this->type === 'deposit' || $this->type === 'incoming' ? 'إيداع رصيد' : 'عملية مالية';

        return [
            'title' => $title,
            'body' => $this->message,
            'amount' => $this->amount,
            'type' => $this->type,
            'new_balance' => $this->newBalance,
        ];
    }
}
