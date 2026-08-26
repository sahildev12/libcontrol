<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordNotification extends ResetPassword
{
    public function __construct(
        string $token,
        public string $portal = 'branch',
    ) {
        parent::__construct($token);
    }

    public function toMail($notifiable)
    {
        $url = $this->resetUrl($notifiable);
        $loginLabel = $this->portal === 'admin' ? 'admin login' : 'branch login';

        return (new MailMessage)
            ->subject('Reset your LibSpace password')
            ->greeting('Reset your password')
            ->line('We received a request to reset the password for your '.$loginLabel.' account.')
            ->action('Reset Password', $url)
            ->line('This link expires in '.config('auth.passwords.users.expire', 60).' minutes.')
            ->line('If you did not request a password reset, you can ignore this email.');
    }

    protected function resetUrl(mixed $notifiable): string
    {
        return url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
            'portal' => $this->portal,
        ], false));
    }
}
