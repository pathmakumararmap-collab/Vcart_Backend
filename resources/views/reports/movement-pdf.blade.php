<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $title }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1f2937; }
        .header { margin-bottom: 16px; }
        h1 { font-size: 18px; margin: 0 0 4px; }
        .muted { color: #6b7280; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #d1d5db; padding: 5px 7px; text-align: left; font-size: 10px; }
        th { background: #f3f4f6; }
        .text-right { text-align: right; }
        .badge { padding: 2px 6px; border-radius: 3px; font-size: 9px; }
        .badge-fast { background: #dcfce7; color: #166534; }
        .badge-slow { background: #fef9c3; color: #854d0e; }
        .badge-non_moving { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Royal SL E-commerce</h1>
        <h1>{{ $title }}</h1>
        <div class="muted">Period: {{ $report['range']['from'] }} to {{ $report['range']['to'] }}</div>
        @if ($report['group_by'])
            <div class="muted">Grouped by: {{ ucfirst($report['group_by']) }}</div>
        @endif
    </div>

    <table>
        <thead>
            <tr>
                @foreach ($headers as $header)
                    <th>{{ $header }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    @foreach ($row as $index => $value)
                        <td class="{{ is_numeric($value) ? 'text-right' : '' }}">{{ $value }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($headers) }}" class="muted">No data for this period.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
