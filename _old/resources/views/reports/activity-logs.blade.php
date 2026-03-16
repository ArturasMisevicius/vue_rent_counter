<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('reports.exports.activity_logs.title') }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10px;
            line-height: 1.3;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        
        .header {
            text-align: center;
            border-bottom: 2px solid #3b82f6;
            padding-bottom: 15px;
            margin-bottom: 25px;
        }
        
        .header h1 {
            color: #1e40af;
            font-size: 20px;
            margin: 0;
        }
        
        .period-info {
            background: #f8fafc;
            padding: 10px;
            border: 1px solid #e2e8f0;
            border-radius: 5px;
            margin-bottom: 20px;
            text-align: center;
        }
        
        .summary-stats {
            display: table;
            width: 100%;
            margin-bottom: 25px;
        }
        
        .stat-item {
            display: table-cell;
            width: 25%;
            text-align: center;
            padding: 10px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
        }
        
        .stat-value {
            font-size: 18px;
            font-weight: bold;
            color: #059669;
        }
        
        .stat-label {
            color: #6b7280;
            font-size: 9px;
            margin-top: 3px;
        }
        
        .logs-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
            font-size: 8px;
        }
        
        .logs-table th,
        .logs-table td {
            padding: 4px 6px;
            text-align: left;
            border: 1px solid #e2e8f0;
            vertical-align: top;
        }
        
        .logs-table th {
            background: #f1f5f9;
            color: #374151;
            font-weight: bold;
        }
        
        .action-create { color: #059669; }
        .action-update { color: #3b82f6; }
        .action-delete { color: #dc2626; }
        .action-login { color: #8b5cf6; }
        .action-logout { color: #6b7280; }
        
        .distribution-section {
            margin-bottom: 20px;
            page-break-inside: avoid;
        }
        
        .distribution-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 9px;
        }
        
        .distribution-table th,
        .distribution-table td {
            padding: 4px 8px;
            border: 1px solid #e2e8f0;
            text-align: left;
        }
        
        .distribution-table th {
            background: #f1f5f9;
            font-weight: bold;
        }
        
        .two-column {
            display: table;
            width: 100%;
        }
        
        .column {
            display: table-cell;
            width: 48%;
            vertical-align: top;
            padding-right: 2%;
        }
        
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #e2e8f0;
            text-align: center;
            color: #6b7280;
            font-size: 8px;
        }
        
        .metadata-cell {
            max-width: 200px;
            word-wrap: break-word;
            font-size: 7px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ __('reports.exports.activity_logs.title') }}</h1>
        <div>{{ __('reports.exports.generated_on', ['date' => $generated_at->locale(app()->getLocale())->translatedFormat('F j, Y H:i')]) }}</div>
    </div>

    <div class="period-info">
        <strong>{{ __('reports.exports.activity_logs.report_period') }}:</strong> {{ $period['start'] }} {{ __('reports.exports.common.to') }} {{ $period['end'] }}
        <br>
        <strong>{{ __('reports.exports.activity_logs.total_activities') }}:</strong> {{ number_format($total_count) }}
        @if($total_count >= 1000)
            ({{ __('reports.exports.activity_logs.showing_first_records', ['count' => number_format(1000)]) }})
        @endif
    </div>

    <!-- Summary Statistics -->
    <div class="summary-stats">
        <div class="stat-item">
            <div class="stat-value">{{ number_format($total_count) }}</div>
            <div class="stat-label">{{ __('reports.exports.activity_logs.total_activities') }}</div>
        </div>
        <div class="stat-item">
            <div class="stat-value">{{ count($action_distribution) }}</div>
            <div class="stat-label">{{ __('reports.exports.activity_logs.unique_actions') }}</div>
        </div>
        <div class="stat-item">
            <div class="stat-value">{{ count($top_users) }}</div>
            <div class="stat-label">{{ __('reports.exports.activity_logs.active_users') }}</div>
        </div>
        <div class="stat-item">
            <div class="stat-value">{{ count($top_organizations) }}</div>
            <div class="stat-label">{{ __('reports.exports.common.organizations') }}</div>
        </div>
    </div>

    <!-- Activity Logs Table -->
    <table class="logs-table">
        <thead>
            <tr>
                <th>{{ __('reports.exports.common.timestamp') }}</th>
                <th>{{ __('reports.exports.common.organization') }}</th>
                <th>{{ __('reports.exports.common.user') }}</th>
                <th>{{ __('reports.exports.common.action') }}</th>
                <th>{{ __('reports.exports.common.resource') }}</th>
                <th>{{ __('reports.exports.common.ip_address') }}</th>
                <th>{{ __('reports.exports.common.metadata') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($logs as $log)
            <tr>
                <td>{{ $log->created_at->format('m/d H:i') }}</td>
                <td>{{ $log->organization?->name ?? __('reports.exports.common.not_available') }}</td>
                <td>{{ $log->user?->name ?? __('reports.exports.common.system') }}</td>
                <td class="action-{{ strtolower(explode('_', $log->action)[0] ?? 'other') }}">
                    {{ $log->action }}
                </td>
                <td>
                    {{ $log->resource_type }}
                    @if($log->resource_id)
                        #{{ $log->resource_id }}
                    @endif
                </td>
                <td>{{ $log->ip_address }}</td>
                <td class="metadata-cell">
                    @if($log->metadata && is_array($log->metadata))
                        {{ json_encode($log->metadata, JSON_UNESCAPED_SLASHES) }}
                    @endif
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Distribution Analysis -->
    <div class="two-column">
        <div class="column">
            <div class="distribution-section">
                <h3 style="color: #1e40af; font-size: 12px; margin-bottom: 8px;">{{ __('reports.exports.activity_logs.top_actions') }}</h3>
                <table class="distribution-table">
                    <thead>
                        <tr>
                            <th>{{ __('reports.exports.common.action') }}</th>
                            <th>{{ __('reports.exports.common.count') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($action_distribution->take(10) as $action => $count)
                        <tr>
                            <td>{{ $action }}</td>
                            <td>{{ number_format($count) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="distribution-section">
                <h3 style="color: #1e40af; font-size: 12px; margin-bottom: 8px;">{{ __('reports.exports.activity_logs.top_users') }}</h3>
                <table class="distribution-table">
                    <thead>
                        <tr>
                            <th>{{ __('reports.exports.common.user') }}</th>
                            <th>{{ __('reports.exports.activity_logs.activities') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($top_users->take(8) as $user => $count)
                        <tr>
                            <td>{{ $user ?: __('reports.exports.common.system') }}</td>
                            <td>{{ number_format($count) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <div class="column">
            <div class="distribution-section">
                <h3 style="color: #1e40af; font-size: 12px; margin-bottom: 8px;">{{ __('reports.exports.activity_logs.top_organizations') }}</h3>
                <table class="distribution-table">
                    <thead>
                        <tr>
                            <th>{{ __('reports.exports.common.organization') }}</th>
                            <th>{{ __('reports.exports.activity_logs.activities') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($top_organizations->take(8) as $org => $count)
                        <tr>
                            <td>{{ $org ?: __('reports.exports.common.system') }}</td>
                            <td>{{ number_format($count) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            @if(count($daily_activity) > 0)
            <div class="distribution-section">
                <h3 style="color: #1e40af; font-size: 12px; margin-bottom: 8px;">{{ __('reports.exports.activity_logs.daily_activity_last_days', ['count' => 7]) }}</h3>
                <table class="distribution-table">
                    <thead>
                        <tr>
                            <th>{{ __('reports.exports.common.date') }}</th>
                            <th>{{ __('reports.exports.activity_logs.activities') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach(array_slice($daily_activity, -7, 7, true) as $date => $count)
                        <tr>
                            <td>{{ \Carbon\Carbon::parse($date)->locale(app()->getLocale())->translatedFormat('M j') }}</td>
                            <td>{{ number_format($count) }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @endif
        </div>
    </div>

    <div class="footer">
        <div>{{ __('reports.exports.activity_logs.footer_title') }}</div>
        <div>{{ __('reports.exports.common.confidential_notice') }}</div>
    </div>
</body>
</html>
