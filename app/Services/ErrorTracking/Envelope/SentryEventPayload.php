<?php

namespace App\Services\ErrorTracking\Envelope;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;

class SentryEventPayload
{
    /**
     * @param  array<string, mixed>  $raw
     */
    public function __construct(public readonly array $raw) {}

    public function eventId(): string
    {
        $value = (string) ($this->raw['event_id'] ?? '');

        return strtolower(str_replace('-', '', $value));
    }

    public function platform(): string
    {
        return (string) ($this->raw['platform'] ?? 'other');
    }

    public function level(): string
    {
        $level = strtolower((string) ($this->raw['level'] ?? 'error'));

        return in_array($level, ['fatal', 'error', 'warning', 'info', 'debug'], true) ? $level : 'error';
    }

    public function environment(): ?string
    {
        return $this->raw['environment'] ?? null;
    }

    public function release(): ?string
    {
        return $this->raw['release'] ?? null;
    }

    public function serverName(): ?string
    {
        return $this->raw['server_name'] ?? null;
    }

    public function transaction(): ?string
    {
        return $this->raw['transaction'] ?? null;
    }

    public function message(): ?string
    {
        $message = $this->raw['message'] ?? null;

        if (is_array($message)) {
            return $message['formatted'] ?? $message['message'] ?? null;
        }

        return is_string($message) ? $message : null;
    }

    public function occurredAt(): CarbonInterface
    {
        $timestamp = $this->raw['timestamp'] ?? null;

        if (is_numeric($timestamp)) {
            return CarbonImmutable::createFromTimestamp((float) $timestamp);
        }

        if (is_string($timestamp) && $timestamp !== '') {
            return CarbonImmutable::parse($timestamp);
        }

        return CarbonImmutable::now();
    }

    /** @return array<string, mixed>|null */
    public function exception(): ?array
    {
        $exception = $this->raw['exception'] ?? null;

        if (! is_array($exception)) {
            return null;
        }

        return $exception;
    }

    /** @return array<int, array<string, mixed>> */
    public function exceptionValues(): array
    {
        $exception = $this->exception();

        if (! $exception) {
            return [];
        }

        if (isset($exception['values']) && is_array($exception['values'])) {
            return array_values($exception['values']);
        }

        if (array_is_list($exception)) {
            return $exception;
        }

        return [$exception];
    }

    /** @return array<int, array<string, mixed>> */
    public function frames(): array
    {
        $values = $this->exceptionValues();

        foreach ($values as $value) {
            $frames = $value['stacktrace']['frames'] ?? null;
            if (is_array($frames) && $frames !== []) {
                return $frames;
            }
        }

        $top = $this->raw['stacktrace']['frames'] ?? null;

        return is_array($top) ? $top : [];
    }

    /** @return array<int, string>|null */
    public function fingerprint(): ?array
    {
        $fp = $this->raw['fingerprint'] ?? null;
        if (! is_array($fp) || $fp === []) {
            return null;
        }

        return array_map(fn ($v) => (string) $v, $fp);
    }

    public function exceptionType(): ?string
    {
        return $this->exceptionValues()[0]['type'] ?? null;
    }

    public function exceptionValue(): ?string
    {
        return $this->exceptionValues()[0]['value'] ?? null;
    }

    /** @return array<string, mixed>|null */
    public function breadcrumbs(): ?array
    {
        $value = $this->raw['breadcrumbs'] ?? null;

        return is_array($value) ? $value : null;
    }

    /** @return array<string, mixed>|null */
    public function tags(): ?array
    {
        $tags = $this->raw['tags'] ?? null;

        return is_array($tags) ? $tags : null;
    }

    /** @return array<string, mixed>|null */
    public function contexts(): ?array
    {
        $contexts = $this->raw['contexts'] ?? null;

        return is_array($contexts) ? $contexts : null;
    }

    /** @return array<string, mixed>|null */
    public function request(): ?array
    {
        $request = $this->raw['request'] ?? null;

        return is_array($request) ? $request : null;
    }

    /** @return array<string, mixed>|null */
    public function user(): ?array
    {
        $user = $this->raw['user'] ?? null;

        return is_array($user) ? $user : null;
    }

    /** @return array<string, mixed>|null */
    public function extra(): ?array
    {
        $extra = $this->raw['extra'] ?? null;

        return is_array($extra) ? $extra : null;
    }

    /** @return array<string, mixed>|null */
    public function sdk(): ?array
    {
        $sdk = $this->raw['sdk'] ?? null;

        return is_array($sdk) ? $sdk : null;
    }
}
