<?php

use App\Services\ErrorTracking\Envelope\SentryEventPayload;
use App\Services\ErrorTracking\FingerprintGenerator;

it('groups by exception type + first in-app frame by default', function () {
    $generator = new FingerprintGenerator;

    $a = new SentryEventPayload([
        'platform' => 'php',
        'exception' => ['values' => [[
            'type' => 'RuntimeException',
            'value' => 'something different',
            'stacktrace' => ['frames' => [[
                'filename' => 'app/Foo.php',
                'function' => 'work',
                'lineno' => 7,
                'in_app' => true,
            ]]],
        ]]],
    ]);

    $b = new SentryEventPayload([
        'platform' => 'php',
        'exception' => ['values' => [[
            'type' => 'RuntimeException',
            'value' => 'a wholly different message',
            'stacktrace' => ['frames' => [[
                'filename' => 'app/Foo.php',
                'function' => 'work',
                'lineno' => 7,
                'in_app' => true,
            ]]],
        ]]],
    ]);

    expect($generator->hash($a))->toBe($generator->hash($b));
});

it('respects custom fingerprints from the SDK', function () {
    $generator = new FingerprintGenerator;

    $event = new SentryEventPayload([
        'platform' => 'php',
        'fingerprint' => ['my-custom-key', 'sub'],
        'exception' => ['values' => [['type' => 'A']]],
    ]);

    expect($generator->parts($event))->toBe(['my-custom-key', 'sub']);
});

it('falls back to the message when no exception is present', function () {
    $generator = new FingerprintGenerator;

    $event = new SentryEventPayload([
        'platform' => 'php',
        'message' => 'something went wrong',
    ]);

    expect($generator->parts($event))->toBe(['message', 'something went wrong']);
});
