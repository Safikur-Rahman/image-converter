<?php

namespace App\Notifications;

use App\Models\Conversion;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ConversionCompleted extends Notification
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
            ->subject('Your image is ready to download')
            ->greeting('Hi '.$notifiable->name.',')
            ->line('Your image "'.$this->conversion->original_filename.'" has been converted to SVG.')
            ->action('View Conversion', route('conversions.show', $this->conversion->uuid))
            ->line('Thank you for using '.config('app.name').'.');
    }

    public function toDatabase(object $notifiable): array
    {
        return [
            'conversion_uuid' => $this->conversion->uuid,
            'message' => 'Your image "'.$this->conversion->original_filename.'" has finished converting.',
            'url' => route('conversions.show', $this->conversion->uuid),
        ];
    }
}