<?php

namespace App\Http\Controllers\Api;

use App\Jobs\ErrorTracking\ProcessSentryEnvelopeJob;
use App\Models\ErrorTracking\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;

class SentryIngestController
{
    public function envelope(Request $request, string $projectId): JsonResponse|Response
    {
        return $this->ingest($request, $projectId, 'envelope');
    }

    public function store(Request $request, string $projectId): JsonResponse|Response
    {
        return $this->ingest($request, $projectId, 'store');
    }

    public function security(Request $request, string $projectId): Response
    {
        return response()->noContent();
    }

    private function ingest(Request $request, string $projectId, string $kind): JsonResponse|Response
    {
        if (! ctype_digit($projectId)) {
            return response()->json(['error' => 'Invalid project id'], 404);
        }

        /** @var Project|null $project */
        $project = Project::query()->withoutGlobalScope('user')
            ->where('internal_id', (int) $projectId)
            ->where('is_active', true)
            ->first();

        if (! $project) {
            return response()->json(['error' => 'Project not found'], 404);
        }

        $body = $this->decompress($request);

        if ($body === null) {
            return response()->json(['error' => 'Unsupported content encoding'], 415);
        }

        $maxBytes = ((int) config('error-tracking.max_event_size_kb', 1024)) * 1024;

        if (strlen($body) > $maxBytes) {
            return response()->json(['error' => 'Payload too large'], 413);
        }

        $publicKey = $this->extractPublicKey($request, $body);

        if ($publicKey === null || ! hash_equals($project->public_key, $publicKey)) {
            return response()->json(['error' => 'Invalid public key'], 401);
        }

        if ($kind === 'store') {
            $body = $this->wrapStorePayload($body, $project);
        }

        $eventId = $this->extractEventId($body) ?? Str::lower(Str::random(32));

        ProcessSentryEnvelopeJob::dispatch($project->id, $body)
            ->onQueue(config('error-tracking.ingest_queue', 'errors'));

        return response()->json(['id' => $eventId]);
    }

    private function decompress(Request $request): ?string
    {
        $body = $request->getContent();
        $encoding = strtolower((string) $request->header('Content-Encoding', ''));

        if ($encoding === '' || $encoding === 'identity') {
            return $body;
        }

        if ($encoding === 'gzip') {
            $decoded = @gzdecode($body);

            return $decoded === false ? null : $decoded;
        }

        if ($encoding === 'deflate') {
            $decoded = @gzinflate($body);

            if ($decoded !== false) {
                return $decoded;
            }

            $decoded = @gzuncompress($body);

            return $decoded === false ? null : $decoded;
        }

        if ($encoding === 'br') {
            if (! function_exists('brotli_uncompress')) {
                return null;
            }

            $decoded = @brotli_uncompress($body);

            return $decoded === false ? null : $decoded;
        }

        return null;
    }

    private function extractPublicKey(Request $request, string $body): ?string
    {
        $authHeader = $request->header('X-Sentry-Auth');

        if (is_string($authHeader) && $authHeader !== '') {
            if (preg_match('/sentry_key\s*=\s*([A-Za-z0-9]+)/i', $authHeader, $matches) === 1) {
                return $matches[1];
            }
        }

        $newlinePos = strpos($body, "\n");

        if ($newlinePos === false) {
            return null;
        }

        $headerLine = substr($body, 0, $newlinePos);
        $decoded = json_decode($headerLine, true);

        if (! is_array($decoded)) {
            return null;
        }

        $dsn = $decoded['dsn'] ?? null;

        if (is_string($dsn) && $dsn !== '') {
            $parsed = parse_url($dsn);
            if (isset($parsed['user']) && is_string($parsed['user'])) {
                return $parsed['user'];
            }
        }

        return null;
    }

    private function extractEventId(string $body): ?string
    {
        $newlinePos = strpos($body, "\n");

        if ($newlinePos === false) {
            return null;
        }

        $headerLine = substr($body, 0, $newlinePos);
        $decoded = json_decode($headerLine, true);

        if (! is_array($decoded)) {
            return null;
        }

        $eventId = $decoded['event_id'] ?? null;

        return is_string($eventId) ? strtolower(str_replace('-', '', $eventId)) : null;
    }

    private function wrapStorePayload(string $body, Project $project): string
    {
        $event = json_decode($body, true);
        $eventId = is_array($event) && isset($event['event_id']) ? (string) $event['event_id'] : Str::lower(Str::random(32));

        $envelopeHeader = json_encode([
            'event_id' => $eventId,
            'dsn' => $project->dsn,
            'sent_at' => now()->toIso8601String(),
        ]);

        $itemHeader = json_encode([
            'type' => 'event',
            'content_type' => 'application/json',
            'length' => strlen($body),
        ]);

        return $envelopeHeader."\n".$itemHeader."\n".$body;
    }
}
