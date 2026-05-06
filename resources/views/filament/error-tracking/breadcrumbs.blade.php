@php
    /** @var array|null $breadcrumbs */
    $values = $breadcrumbs['values'] ?? [];
    $values = is_array($values) ? array_reverse($values) : [];
@endphp

@if (count($values) === 0)
    <p class="text-sm text-gray-500 dark:text-gray-400">No breadcrumbs were captured for this event.</p>
@else
    <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-white/10">
        <table class="min-w-full divide-y divide-gray-100 dark:divide-white/10 text-sm">
            <thead class="bg-gray-50 dark:bg-white/5">
                <tr>
                    <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Time</th>
                    <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Type</th>
                    <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Category</th>
                    <th class="px-3 py-2 text-left font-medium text-gray-500 dark:text-gray-400">Message</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100 dark:divide-white/10">
                @foreach ($values as $crumb)
                    @php
                        $ts = $crumb['timestamp'] ?? null;
                        if (is_numeric($ts)) {
                            $ts = \Illuminate\Support\Carbon::createFromTimestamp((float) $ts)->format('H:i:s');
                        } elseif (is_string($ts) && $ts !== '') {
                            try {
                                $ts = \Illuminate\Support\Carbon::parse($ts)->format('H:i:s');
                            } catch (\Throwable) {
                                // keep as-is
                            }
                        } else {
                            $ts = '—';
                        }
                    @endphp
                    <tr>
                        <td class="px-3 py-1.5 font-mono text-xs text-gray-500 dark:text-gray-400">{{ $ts }}</td>
                        <td class="px-3 py-1.5">
                            <span class="inline-flex items-center rounded bg-gray-100 px-1.5 py-0.5 text-xs font-medium text-gray-700 dark:bg-white/10 dark:text-gray-200">
                                {{ $crumb['type'] ?? 'default' }}
                            </span>
                        </td>
                        <td class="px-3 py-1.5 font-mono text-xs">{{ $crumb['category'] ?? '' }}</td>
                        <td class="px-3 py-1.5 break-words text-gray-700 dark:text-gray-200">
                            {{ $crumb['message'] ?? '' }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
@endif
