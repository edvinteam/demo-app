<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap');

        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            color: #1e293b;
            background: #ffffff;
            font-size: 13px;
            line-height: 1.5;
        }

        .header {
            background: linear-gradient(135deg, #0f172a 0%, #1e293b 50%, #334155 100%);
            color: white;
            padding: 40px 48px;
            margin-bottom: 32px;
        }

        .header h1 {
            font-size: 28px;
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        .header .subtitle {
            color: #94a3b8;
            font-size: 14px;
            margin-top: 4px;
        }

        .header .date {
            color: #64748b;
            font-size: 12px;
            margin-top: 12px;
        }

        .stats {
            display: flex;
            gap: 16px;
            padding: 0 48px;
            margin-bottom: 32px;
        }

        .stat-card {
            flex: 1;
            border-radius: 12px;
            padding: 20px 24px;
            border: 1px solid #e2e8f0;
        }

        .stat-card .number {
            font-size: 32px;
            font-weight: 700;
            line-height: 1;
        }

        .stat-card .label {
            font-size: 12px;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 4px;
        }

        .section {
            padding: 0 48px;
            margin-bottom: 28px;
        }

        .section-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 16px;
            padding-bottom: 10px;
            border-bottom: 2px solid #f1f5f9;
        }

        .section-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
        }

        .section-title {
            font-size: 16px;
            font-weight: 600;
        }

        .section-count {
            font-size: 12px;
            color: #94a3b8;
            font-weight: 500;
        }

        .task-list {
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .task-card {
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            padding: 16px 20px;
            background: #ffffff;
            page-break-inside: avoid;
        }

        .task-title {
            font-weight: 600;
            font-size: 14px;
            color: #0f172a;
        }

        .task-description {
            color: #64748b;
            font-size: 12px;
            margin-top: 4px;
        }

        .task-meta {
            margin-top: 8px;
            font-size: 11px;
            color: #94a3b8;
        }

        .empty {
            text-align: center;
            padding: 24px;
            color: #94a3b8;
            font-style: italic;
            font-size: 13px;
        }

        .footer {
            margin-top: 40px;
            padding: 24px 48px;
            border-top: 1px solid #e2e8f0;
            text-align: center;
            color: #94a3b8;
            font-size: 11px;
        }
    </style>
</head>
<body>

<div class="header">
    <h1>Task Board Report</h1>
    <div class="subtitle">Project status overview</div>
    <div class="date">Generated {{ now()->format('F j, Y \a\t g:i A') }}</div>
</div>

<div class="stats">
    @foreach ($columns as $status => $col)
        <div class="stat-card" style="background: {{ $col['bg'] }};">
            <div class="number" style="color: {{ $col['color'] }};">{{ $tasks->where('status', $status)->count() }}</div>
            <div class="label" style="color: {{ $col['color'] }};">{{ $col['label'] }}</div>
        </div>
    @endforeach
    <div class="stat-card" style="background: #f8fafc;">
        <div class="number" style="color: #475569;">{{ $tasks->count() }}</div>
        <div class="label" style="color: #475569;">Total</div>
    </div>
</div>

@foreach ($columns as $status => $col)
    <div class="section">
        <div class="section-header">
            <div class="section-dot" style="background: {{ $col['color'] }};"></div>
            <span class="section-title">{{ $col['label'] }}</span>
            <span class="section-count">{{ $tasks->where('status', $status)->count() }} tasks</span>
        </div>

        @php $statusTasks = $tasks->where('status', $status); @endphp

        @if ($statusTasks->isEmpty())
            <div class="empty">No tasks</div>
        @else
            <div class="task-list">
                @foreach ($statusTasks as $task)
                    <div class="task-card" style="border-left: 3px solid {{ $col['color'] }};">
                        <div class="task-title">{{ $task->title }}</div>
                        @if ($task->description)
                            <div class="task-description">{{ $task->description }}</div>
                        @endif
                        <div class="task-meta">Created {{ $task->created_at->format('M j, Y \a\t g:i A') }}</div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
@endforeach

<div class="footer">
    Task Board &mdash; Generated with Gotenberg PDF engine
</div>

</body>
</html>
