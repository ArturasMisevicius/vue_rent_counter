<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('reports.exports.executive_summary.title') }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 12px;
            line-height: 1.4;
            color: #333;
            margin: 0;
            padding: 20px;
        }
        
        .header {
            text-align: center;
            border-bottom: 2px solid #3b82f6;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        
        .header h1 {
            color: #1e40af;
            font-size: 24px;
            margin: 0;
        }
        
        .header .subtitle {
            color: #6b7280;
            font-size: 14px;
            margin-top: 5px;
        }
        
        .metrics-grid {
            display: table;
            width: 100%;
            margin-bottom: 30px;
        }
        
        .metric-card {
            display: table-cell;
            width: 33.33%;
            padding: 15px;
            margin: 10px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            text-align: center;
        }
        
        .metric-card h3 {
            color: #1e40af;
            font-size: 14px;
            margin: 0 0 10px 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .metric-value {
            font-size: 28px;
            font-weight: bold;
            color: #059669;
            margin: 5px 0;
        }
        
        .metric-label {
            color: #6b7280;
            font-size: 11px;
        }
        
        .section {
            margin-bottom: 30px;
        }
        
        .section h2 {
            color: #1e40af;
            font-size: 16px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 5px;
            margin-bottom: 15px;
        }
        
        .distribution-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .distribution-table th,
        .distribution-table td {
            padding: 8px 12px;
            text-align: left;
            border-bottom: 1px solid #e2e8f0;
        }
        
        .distribution-table th {
            background: #f1f5f9;
            color: #374151;
            font-weight: bold;
        }
        
        .activity-item {
            padding: 8px 0;
            border-bottom: 1px solid #f3f4f6;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-time {
            color: #6b7280;
            font-size: 10px;
        }
        
        .activity-action {
            font-weight: bold;
            color: #1f2937;
        }
        
        .activity-user {
            color: #3b82f6;
        }
        
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
            text-align: center;
            color: #6b7280;
            font-size: 10px;
        }
        
        .page-break {
            page-break-before: always;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ __('reports.exports.executive_summary.title') }}</h1>
        <div class="subtitle">
            {{ __('reports.exports.executive_summary.subtitle', ['date' => $generated_at->locale(app()->getLocale())->translatedFormat('F j, Y H:i'), 'period' => $period]) }}
        </div>
    </div>

    <!-- Key Metrics -->
    <div class="metrics-grid">
        <div class="metric-card">
            <h3>{{ __('reports.exports.common.organizations') }}</h3>
            <div class="metric-value">{{ number_format($metrics['organizations']['total']) }}</div>
            <div class="metric-label">{{ __('reports.exports.executive_summary.total_organizations') }}</div>
            <div style="margin-top: 10px; font-size: 11px;">
                <div>{{ __('reports.exports.executive_summary.active') }}: {{ number_format($metrics['organizations']['active']) }}</div>
                <div>{{ __('reports.exports.executive_summary.suspended') }}: {{ number_format($metrics['organizations']['suspended']) }}</div>
                <div>{{ __('reports.exports.executive_summary.growth_30d') }}: +{{ number_format($metrics['organizations']['growth']) }}</div>
            </div>
        </div>
        
        <div class="metric-card">
            <h3>{{ __('reports.exports.common.subscriptions') }}</h3>
            <div class="metric-value">{{ number_format($metrics['subscriptions']['total']) }}</div>
            <div class="metric-label">{{ __('reports.exports.executive_summary.total_subscriptions') }}</div>
            <div style="margin-top: 10px; font-size: 11px;">
                <div>{{ __('reports.exports.executive_summary.active') }}: {{ number_format($metrics['subscriptions']['active']) }}</div>
                <div>{{ __('reports.exports.executive_summary.expiring_soon') }}: {{ number_format($metrics['subscriptions']['expiring']) }}</div>
                <div>{{ __('reports.exports.executive_summary.growth_30d') }}: +{{ number_format($metrics['subscriptions']['growth']) }}</div>
            </div>
        </div>
        
        <div class="metric-card">
            <h3>{{ __('reports.exports.executive_summary.platform_usage') }}</h3>
            <div class="metric-value">{{ number_format($metrics['platform']['users']) }}</div>
            <div class="metric-label">{{ __('reports.exports.executive_summary.total_users') }}</div>
            <div style="margin-top: 10px; font-size: 11px;">
                <div>{{ __('reports.exports.common.properties') }}: {{ number_format($metrics['platform']['properties']) }}</div>
                <div>{{ __('reports.exports.common.invoices') }}: {{ number_format($metrics['platform']['invoices']) }}</div>
            </div>
        </div>
    </div>

    <!-- Plan Distribution -->
    <div class="section">
        <h2>{{ __('reports.exports.executive_summary.subscription_plan_distribution') }}</h2>
        <table class="distribution-table">
            <thead>
                <tr>
                    <th>{{ __('reports.exports.common.plan_type') }}</th>
                    <th>{{ __('reports.exports.common.organizations') }}</th>
                    <th>{{ __('reports.exports.common.percentage') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($plan_distribution as $plan => $count)
                <tr>
                    <td>{{ enum_label($plan, \App\Enums\SubscriptionPlanType::class) }}</td>
                    <td>{{ number_format($count) }}</td>
                    <td>{{ number_format(($count / $metrics['organizations']['total']) * 100, 1) }}%</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Recent Activity -->
    <div class="section">
        <h2>{{ __('reports.exports.executive_summary.recent_platform_activity') }}</h2>
        @foreach($recent_activity as $activity)
        <div class="activity-item">
            <div class="activity-time">{{ $activity->created_at->locale(app()->getLocale())->translatedFormat('M j, Y H:i') }}</div>
            <div class="activity-action">{{ $activity->action }}</div>
            <div>
                <span class="activity-user">{{ $activity->user?->name ?? __('reports.exports.common.system') }}</span>
                @if($activity->organization)
                    {{ __('reports.exports.executive_summary.in') }} <strong>{{ $activity->organization->name }}</strong>
                @endif
                @if($activity->resource_type)
                    - {{ $activity->resource_type }}
                    @if($activity->resource_id)
                        #{{ $activity->resource_id }}
                    @endif
                @endif
            </div>
        </div>
        @endforeach
    </div>

    <div class="footer">
        <div>{{ __('reports.exports.executive_summary.footer_title') }}</div>
        <div>{{ __('reports.exports.common.confidential_notice') }}</div>
    </div>
</body>
</html>
