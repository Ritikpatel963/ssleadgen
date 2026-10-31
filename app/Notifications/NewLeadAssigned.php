<?php

namespace App\Notifications;

use App\Models\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class NewLeadAssigned extends Notification implements ShouldBroadcast
{
    use Queueable;

    public function __construct(public Client $client) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'New Lead Assigned',
            'message' => "{$this->client->first_name} {$this->client->last_name} was assigned to your pipeline.",
            'client_id' => $this->client->id,
            'icon' => 'new-lead',
            'url' => route('clients.login'),
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
