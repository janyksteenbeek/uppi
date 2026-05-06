<?php

namespace App\Models;

use App\Enums\Alerts\AlertType;
use App\Models\ErrorTracking\IssueAnomalyRule;
use App\Observers\UserIdObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Slack\SlackRoute;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
use NotificationChannels\Bird\BirdRoute;
use NotificationChannels\Expo\ExpoPushToken;
use NotificationChannels\Messagebird\MessagebirdRoute;
use NotificationChannels\Pushover\PushoverReceiver;

#[ObservedBy(UserIdObserver::class)]
class Alert extends Model
{
    use HasFactory, HasUlids, Notifiable;

    protected $guarded = [];

    protected $casts = [
        'is_enabled' => 'boolean',
        'type' => AlertType::class,
        'config' => 'array',
    ];

    protected $hidden = [
        'config',
    ];

    protected static function booted()
    {
        static::addGlobalScope('user', function (Builder $builder) {
            if (Auth::hasUser()) {
                $builder->where('user_id', Auth::id());
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function monitors(): BelongsToMany
    {
        return $this->belongsToMany(Monitor::class);
    }

    public function anomalies(): HasMany
    {
        return $this->hasMany(Anomaly::class);
    }

    public function issueAnomalyRules(): BelongsToMany
    {
        return $this->belongsToMany(IssueAnomalyRule::class, 'alert_issue_anomaly_rule', 'alert_id', 'issue_anomaly_rule_id');
    }

    public function routeNotificationForMail(): ?string
    {
        if ($this->type !== AlertType::EMAIL) {
            return null;
        }

        return $this->destination;
    }

    public function routeNotificationForSlack(Notification $notification): ?SlackRoute
    {
        if ($this->type !== AlertType::SLACK) {
            return null;
        }

        if (! isset($this->config['slack_token'])) {
            throw new InvalidArgumentException('Slack token and channel are required');
        }

        return SlackRoute::make($this->destination, $this->config['slack_token']);
    }

    public function routeNotificationForMessagebird(Notification $notification): ?MessagebirdRoute
    {
        if ($this->type !== AlertType::MESSAGEBIRD) {
            return null;
        }

        if (! isset($this->config['bird_api_key']) || ! isset($this->config['bird_originator'])) {
            throw new InvalidArgumentException('Bird API key and originator are required');
        }

        if (empty($this->destination)) {
            throw new InvalidArgumentException('Destination is required');
        }

        return MessagebirdRoute::make([$this->destination], $this->config['bird_api_key'], $this->config['bird_originator']);
    }

    public function routeNotificationForPushover()
    {
        if ($this->type !== AlertType::PUSHOVER) {
            return null;
        }

        return PushoverReceiver::withUserKey($this->destination)->withApplicationToken($this->config['pushover_api_token']);
    }

    public function routeNotificationForTelegram(Notification $notification)
    {
        if ($this->type !== AlertType::TELEGRAM) {
            return null;
        }

        return $this->destination;
    }

    public function routeNotificationForBird(Notification $notification): ?BirdRoute
    {
        if ($this->type !== AlertType::BIRD) {
            return null;
        }

        if (! isset($this->config['bird_api_key']) || ! isset($this->config['bird_workspace_id']) || ! isset($this->config['bird_channel_id'])) {
            throw new InvalidArgumentException('Bird API key, workspace ID and channel ID are required');
        }

        if (empty($this->destination)) {
            throw new InvalidArgumentException('Destination is required');
        }

        return BirdRoute::make([$this->destination], $this->config['bird_api_key'], $this->config['bird_workspace_id'], $this->config['bird_channel_id']);
    }

    public function routeNotificationForExpo(): ?ExpoPushToken
    {
        if ($this->type !== AlertType::EXPO) {
            return null;
        }

        return ExpoPushToken::make($this->destination);
    }
}
