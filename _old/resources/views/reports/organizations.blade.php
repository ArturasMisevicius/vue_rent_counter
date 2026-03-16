<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ __('reports.exports.organizations.title') }}</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 10px;
            line-height: 1.3;
            color: #333;
            margin: 0;
            padding: 15px;
        }
        
        .header {
            text-align: center;
            border-bottom: 2px solid #3b82f6;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        
        .header h1 {
            color: #1e40af;
            font-size: 20px;
            margin: 0;
        }
        
        .summary-stats {
            display: table;
            width: 100%;
            margin-bottom: 20px;
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
        
        .organizations-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            font-size: 8px;
        }
        
        .organizations-table th,
        .organizations-table td {
            padding: 4px 6px;
            text-align: left;
            border: 1px solid #e2e8f0;
            vertical-align: top;
        }
        
        .organizations-table th {
            background: #f1f5f9;
            color: #374151;
            font-weight: bold;
            font-size: 8px;
        }
        
        .status-active { color: #059669; font-weight: bold; }
        .status-suspended { color: #dc2626; font-weight: bold; }
        .status-pending { color: #d97706; font-weight: bold; }
        .status-cancelled { color: #6b7280; font-weight: bold; }
        
        .plan-basic { color: #3b82f6; }
        .plan-professional { color: #8b5cf6; }
        .plan-enterprise { color: #f59e0b; }
        
        .footer {
            margin-top: 20px;
            padding-top: 10px;
            border-top: 1px solid #e2e8f0;
            text-align: center;
            color: #6b7280;
            font-size: 8px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>{{ __('reports.exports.organizations.title') }}</h1>
        <div>{{ __('reports.exports.generated_on', ['date' => $generated_at->locale(app()->getLocale())->translatedFormat('F j, Y H:i')]) }}</div>
        <div>{{ __('reports.exports.organizations.total_organizations_line', ['count' => number_format($total_count)]) }}</div>
    </div>

    <!-- Summary Statistics -->
    <div class="summary-stats">
        <div class="stat-item">
            <div class="stat-value">{{ number_format($summary_stats['total_properties']) }}</div>
            <div class="stat-label">{{ __('reports.exports.organizations.total_properties') }}</div>
        </div>
        <div class="stat-item">
            <div class="stat-value">{{ number_format($summary_stats['total_users']) }}</div>
            <div class="stat-label">{{ __('reports.exports.organizations.total_users') }}</div>
        </div>
        <div class="stat-item">
            <div class="stat-value">{{ number_format($summary_stats['avg_properties_per_org'], 1) }}</div>
            <div class="stat-label">{{ __('reports.exports.organizations.avg_properties_per_org') }}</div>
        </div>
        <div class="stat-item">
            <div class="stat-value">{{ number_format($summary_stats['avg_users_per_org'], 1) }}</div>
            <div class="stat-label">{{ __('reports.exports.organizations.avg_users_per_org') }}</div>
        </div>
    </div>

    <!-- Organizations Table -->
    <table class="organizations-table">
        <thead>
            <tr>
                <th>{{ __('reports.exports.common.id') }}</th>
                <th>{{ __('reports.exports.common.name') }}</th>
                <th>{{ __('reports.exports.common.email') }}</th>
                <th>{{ __('reports.exports.common.plan') }}</th>
                <th>{{ __('reports.exports.common.status') }}</th>
                <th>{{ __('reports.exports.common.properties') }}</th>
                <th>{{ __('reports.exports.common.users') }}</th>
                <th>{{ __('reports.exports.organizations.subscription_expires') }}</th>
                <th>{{ __('reports.exports.common.days_left') }}</th>
                <th>{{ __('reports.exports.common.created') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach($organizations as $org)
            <tr>
                <td>{{ $org->id }}</td>
                <td>{{ $org->name }}</td>
                <td>{{ $org->email }}</td>
                <td class="plan-{{ strtolower($org->plan?->value ?? 'basic') }}">
                    {{ enum_label($org->plan, \App\Enums\SubscriptionPlan::class) }}
                </td>
                <td class="status-{{ strtolower($org->getTenantStatus()->value) }}">
                    {{ enum_label($org->getTenantStatus(), \App\Enums\TenantStatus::class) }}
                </td>
                <td>{{ $org->properties_count ?? $org->properties()->count() }}/{{ $org->max_properties }}</td>
                <td>{{ $org->users_count ?? $org->users()->count() }}/{{ $org->max_users }}</td>
                <td>{{ $org->subscription_ends_at?->format('Y-m-d') ?? __('reports.exports.common.not_available') }}</td>
                <td>
                    @if($org->subscription_ends_at)
                        @if($org->daysUntilExpiry() < 0)
                            <span style="color: #dc2626;">{{ __('reports.exports.common.expired') }}</span>
                        @elseif($org->daysUntilExpiry() <= 7)
                            <span style="color: #dc2626;">{{ $org->daysUntilExpiry() }}</span>
                        @elseif($org->daysUntilExpiry() <= 14)
                            <span style="color: #d97706;">{{ $org->daysUntilExpiry() }}</span>
                        @else
                            {{ $org->daysUntilExpiry() }}
                        @endif
                    @else
                        {{ __('reports.exports.common.not_available') }}
                    @endif
                </td>
                <td>{{ $org->created_at->format('Y-m-d') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Distribution Charts -->
    <div style="margin-top: 20px;">
        <h3 style="color: #1e40af; font-size: 12px;">{{ __('reports.exports.organizations.status_distribution') }}</h3>
        <table style="width: 50%; border-collapse: collapse;">
            @foreach($status_distribution as $status => $count)
            <tr>
                <td style="padding: 3px 8px; border: 1px solid #e2e8f0;">{{ enum_label($status, \App\Enums\TenantStatus::class) }}</td>
                <td style="padding: 3px 8px; border: 1px solid #e2e8f0;">{{ $count }}</td>
                <td style="padding: 3px 8px; border: 1px solid #e2e8f0;">{{ number_format(($count / $total_count) * 100, 1) }}%</td>
            </tr>
            @endforeach
        </table>
    </div>

    <div style="margin-top: 15px;">
        <h3 style="color: #1e40af; font-size: 12px;">{{ __('reports.exports.organizations.plan_distribution') }}</h3>
        <table style="width: 50%; border-collapse: collapse;">
            @foreach($plan_distribution as $plan => $count)
            <tr>
                <td style="padding: 3px 8px; border: 1px solid #e2e8f0;">{{ enum_label($plan, \App\Enums\SubscriptionPlan::class) }}</td>
                <td style="padding: 3px 8px; border: 1px solid #e2e8f0;">{{ $count }}</td>
                <td style="padding: 3px 8px; border: 1px solid #e2e8f0;">{{ number_format(($count / $total_count) * 100, 1) }}%</td>
            </tr>
            @endforeach
        </table>
    </div>

    <div class="footer">
        <div>{{ __('reports.exports.organizations.footer_title') }}</div>
        <div>{{ __('reports.exports.common.confidential_notice') }}</div>
    </div>
</body>
</html>
