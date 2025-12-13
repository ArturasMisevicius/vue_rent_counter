<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscriptions Report</title>
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
        <h1>Subscriptions Report</h1>
        <div>Generated on {{ $generated_at->format('F j, Y \a\t g:i A') }}</div>
        <div>Total Subscriptions: {{ number_format($total_count) }}</div>
    </div>

    <!-- Summary Statistics -->
    <div class="summary-stats">
        <div class="stat-item">
            <div class="stat-value">{{ number_format($total_count) }}</div>
            <div class="stat-label">Total Subscriptions</div>
        </div>
        <div class="stat-item">
            <div class="stat-value">{{ number_format($expiring_count) }}</div>
            <div class="stat-label">Expiring Soon (14 days)</div>
        </div>
        <div class="stat-item">
            <div class="stat-value">{{ number_format($summary_stats['total_max_properties']) }}</div>
            <div class="stat-label">Total Property Capacity</div>
        </div>
        <div class="stat-item">
            <div class="stat-value">{{ number_format($summary_stats['avg_days_until_expiry'], 0) }}</div>
            <div class="stat-label">Avg Days Until Expiry</div>
        </div>
    </div>

    <!-- Subscriptions Table -->
    <table class="subscriptions-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Organization</th>
                <th>User</th>
                <th>Plan</th>
                <th>Status</th>
                <th>Starts</th>
                <th>Expires</th>
                <th>Days Left</th>
                <th>Max Properties</th>
                <th>Max Tenants</th>
                <th>Active</th>
            </tr>
        </thead>
        <tbody>
            @foreach($subscriptions as $subscription)
            <tr>
                <td>{{ $subscription->id }}</td>
                <td>{{ $subscription->user?->organization?->name ?? 'N/A' }}</td>
                <td>{{ $subscription->user?->name ?? 'N/A' }}</td>
                <td class="plan-{{ strtolower($subscription->plan_type) }}">
                    {{ ucfirst($subscription->plan_type) }}
                </td>
                <td class="status-{{ strtolower($subscription->status?->value ?? 'unknown') }}">
                    {{ ucfirst($subscription->status?->value ?? 'Unknown') }}
                </td>
                <td>{{ $subscription->starts_at?->format('Y-m-d') ?? 'N/A' }}</td>
                <td>{{ $subscription->expires_at?->format('Y-m-d') ?? 'N/A' }}</td>
                <td>
                    @if($subscription->expires_at)
                        @php $days = $subscription->daysUntilExpiry(); @endphp
                        @if($days < 0)
                            <span class="expiry-critical">Expired</span>
                        @elseif($days <= 7)
                            <span class="expiry-critical">{{ $days }}</span>
                        @elseif($days <= 14)
                            <span class="expiry-warning">{{ $days }}</span>
                        @else
                            <span class="expiry-normal">{{ $days }}</span>
                        @endif
                    @else
                        N/A
                    @endif
                </td>
                <td>{{ number_format($subscription->max_properties) }}</td>
                <td>{{ number_format($subscription->max_tenants) }}</td>
                <td>{{ $subscription->isActive() ? 'Yes' : 'No' }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Distribution Charts -->
    <div class="distribution-section">
        <h3 style="color: #1e40af; font-size: 14px; margin-bottom: 10px;">Status Distribution</h3>
        <table class="distribution-table">
            <thead>
                <tr>
                    <th>Status</th>
                    <th>Count</th>
                    <th>Percentage</th>
                </tr>
            </thead>
            <tbody>
                @foreach($status_distribution as $status => $count)
                <tr>
                    <td>{{ ucfirst($status) }}</td>
                    <td>{{ number_format($count) }}</td>
                    <td>{{ number_format(($count / $total_count) * 100, 1) }}%</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="distribution-section">
        <h3 style="color: #1e40af; font-size: 14px; margin-bottom: 10px;">Plan Distribution</h3>
        <table class="distribution-table">
            <thead>
                <tr>
                    <th>Plan Type</th>
                    <th>Count</th>
                    <th>Percentage</th>
                </tr>
            </thead>
            <tbody>
                @foreach($plan_distribution as $plan => $count)
                <tr>
                    <td>{{ ucfirst($plan) }}</td>
                    <td>{{ number_format($count) }}</td>
                    <td>{{ number_format(($count / $total_count) * 100, 1) }}%</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="footer">
        <div>Vilnius Utilities Billing Platform - Subscriptions Report</div>
        <div>This report contains confidential information. Distribution is restricted.</div>
    </div>
</body>
</html>