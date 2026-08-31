<?php

namespace App\Notifications;

use App\Mail\PasswordResetMail;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;

class ResetPasswordNotification extends ResetPassword
{
    public function toMail($notifiable): PasswordResetMail
    {
        /** @var User $notifiable */
        $expireMinutes = (int) config('auth.passwords.users.expire', 60);

        return (new PasswordResetMail(
            resetUrl: $this->resetUrl($notifiable),
            recipientName: $notifiable->name,
            expireMinutes: $expireMinutes,
        ))->to($notifiable->getEmailForPasswordReset());
    }

    protected function resetUrl(mixed $notifiable): string
    {
        return url(route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ], false));
    }
}
