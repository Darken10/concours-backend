<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EmailVerificationCode extends Notification
{
    /**
     * Create a new notification instance.
     */
    public function __construct(public string $verificationCode) {}

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
        return (new MailMessage)
            ->subject('Code de vérification de votre compte')
            ->greeting('Bonjour '.$notifiable->firstname.' !')
            ->line('Merci de vous être inscrit sur notre plateforme.')
            ->line('Votre code de vérification est :')
            ->line('**'.$this->verificationCode.'**')
            ->line('Ce code est valable pendant 15 minutes.')
            ->line('Si vous n\'avez pas créé de compte, ignorez cet email.');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'verification_code' => $this->verificationCode,
        ];
    }
}
