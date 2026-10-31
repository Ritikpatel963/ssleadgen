<?php

namespace App\Notifications;

use App\Models\Client;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

class StageChangeNotification extends Notification implements ShouldBroadcast
{
    use Queueable;

    public function __construct(
        public Client $client,
        public string $oldStage,
        public string $newStage
    ) {}

    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => 'Client Stage Changed',
            'message' => "{$this->client->first_name} {$this->client->last_name} moved from {$this->oldStage} to {$this->newStage}.",
            'client_id' => $this->client->id,
            'old_stage' => $this->oldStage,
            'new_stage' => $this->newStage,
            'icon' => 'stage-change',
            'url' => route('clients.login'),
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage($this->toArray($notifiable));
    }
}
