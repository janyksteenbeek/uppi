<?php

namespace App\Services\ErrorTracking\Envelope;

class EnvelopeParser
{
    /**
     * Parse a Sentry envelope payload into a header + a list of items.
     *
     * @return array{header: array<string, mixed>, items: array<int, array{header: array<string, mixed>, payload: array<string, mixed>|string}>}
     */
    public function parse(string $body): array
    {
        $body = ltrim($body, "\xEF\xBB\xBF");
        $lines = preg_split("/\r\n|\n|\r/", $body);

        if ($lines === false || count($lines) === 0) {
            throw new InvalidEnvelopeException('Empty envelope');
        }

        while (count($lines) > 0 && trim((string) end($lines)) === '') {
            array_pop($lines);
        }

        if (count($lines) === 0) {
            throw new InvalidEnvelopeException('Envelope has no header');
        }

        $headerLine = array_shift($lines);
        $header = $this->decodeJsonObject($headerLine, 'envelope header');

        $items = [];

        while (count($lines) > 0) {
            $itemHeaderLine = array_shift($lines);

            if (trim($itemHeaderLine) === '') {
                continue;
            }

            $itemHeader = $this->decodeJsonObject($itemHeaderLine, 'item header');

            if (! isset($itemHeader['type']) || ! is_string($itemHeader['type'])) {
                throw new InvalidEnvelopeException('Item header missing type');
            }

            $payloadLine = count($lines) > 0 ? array_shift($lines) : '';

            $items[] = [
                'header' => $itemHeader,
                'payload' => $this->decodePayload($itemHeader, $payloadLine),
            ];
        }

        return ['header' => $header, 'items' => $items];
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJsonObject(string $line, string $context): array
    {
        $decoded = json_decode($line, true);

        if (! is_array($decoded)) {
            throw new InvalidEnvelopeException("Invalid JSON in {$context}");
        }

        return $decoded;
    }

    /**
     * @param  array<string, mixed>  $itemHeader
     * @return array<string, mixed>|string
     */
    private function decodePayload(array $itemHeader, string $payloadLine): array|string
    {
        $contentType = strtolower((string) ($itemHeader['content_type'] ?? 'application/json'));

        if (str_contains($contentType, 'json') || $contentType === '') {
            $decoded = json_decode($payloadLine, true);

            return is_array($decoded) ? $decoded : [];
        }

        return $payloadLine;
    }
}
