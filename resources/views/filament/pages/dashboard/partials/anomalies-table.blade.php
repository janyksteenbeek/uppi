<div class="pulse-table-wrap">
    <table class="pulse-table">
        <thead>
            <tr>
                <th>Status</th>
                <th>Type</th>
                <th>Monitor</th>
                <th>Address</th>
                <th>Started</th>
                <th>Duration</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @foreach ($rows as $row)
                <tr>
                    <td>
                        @if ($row['monitor_status_label'])
                            <x-filament::badge :color="$row['monitor_status_color']" size="sm">
                                {{ $row['monitor_status_label'] }}
                            </x-filament::badge>
                        @endif
                    </td>
                    <td class="pulse-td-mono">{{ $row['monitor_type'] }}</td>
                    <td class="pulse-td-strong">
                        @if ($row['monitor_url'])
                            <a href="{{ $row['monitor_url'] }}" class="pulse-link">{{ $row['monitor_name'] }}</a>
                        @else
                            {{ $row['monitor_name'] }}
                        @endif
                    </td>
                    <td class="pulse-td-mono">
                        {{ $row['monitor_address'] }}
                        @if ($row['monitor_port'])
                            <div class="pulse-td-sub">{{ $row['monitor_port'] }}</div>
                        @endif
                    </td>
                    <td class="pulse-td-mono">{{ $row['started_at'] }}</td>
                    <td class="pulse-td-mono">{{ $row['duration'] }}</td>
                    <td class="pulse-td-action">
                        <a href="{{ $row['view_url'] }}" class="pulse-link">View</a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
