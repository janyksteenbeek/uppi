@php
    /** @var \App\Models\ErrorTracking\Project|null $record */
    $record ??= isset($getRecord) && is_callable($getRecord) ? $getRecord() : null;
    $dsn = $record?->dsn;
    $publicKey = $record?->public_key;
    $internalId = $record?->internal_id;
    $platform = $record?->platform ?? 'php';

    $snippets = match ($platform) {
        'laravel' => [
            ['label' => 'Install', 'lang' => 'bash', 'code' => 'composer require sentry/sentry-laravel'],
            ['label' => '.env', 'lang' => 'dotenv', 'code' => "SENTRY_LARAVEL_DSN={$dsn}"],
        ],
        'php' => [
            ['label' => 'Install', 'lang' => 'bash', 'code' => 'composer require sentry/sentry'],
            ['label' => 'Init', 'lang' => 'php', 'code' => "\\Sentry\\init(['dsn' => '{$dsn}']);"],
        ],
        'javascript', 'node' => [
            ['label' => 'Install', 'lang' => 'bash', 'code' => 'npm install --save @sentry/browser'],
            ['label' => 'Init', 'lang' => 'js', 'code' => "Sentry.init({ dsn: '{$dsn}' });"],
        ],
        'python' => [
            ['label' => 'Install', 'lang' => 'bash', 'code' => 'pip install --upgrade sentry-sdk'],
            ['label' => 'Init', 'lang' => 'python', 'code' => "import sentry_sdk\nsentry_sdk.init(dsn=\"{$dsn}\")"],
        ],
        default => [
            ['label' => 'DSN env var', 'lang' => 'dotenv', 'code' => "SENTRY_DSN={$dsn}"],
        ],
    };
@endphp

<div
    x-data="{
        copied: null,
        copy(key, value) {
            navigator.clipboard.writeText(value);
            this.copied = key;
            setTimeout(() => { if (this.copied === key) this.copied = null }, 1500);
        }
    }"
    class="space-y-4"
>
    <div>
        <div class="flex items-center justify-between gap-2">
            <h3 class="text-base font-semibold text-gray-950 dark:text-white">Client SDK setup</h3>
            <a
                href="https://docs.sentry.io/platforms/"
                target="_blank"
                rel="noreferrer"
                class="text-xs text-primary-600 hover:underline dark:text-primary-400"
            >
                Sentry SDK docs ↗
            </a>
        </div>
        <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
            Point any Sentry-compatible SDK at this DSN. No code changes — just swap the DSN in your existing setup.
        </p>
    </div>

    <div class="rounded-lg border border-gray-200 bg-gray-50 p-3 dark:border-white/10 dark:bg-white/5">
        <div class="flex items-center justify-between gap-2">
            <span class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">DSN</span>
            <button
                type="button"
                @click="copy('dsn', @js($dsn))"
                class="inline-flex items-center gap-1 rounded-md bg-primary-600 px-2 py-1 text-xs font-medium text-white hover:bg-primary-500 disabled:opacity-50"
                :disabled="copied === 'dsn'"
            >
                <template x-if="copied !== 'dsn'">
                    <span class="flex items-center gap-1">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
                        Copy
                    </span>
                </template>
                <template x-if="copied === 'dsn'">
                    <span class="flex items-center gap-1">
                        <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                        Copied
                    </span>
                </template>
            </button>
        </div>
        <code class="mt-2 block break-all rounded bg-gray-900 px-3 py-2 font-mono text-xs text-gray-100">{{ $dsn }}</code>
    </div>

    @foreach ($snippets as $i => $snippet)
        <div class="rounded-lg border border-gray-200 dark:border-white/10">
            <div class="flex items-center justify-between gap-2 border-b border-gray-200 bg-gray-50 px-3 py-2 dark:border-white/10 dark:bg-white/5">
                <span class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">
                    {{ $snippet['label'] }}
                </span>
                <button
                    type="button"
                    @click="copy('snippet-{{ $i }}', @js($snippet['code']))"
                    class="inline-flex items-center gap-1 rounded-md border border-gray-300 px-2 py-1 text-xs font-medium text-gray-700 hover:bg-white dark:border-white/20 dark:text-gray-200 dark:hover:bg-white/10"
                >
                    <template x-if="copied !== 'snippet-{{ $i }}'">
                        <span>Copy</span>
                    </template>
                    <template x-if="copied === 'snippet-{{ $i }}'">
                        <span>Copied</span>
                    </template>
                </button>
            </div>
            <pre class="m-0 overflow-x-auto bg-gray-900 px-3 py-2 font-mono text-xs leading-relaxed text-gray-100"><code>{{ $snippet['code'] }}</code></pre>
        </div>
    @endforeach

    <div class="grid gap-3 sm:grid-cols-2">
        <div class="rounded-lg border border-gray-200 px-3 py-2 dark:border-white/10">
            <div class="flex items-center justify-between gap-2">
                <span class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Public key</span>
                <button
                    type="button"
                    @click="copy('pk', @js($publicKey))"
                    class="text-xs text-primary-600 hover:underline dark:text-primary-400"
                >
                    <span x-text="copied === 'pk' ? 'Copied' : 'Copy'"></span>
                </button>
            </div>
            <code class="mt-1 block break-all font-mono text-xs text-gray-700 dark:text-gray-200">{{ $publicKey }}</code>
        </div>
        <div class="rounded-lg border border-gray-200 px-3 py-2 dark:border-white/10">
            <div class="flex items-center justify-between gap-2">
                <span class="text-xs font-semibold uppercase tracking-wide text-gray-500 dark:text-gray-400">Project ID</span>
                <button
                    type="button"
                    @click="copy('pid', @js((string) $internalId))"
                    class="text-xs text-primary-600 hover:underline dark:text-primary-400"
                >
                    <span x-text="copied === 'pid' ? 'Copied' : 'Copy'"></span>
                </button>
            </div>
            <code class="mt-1 block font-mono text-xs text-gray-700 dark:text-gray-200">{{ $internalId }}</code>
        </div>
    </div>
</div>
