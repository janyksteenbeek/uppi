<div class="pulse-table-wrap">
    <table class="pulse-table">
        <thead>
            <tr>
                <th>Status</th>
                <th>Test</th>
                <th>Steps</th>
                <th>Duration</th>
                <th>Started</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr>
                    <td>
                        @if ($row['status_label'])
                            <x-filament::badge :color="$row['status_color']" size="sm">
                                {{ $row['status_label'] }}
                            </x-filament::badge>
                        @endif
                    </td>
                    <td class="pulse-td-strong">
                        @if ($row['test_url'])
                            <a href="{{ $row['test_url'] }}" class="pulse-link">{{ $row['test_name'] }}</a>
                        @else
                            {{ $row['test_name'] }}
                        @endif
                    </td>
                    <td class="pulse-td-mono">{{ $row['steps'] }}</td>
                    <td class="pulse-td-mono">{{ $row['duration'] }}</td>
                    <td class="pulse-td-mono" title="{{ $row['started_at'] }}">{{ $row['started_at_human'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
