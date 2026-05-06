@php
    /** @var \App\Models\ErrorTracking\Issue $record */
    $event = $record->latestEvent ?? $record->events()->latest('occurred_at')->first();
    $project = $record->project;
    $platform = $event?->platform ?? $record->platform ?? 'php';
    $stacktrace = $event?->stacktrace ?? null;
    $exception = $event?->exception ?? null;
    $frames = is_array($stacktrace) ? ($stacktrace['frames'] ?? []) : [];

    $exceptionType = $exception['values'][0]['type'] ?? null;
    $exceptionValue = $exception['values'][0]['value'] ?? $event?->message;
    $isHandled = (bool) ($exception['values'][0]['mechanism']['handled'] ?? true);

    $contexts = $event?->contexts ?? [];
    $runtime = $contexts['runtime'] ?? null;
    $os = $contexts['os'] ?? null;
    $browser = $contexts['browser'] ?? null;
    $appCtx = $contexts['app'] ?? null;

    $sdk = $event?->sdk;
    $sdkLabel = is_array($sdk) ? ($sdk['name'] ?? null) : null;

    $frameworkBadges = collect();
    if ($runtime && ($runtime['name'] ?? null)) {
        $frameworkBadges->push([
            'label' => strtoupper($runtime['name']),
            'value' => $runtime['version'] ?? null,
        ]);
    }
    if ($appCtx && ($appCtx['app_name'] ?? null) && ! str_contains(strtolower($appCtx['app_name']), 'php')) {
        $frameworkBadges->push([
            'label' => $appCtx['app_name'],
            'value' => $appCtx['app_version'] ?? null,
        ]);
    }
    if ($sdkLabel === 'sentry.laravel' || ($appCtx['laravel_version'] ?? null)) {
        $frameworkBadges->push([
            'label' => 'LARAVEL',
            'value' => $appCtx['laravel_version'] ?? null,
        ]);
    }

    $entrypointFrame = null;
    foreach (array_reverse($frames) as $f) {
        if (($f['in_app'] ?? null) === true) {
            $entrypointFrame = $f;
            break;
        }
    }
    if (! $entrypointFrame && $frames !== []) {
        $entrypointFrame = $frames[0];
    }

    $vendorFrameCount = count(array_filter($frames, fn ($f) => empty($f['in_app'])));
    $stackRenderer = app(\App\Services\ErrorTracking\StackTrace\StackTraceRendererManager::class);

    $now = now();
    $firstSeen = $record->first_seen_at ?? $now->copy()->subDay();
    $spanMinutes = max(1, (int) $firstSeen->diffInMinutes($now));

    if ($spanMinutes <= 60) {
        $bucketCount = 30;
        $bucketMinutes = 2;
        $rangeLabel = 'last hour';
        $axisFormat = 'H:i';
    } elseif ($spanMinutes <= 60 * 24) {
        $bucketCount = 24;
        $bucketMinutes = 60;
        $rangeLabel = 'last 24h';
        $axisFormat = 'H:00';
    } elseif ($spanMinutes <= 60 * 24 * 7) {
        $bucketCount = 14;
        $bucketMinutes = 60 * 12;
        $rangeLabel = 'last 7d';
        $axisFormat = 'M j';
    } else {
        $bucketCount = 30;
        $bucketMinutes = 60 * 24;
        $rangeLabel = 'last 30d';
        $axisFormat = 'M j';
    }

    $chartSince = $now->copy()->subMinutes($bucketCount * $bucketMinutes);

    $eventsForChart = \App\Models\ErrorTracking\Event::query()
        ->withoutGlobalScope('user')
        ->where('issue_id', $record->id)
        ->where('occurred_at', '>=', $chartSince)
        ->get(['occurred_at', 'environment']);

    $byBucket = array_fill(0, $bucketCount, 0);
    $byEnv = [];
    foreach ($eventsForChart as $e) {
        $idx = (int) floor($chartSince->diffInRealMinutes($e->occurred_at) / $bucketMinutes);
        if ($idx >= 0 && $idx < $bucketCount) {
            $byBucket[$idx]++;
        }
        $env = $e->environment ?: 'unknown';
        $byEnv[$env] = ($byEnv[$env] ?? 0) + 1;
    }
    $maxBucket = max($byBucket) ?: 1;
    $totalEvents = array_sum($byBucket);

    $tags = is_array($event?->tags) ? $event->tags : [];

    $tagsRollup = collect($tags)
        ->map(fn ($v) => is_scalar($v) ? (string) $v : json_encode($v))
        ->take(8);

    $highlights = array_filter([
        'handled' => $isHandled ? 'yes' : 'no',
        'level' => $event?->level?->value,
        'release' => $event?->release,
        'environment' => $event?->environment,
        'transaction' => $event?->transaction,
        'server_name' => $event?->server_name,
        'browser' => $browser ? (($browser['name'] ?? '').' '.($browser['version'] ?? '')) : null,
        'os' => $os ? (($os['name'] ?? '').' '.($os['version'] ?? '')) : null,
        'runtime' => $runtime ? (($runtime['name'] ?? '').' '.($runtime['version'] ?? '')) : null,
    ], fn ($v) => $v !== null && $v !== '' && trim((string) $v) !== '');

    $request = $event?->request;
    $breadcrumbs = $event?->breadcrumbs;
    $userContext = $event?->user_context;

    $statusCfg = match ($record->status?->value) {
        'open'     => ['Open',     'var(--pulse-red)'],
        'resolved' => ['Resolved', 'var(--pulse-green)'],
        'ignored'  => ['Ignored',  'var(--pulse-muted)'],
        default    => ['Unknown',  'var(--pulse-muted)'],
    };
