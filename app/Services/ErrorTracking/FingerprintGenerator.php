<?php

namespace App\Services\ErrorTracking;

use App\Services\ErrorTracking\Envelope\SentryEventPayload;

class FingerprintGenerator
{
    /**
     * The placeholder Sentry SDKs use to mean "use the default grouping".
     */
    private const DEFAULT_PLACEHOLDER = '{{ default }}';

    public function hash(SentryEventPayload $event): string
    {
        $parts = $this->parts($event);

        return sha1(implode("\0", $parts));
    }

    /**
     * @return array<int, string>
     */
    public function parts(SentryEventPayload $event): array
    {
        $custom = $event->fingerprint();

        if ($custom !== null) {
            $expanded = [];
            foreach ($custom as $part) {
                if ($part === self::DEFAULT_PLACEHOLDER) {
                    $expanded = array_merge($expanded, $this->defaultParts($event));
                } else {
                    $expanded[] = $part;
                }
            }

            if ($expanded !== []) {
                return $expanded;
            }
        }

        return $this->defaultParts($event);
    }

    /**
     * @return array<int, string>
     */
    private function defaultParts(SentryEventPayload $event): array
    {
        $type = $event->exceptionType();

        if ($type !== null && $type !== '') {
            $frame = $this->firstInAppFrame($event->frames());

            if ($frame === null) {
                return ['exception', $type];
            }

            return [
                'exception',
                $type,
                (string) ($frame['function'] ?? ''),
                (string) ($frame['filename'] ?? $frame['module'] ?? ''),
            ];
        }

        $message = $event->message();

        if ($message !== null && $message !== '') {
            return ['message', $message];
        }

        return ['event', $event->eventId()];
    }

    /**
     * @param  array<int, array<string, mixed>>  $frames
     * @return array<string, mixed>|null
     */
    private function firstInAppFrame(array $frames): ?array
    {
        $iterable = array_reverse($frames);

        foreach ($iterable as $frame) {
            if (($frame['in_app'] ?? null) === true) {
                return $frame;
            }
        }

        return $iterable[0] ?? null;
    }
}
