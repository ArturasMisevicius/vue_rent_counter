<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('reports.exports.subscriptions.title') }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11px;
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
            font-size: 22px;
            margin: 0;
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
            padding: 12px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
        }
        
        .stat-value {
            font-size: 20px;
            font-weight: bold;
            color: #059669;
        }
        
        .stat-label {
            color: #6b7280;
            font-size: 10px;
            margin-top: 5px;
        }
        
        .subscriptions-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
            font-size: 9px;
        }
        
        .subscriptions-table th,
        .subscriptions-table td {
            padding: 6px 8px;
            text-align: left;
            border: 1px solid #e2e8f0;
            vertical-align: top;
        }
        
        .subscriptions-table th {
            background: #f1f5f9;
            color: #374151;
            font-weight: bold;
        }
        
        .status-active { color: #059669; font-weight: bold; }
        .status-expired { color: #dc2626; font-weight: bold; }
        .status-suspended { color: #d97706; font-weight: bold; }
        .status-cancelled { color: #6b7280; font-weight: bold; }
        
        .plan-basic { color: #3b82f6; }
        .plan-professional { color: #8b5cf6; }
        .plan-enterprise { color: #f59e0b; }
        
        .expiry-critical { color: #dc2626; font-weight: bold; }
        .expiry-warning { color: #d97706; font-weight: bold; }
        .expiry-normal { color: #059669; }
        
        .distribution-section {
            margin-bottom: 20px;
        }
        
        .distribution-table {
            width: 60%;
            border-collapse: collapse;
            font-size: 10px;
        }
        
        .distribution-table th,
        .distribution-table td {
            padding: 5px 10px;
            border: 1px solid #e2e8f0;
            text-align: left;
        }
        
        .distribution-table th {
            background: #f1f5f9;
            font-weight: bold;
        }
        
        .footer {
            margin-top: 30px;
            padding-top: 15px;
            border-top: 1px solid #e2e8f0;
            text-align: center;
            color: #6b7280;
            font-size: 9px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ __('reports.exports.subscriptions.title') }}</h1>
        <div>{{ __('reports.exports.generated_on', ['date' => $generated_at->locale(app()->getLocale())->translatedFormat('F j, Y H:i')]) }}</div>
        <div>{{ __('reports.exports.subscriptions.total_subscriptions_line', ['count' => number_format($total_count)]) }}</div>
    </div>

    <!-- Summary Statistics -->
    <div class="summary-stats">
        <div class="stat-item">
            <div class="stat-value">{{ number_format($total_count) }}</div>
            <div class="stat-label">{{ __('reports.exports.subscriptions.total_subscriptions') }}</div>
        </div>
        <div class="stat-item">
            <div class="stat-value">{{ number_format($expiring_count) }}</div>
            <div class="stat-label">{{ __('reports.exports.subscriptions.expiring_soon_days', ['count' => 14]) }}</div>
        </div>
        <div class="stat-item">
            <div class="stat-value">{{ number_format($summary_stats['total_max_properties']) }}</div>
            <div class="stat-label">{{ __('reports.exports.subscriptions.total_property_capacity') }}</div>
        </div>
        <div class="stat-item">
            <div class="stat-value">{{ number_format($summary_stats['avg_days_until_expiry'], 0) }}</div>
            <div class="stat-label">{{ __('reports.exports.subscriptions.avg_days_until_expiry') }}</div>
        </div>
    </div>

    <!-- Subscriptions Table -->
    <table class="subscriptions-table">
        <thead>
            <tr>
                <th>{{ __('reports.exports.common.id') }}</th>
                <th>{{ __('reports.exports.common.organization') }}</th>
                <th>{{ __('reports.exports.common.user') }}</th>
                <th>{{ __('reports.exports.common.plan') }}</th>
                <th>{{ __('reports.exports.common.status') }}</th>
                <th>{{ __('reports.exports.common.starts') }}</th>
                <th>{{ __('reports.exports.common.expires') }}</th>
                <th>{{ __('reports.exports.common.days_left') }}</th>
                <th>{{ __('reports.exports.subscriptions.max_properties') }}</th>
                <th>{{ __('reports.exports.subscriptions.max_tenants') }}</th>
                <th>{{ __('reports.exports.subscriptions.active') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($subscriptions as $subscription)
            <tr>
                <td>{{ $subscription->id }}</td>
                <td>{{ $subscription->user?->organization?->name ?? __('reports.exports.common.not_available') }}</td>
                <td>{{ $subscription->user?->name ?? __('reports.exports.common.not_available') }}</td>
                <td class="plan-{{ strtolower($subscription->plan_type) }}">
                    {{ enum_label($subscription->plan_type, \App\Enums\SubscriptionPlanType::class) }}
                </td>
                <td class="status-{{ strtolower($subscription->status?->value ?? 'unknown') }}">
                    {{ enum_label($subscription->status?->value ?? 'unknown', \App\Enums\SubscriptionStatus::class) }}
                </td>
                <td>{{ $subscription->starts_at?->format('Y-m-d') ?? __('reports.exports.common.not_available') }}</td>
                <td>{{ $subscription->expires_at?->format('Y-m-d') ?? __('reports.exports.common.not_available') }}</td>
                <td>
                    @if($subscription->expires_at)
                        @if($subscription->daysUntilExpiry() < 0)
                            <span class="expiry-critical">{{ __('reports.exports.common.expired') }}</span>
                        @elseif($subscription->daysUntilExpiry() <= 7)
                            <span class="expiry-critical">{{ $subscription->daysUntilExpiry() }}</span>
                        @elseif($subscription->daysUntilExpiry() <= 14)
                            <span class="expiry-warning">{{ $subscription->daysUntilExpiry() }}</span>
                        @else
                            <span class="expiry-normal">{{ $subscription->daysUntilExpiry() }}</span>
                        @endif
                    @else
                        {{ __('reports.exports.common.not_available') }}
                    @endif
                </td>
                <td>{{ number_format($subscription->max_properties) }}</td>
                <td>{{ number_format($subscription->max_tenants) }}</td>
                <td>{{ $subscription->isActive() ? __('common.yes') : __('common.no') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Distribution Charts -->
    <div class="distribution-section">
        <h3 style="color: #1e40af; font-size: 14px; margin-bottom: 10px;">{{ __('reports.exports.subscriptions.status_distribution') }}</h3>
        <table class="distribution-table">
            <thead>
                <tr>
                    <th>{{ __('reports.exports.common.status') }}</th>
                    <th>{{ __('reports.exports.common.count') }}</th>
                    <th>{{ __('reports.exports.common.percentage') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($status_distribution as $status => $count)
                <tr>
                    <td>{{ enum_label($status, \App\Enums\SubscriptionStatus::class) }}</td>
                    <td>{{ number_format($count) }}</td>
                    <td>{{ number_format(($count / $total_count) * 100, 1) }}%</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="distribution-section">
        <h3 style="color: #1e40af; font-size: 14px; margin-bottom: 10px;">{{ __('reports.exports.subscriptions.plan_distribution') }}</h3>
        <table class="distribution-table">
            <thead>
                <tr>
                    <th>{{ __('reports.exports.common.plan_type') }}</th>
                    <th>{{ __('reports.exports.common.count') }}</th>
                    <th>{{ __('reports.exports.common.percentage') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($plan_distribution as $plan => $count)
                <tr>
                    <td>{{ enum_label($plan, \App\Enums\SubscriptionPlanType::class) }}</td>
                    <td>{{ number_format($count) }}</td>
                    <td>{{ number_format(($count / $total_count) * 100, 1) }}%</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="footer">
        <div>{{ __('reports.exports.subscriptions.footer_title') }}</div>
        <div>{{ __('reports.exports.common.confidential_notice') }}</div>
    </div>
</body>
</html>
