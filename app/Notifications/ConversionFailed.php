<?php

namespace App\Notifications;

use App\Models\Conversion;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ConversionFailed extends Notification
{
    use Queueable;

    public function __construct(public Conversion $conversion) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('We could not convert your image')
            ->greeting('Hi '.$notifiable->name.',')
            ->line('Sorry, your image "'.$this->conversion->original_filename.'" could not be converted.')
            ->line('Please try again with a different image.')
            ->action('View Conversion', route('conversions.show', $this->conversion->uuid))
            ->line('Thank you for using '.config('app.name').'.');
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'conversion_uuid' => $this->conversion->uuid,
            'message' => 'Your image "'.$this->conversion->original_filename.'" could not be converted.',
            'url' => route('conversions.show', $this->conversion->uuid),
        ];
    }
}