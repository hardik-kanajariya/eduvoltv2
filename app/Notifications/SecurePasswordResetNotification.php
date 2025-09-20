<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Lang;

class SecurePasswordResetNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * The password reset token.
     */
    public string $token;

    /**
     * Create a new notification instance.
     */
    public function __construct(string $token)
    {
        $this->token = $token;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $url = url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));

        return $this->buildMailMessage($url, $notifiable);
    }

    /**
     * Build the mail message.
     */
    protected function buildMailMessage(string $url, object $notifiable): MailMessage
    {
        $expireMinutes = config('auth.passwords.users.expire', 60);
        
        return (new MailMessage)
            ->subject(Lang::get('Reset Password Notification'))
            ->line(Lang::get('You are receiving this email because we received a password reset request for your account.'))
            ->action(Lang::get('Reset Password'), $url)
            ->line(Lang::get('This password reset link will expire in :count minutes.', ['count' => $expireMinutes]))
            ->line(Lang::get('If you did not request a password reset, no further action is required.'))
            ->line('')
            ->line('**Security Information:**')
            ->line('• This link is valid for ' . $expireMinutes . ' minutes only')
            ->line('• The link can only be used once')
            ->line('• If you did not request this reset, please contact support immediately')
            ->line('• Reset requested at: ' . now()->format('Y-m-d H:i:s T'))
            ->line('• IP Address: ' . (request()->ip() ?? 'Unknown'))
            ->salutation('Regards, ' . config('app.name') . ' Security Team');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'action' => 'password_reset_requested',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'requested_at' => now()->toISOString(),
        ];
    }
}