@endphp

<div class="pulse-issue-detail">
    {{-- MAIN COLUMN --}}
    <div class="pulse-issue-main">
        {{-- Exception summary card --}}
        <section class="pulse-card">
            <header class="pulse-card-head">
                <span class="pulse-issue-tag {{ $isHandled ? 'pulse-issue-tag-mute' : 'pulse-issue-tag-bad' }}">
                    {{ $isHandled ? 'Handled' : 'Unhandled' }}
                </span>
                <div class="pulse-card-head-end">
                    @foreach ($frameworkBadges as $badge)
                        <span class="pulse-issue-chip">
                            <b>{{ $badge['label'] }}</b>
                            @if ($badge['value']) <span>{{ $badge['value'] }}</span>@endif
                        </span>
                    @endforeach
                </div>
            </header>
            <div class="pulse-card-body">
                <div class="pulse-issue-display">{{ $exceptionType ?: 'Error' }}</div>
                @if ($exceptionValue)
                    <p class="pulse-issue-message">{{ $exceptionValue }}</p>
                @endif

                @if ($vendorFrameCount > 0 || $entrypointFrame)
                    <div class="pulse-frame-list">
                        @if ($vendorFrameCount > 0)
                            <div class="pulse-frame-row pulse-frame-row-mute">
                                <span>{{ $vendorFrameCount }} vendor frame{{ $vendorFrameCount === 1 ? '' : 's' }}</span>
                                <a href="#stacktrace" class="pulse-link">View stack trace</a>
                            </div>
                        @endif
                        @if ($entrypointFrame)
                            @php
                                $epFunction = $entrypointFrame['function'] ?? '';
                                $epFile = $entrypointFrame['filename'] ?? $entrypointFrame['module'] ?? '';
                                foreach (['/app/', '/vendor/', '/bootstrap/', '/routes/'] as $marker) {
                                    $pos = strpos($epFile, $marker);
                                    if ($pos !== false) { $epFile = ltrim(substr($epFile, $pos), '/'); break; }
                                }
                                $epLine = $entrypointFrame['lineno'] ?? null;
                            @endphp
                            <div class="pulse-frame-row">
                                <span class="pulse-frame-row-left">
                                    <span class="pulse-issue-status" style="background: var(--pulse-red); margin: 0;"></span>
                                    <span class="pulse-frame-row-lbl">entrypoint</span>
                                    @if ($epFunction)
                                        <span class="pulse-frame-row-fn">{{ $epFunction }}</span>
                                    @endif
                                </span>
                                <span class="pulse-frame-row-path">{{ $epFile }}@if ($epLine):{{ $epLine }}@endif</span>
                            </div>
                        @endif
                    </div>
                @endif
            </div>
        </section>

        {{-- Stacktrace --}}
        <section id="stacktrace" class="pulse-card">
            <header class="pulse-card-head">
                <h3 class="pulse-card-title">Stack trace</h3>
                <span class="pulse-card-eyebrow">{{ $platform }}</span>
            </header>
            <div class="pulse-card-body">
                @if (count($frames) === 0)
                    <p class="pulse-empty-lede" style="text-align:left;">No stacktrace was captured for the latest event.</p>
                @else
                    {!! $stackRenderer->resolveFor($platform)->render($frames, $exception)->render() !!}
                @endif
            </div>
        </section>

        {{-- Highlights --}}
        @if (count($highlights) > 0)
            <section class="pulse-card">
                <header class="pulse-card-head">
                    <h3 class="pulse-card-title">Highlights</h3>
                    @if ($event)
                        <span class="pulse-card-meta">event #{{ Str::limit($event->event_id, 8, '') }}</span>
                    @endif
                </header>
                <dl class="pulse-kv-grid">
                    @foreach ($highlights as $key => $value)
                        <div class="pulse-kv">
                            <dt>{{ str_replace('_', ' ', $key) }}</dt>
                            <dd title="{{ $value }}">{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>
            </section>
        @endif

        {{-- Tags --}}
        @if (count($tagsRollup) > 0)
            <section class="pulse-card">
                <header class="pulse-card-head">
                    <h3 class="pulse-card-title">Tags</h3>
                </header>
                <div class="pulse-card-body pulse-tags-row">
                    @foreach ($tagsRollup as $key => $value)
                        <span class="pulse-issue-chip">
                            <b style="color: var(--pulse-muted); font-weight: 500;">{{ $key }}</b>
                            <span style="color: var(--pulse-ink);">{{ $value }}</span>
                        </span>
                    @endforeach
                </div>
            </section>
        @endif

        {{-- Breadcrumbs --}}
        @if ($breadcrumbs && ! empty($breadcrumbs['values'] ?? []))
            <details class="pulse-card pulse-details">
                <summary class="pulse-card-head pulse-card-head-toggle">
                    <h3 class="pulse-card-title">Breadcrumbs</h3>
                    <svg class="pulse-toggle-icon" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                </summary>
                <div class="pulse-card-body">
                    @include('filament.error-tracking.breadcrumbs', ['breadcrumbs' => $breadcrumbs])
                </div>
            </details>
        @endif

        {{-- Request --}}
        @if ($request)
            <details class="pulse-card pulse-details">
                <summary class="pulse-card-head pulse-card-head-toggle">
                    <h3 class="pulse-card-title">Request</h3>
                    <svg class="pulse-toggle-icon" width="14" height="14" fill="none" stroke="currentColor" stroke-width="1.6" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>
                </summary>
                <div class="pulse-card-body">
                    @include('filament.error-tracking.request', ['request' => $request])
                </div>
            </details>
        @endif
    </div>

    {{-- SIDEBAR --}}
    <aside class="pulse-issue-side">
        <section class="pulse-card">
            <header class="pulse-card-head"><h3 class="pulse-card-title">Manage</h3></header>
            <dl class="pulse-card-body pulse-kv-list">
                <div class="pulse-kv-row">
                    <dt>Status</dt>
                    <dd>
                        <span class="pulse-issue-chip">
                            <i class="dot" style="background: {{ $statusCfg[1] }};"></i>
                            {{ $statusCfg[0] }}
                        </span>
                    </dd>
                </div>
                <div class="pulse-kv-row">
                    <dt>Level</dt>
                    <dd class="pulse-mono-up">{{ $event?->level?->value ?? $record->level?->value }}</dd>
                </div>
                @if ($project)
                    <div class="pulse-kv-row">
                        <dt>Project</dt>
                        <dd>
                            <a href="{{ \App\Filament\Resources\ErrorProjectResource::getUrl('edit', ['record' => $project]) }}" class="pulse-link">{{ $project->name }}</a>
                        </dd>
                    </div>
                @endif
            </dl>
        </section>

        <section class="pulse-card">
            <header class="pulse-card-head"><h3 class="pulse-card-title">Details</h3></header>
            <dl class="pulse-card-body pulse-kv-list">
                <div class="pulse-kv-row">
                    <dt>First seen</dt>
                    <dd>{{ $record->first_seen_at?->diffForHumans() ?? '—' }}</dd>
                </div>
                <div class="pulse-kv-row">
                    <dt>Last seen</dt>
                    <dd>{{ $record->last_seen_at?->diffForHumans() ?? '—' }}</dd>
                </div>
                <div class="pulse-kv-row">
                    <dt>Events</dt>
                    <dd class="pulse-num">{{ number_format($record->times_seen) }}</dd>
                </div>
                <div class="pulse-kv-row">
                    <dt>Users</dt>
                    <dd class="pulse-num">{{ number_format($record->users_seen) }}</dd>
                </div>
            </dl>
        </section>

        <section class="pulse-card">
            <header class="pulse-card-head">
                <h3 class="pulse-card-title">Occurrences</h3>
                <span class="pulse-card-meta">{{ $totalEvents }} · {{ $rangeLabel }}</span>
            </header>
            <div class="pulse-card-body">
                <div class="pulse-bars" style="height: 56px; display: flex; align-items: flex-end; gap: 2px;">
                    @foreach ($byBucket as $idx => $count)
                        @php
                            $h = $count > 0 ? max(8, (int) round(($count / $maxBucket) * 100)) : 6;
                            $tickAt = $chartSince->copy()->addMinutes($idx * $bucketMinutes);
                        @endphp
                        <div class="pulse-bar-col" style="flex: 1 1 0; min-width: 3px; display: flex; align-items: flex-end; height: 100%;" title="{{ $tickAt->format($axisFormat) }} — {{ $count }} event{{ $count === 1 ? '' : 's' }}">
                            <div class="pulse-bar {{ $count > 0 ? 'pulse-bar-hot' : '' }}" style="height: {{ $h }}%; width: 100%; border-radius: 2px; background: {{ $count > 0 ? 'var(--pulse-red)' : 'var(--pulse-line)' }};"></div>
                        </div>
                    @endforeach
                </div>
                <div class="pulse-bars-axis">
                    <span>{{ $chartSince->format($axisFormat) }}</span>
                    <span>{{ $now->format($axisFormat) }}</span>
                </div>
            </div>
        </section>

        @if (count($byEnv) > 0)
            <section class="pulse-card">
                <header class="pulse-card-head">
                    <h3 class="pulse-card-title">Environments</h3>
                    <span class="pulse-card-meta">{{ $rangeLabel }}</span>
                </header>
                <ul class="pulse-card-body pulse-kv-list">
                    @foreach ($byEnv as $env => $count)
                        <li class="pulse-kv-row">
                            <span class="pulse-kv-with-dot">
                                <i class="dot" style="background: var(--pulse-red);"></i>
                                {{ $env }}
                            </span>
                            <span class="pulse-mono">{{ $count }}</span>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        @if (is_array($userContext) && count($userContext) > 0)
            <section class="pulse-card">
                <header class="pulse-card-head"><h3 class="pulse-card-title">User</h3></header>
                <dl class="pulse-card-body pulse-kv-list">
                    @foreach (['id', 'email', 'username', 'ip_address'] as $field)
                        @if (! empty($userContext[$field]))
                            <div class="pulse-kv-row">
                                <dt>{{ $field }}</dt>
                                <dd class="pulse-mono" title="{{ $userContext[$field] }}">{{ $userContext[$field] }}</dd>
                            </div>
                        @endif
                    @endforeach
                </dl>
            </section>
        @endif
    </aside>
</div>
