<?php

namespace App\Models;

use App\Enums\Checks\Status;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements FilamentUser, MustVerifyEmail
{
    use HasApiTokens, HasFactory, HasUlids, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'is_admin',
    ];

    public function canAccessPanel(Panel $panel): bool
    {
        if (! $this->is_admin && $panel->getId() === 'admin') {
            return false;
        }

        return true;
    }

    public function checks(): HasManyThrough
    {
        return $this->hasManyThrough(Check::class, Monitor::class);
    }

    public function alerts(): HasMany
    {
        return $this->hasMany(Alert::class);
    }

    public function statusPages(): HasMany
    {
        return $this->hasMany(StatusPage::class);
    }

    public function impersonate()
    {
        auth()->loginUsingId($this->id);
    }

    public function isOk(): bool
    {
        return ! $this->monitors()
            ->where('status', Status::FAIL)
            ->where('is_enabled', true)
            ->exists();
    }

    public function failingCount(): int
    {
        return $this->monitors()
            ->where('status', Status::FAIL)
            ->where('is_enabled', true)
            ->count();
    }

    public function monitors(): HasMany
    {
        return $this->hasMany(Monitor::class);
    }

    public function servers(): HasMany
    {
        return $this->hasMany(Server::class);
    }

    public function anomalies(): HasManyThrough
    {
        return $this->hasManyThrough(Anomaly::class, Monitor::class);
    }

    public function alertTriggers(): HasManyThrough
    {
        return $this->hasManyThrough(AlertTrigger::class, Monitor::class);
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }

    public function createTemporaryDeviceAuthToken(): string
    {
        $activationCode = strtoupper(Str::random(6));
        $token = $this->createToken('Mobile device (not activated)', expiresAt: now()->addMinutes(15));
        $accessToken = $token->accessToken;
        $accessToken->activation_code = $activationCode;
        $accessToken->save();

        return $activationCode;
    }
}
