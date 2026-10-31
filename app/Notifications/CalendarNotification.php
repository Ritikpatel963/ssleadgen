<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class CalendarNotification extends Notification implements ShouldBroadcast
{
    use Queueable;

    public function __construct(
        public string $title,
        public string $clientName,
        public string $appointmentDate,
        public string $appointmentTime
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'New Appointment Scheduled',
            'message' => "Appointment \"{$this->title}\" for {$this->clientName} on {$this->appointmentDate} at {$this->appointmentTime}.",
            'icon' => 'calendar',
            'url' => route('calendar.index'),
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
