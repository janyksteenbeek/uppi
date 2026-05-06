<?php

namespace App\Notifications\ErrorTracking;

use App\Models\ErrorTracking\IssueAnomaly;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Slack\SlackMessage;
use NotificationChannels\Bird\BirdMessage;
use NotificationChannels\Expo\ExpoMessage;
use NotificationChannels\Messagebird\MessagebirdMessage;
use NotificationChannels\Pushover\PushoverMessage;
use NotificationChannels\Telegram\TelegramMessage;

class IssueDetectedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        protected IssueAnomaly $anomaly,
        protected string $headlineEmoji = '🐛',
        protected string $headlinePrefix = 'New issue',
    ) {}

    public function via(object $notifiable): array
    {
        return [$notifiable->type->toNotificationChannel()];
    }

    protected function issue()
    {
        return $this->anomaly->issue;
    }

    protected function project()
    {
        return $this->issue()->project;
    }

    protected function headline(): string
    {
        $project = $this->project();

        return "{$this->headlineEmoji} {$this->headlinePrefix} in {$project->name}: {$this->issue()->title}";
    }

    public function toMail(object $notifiable): MailMessage
    {
        $issue = $this->issue();
        $project = $this->project();

        $mail = (new MailMessage)
            ->error()
            ->subject($this->headline())
            ->greeting($this->headlinePrefix.' detected in '.$project->name.'.')
            ->line('Issue: '.$issue->title);

        if ($issue->culprit) {
            $mail->line('Culprit: '.$issue->culprit);
        }

        if ($issue->level) {
            $mail->line('Level: '.$issue->level->value);
        }

        $mail->line('Times seen: '.$issue->times_seen);

        return $mail
            ->action('View issue', url('/'))
            ->line('Thank you for using '.config('app.name').'!');
    }

    public function toMessagebird($notifiable): MessagebirdMessage
    {
        return new MessagebirdMessage($this->headline());
    }

    public function toBird(object $notifiable): BirdMessage
    {
        return new BirdMessage($this->headline());
    }

    public function toTelegram($notifiable)
    {
        $issue = $this->issue();

        return TelegramMessage::create()
            ->content($this->headline())
            ->line('Times seen: '.$issue->times_seen)
            ->line('Culprit: '.($issue->culprit ?? '-'))
            ->button('View issue', url('/'));
    }

    public function toPushover(object $notifiable): PushoverMessage
    {
        $issue = $this->issue();

        return PushoverMessage::create()
            ->title($this->headline())
            ->content('Times seen: '.$issue->times_seen.' | Culprit: '.($issue->culprit ?? '-'))
            ->url(url('/'), 'View issue');
    }

    public function toExpo(object $notifiable): ExpoMessage
    {
        $issue = $this->issue();

        return ExpoMessage::create()
            ->title($this->headline())
            ->body('Times seen: '.$issue->times_seen.' — '.($issue->culprit ?? ''))
            ->ttl(3600)
            ->priority('high');
    }

    public function toSlack(object $notifiable): SlackMessage
    {
        $issue = $this->issue();
        $project = $this->project();
        $title = addslashes($issue->title);
        $culprit = addslashes($issue->culprit ?? '-');
        $level = $issue->level?->value ?? 'error';

        $template = <<<JSON
        {
            "blocks": [
                {
                    "type": "header",
                    "text": {
                        "type": "plain_text",
                        "text": "{$this->headlineEmoji} {$this->headlinePrefix} in {$project->name}",
                        "emoji": true
                    }
                },
                {
                    "type": "section",
                    "text": { "type": "mrkdwn", "text": "*{$title}*" }
                },
                {
                    "type": "section",
                    "fields": [
                        { "type": "mrkdwn", "text": "*Level:*\\n{$level}" },
                        { "type": "mrkdwn", "text": "*Times seen:*\\n{$issue->times_seen}" },
                        { "type": "mrkdwn", "text": "*Culprit:*\\n{$culprit}" }
                    ]
                }
            ]
        }
        JSON;

        return (new SlackMessage)->usingBlockKitTemplate($template);
    }
}
