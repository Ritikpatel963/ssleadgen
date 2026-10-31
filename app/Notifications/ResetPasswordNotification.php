<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordNotification extends ResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        $url = $this->resetUrl($notifiable);
        $data = [
            'url' => $url,
            'userName' => $notifiable->name,
            'expiration' => config('auth.passwords.'.config('auth.defaults.passwords').'.expire'),
        ];

        return (new MailMessage)
            ->subject('Reset your SS Advisory password')
            ->view('emails.auth.password-reset', $data)
            ->text('emails.auth.password-reset-text', $data);
    }
}
