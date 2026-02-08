<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Organizations Report</title>
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
        <h1>Organizations Report</h1>
        <div>Generated on {{ $generated_at->format('F j, Y \a\t g:i A') }}</div>
        <div>Total Organizations: {{ number_format($total_count) }}</div>
    </div>

    <!-- Summary Statistics -->
    <div class="summary-stats">
        <div class="stat-item">
            <div class="stat-value">{{ number_format($summary_stats['total_properties']) }}</div>
            <div class="stat-label">Total Properties</div>
        </div>
        <div class="stat-item">
            <div class="stat-value">{{ number_format($summary_stats['total_users']) }}</div>
            <div class="stat-label">Total Users</div>
        </div>
        <div class="stat-item">
            <div class="stat-value">{{ number_format($summary_stats['avg_properties_per_org'], 1) }}</div>
            <div class="stat-label">Avg Properties/Org</div>
        </div>
        <div class="stat-item">
            <div class="stat-value">{{ number_format($summary_stats['avg_users_per_org'], 1) }}</div>
            <div class="stat-label">Avg Users/Org</div>
        </div>
    </div>

    <!-- Organizations Table -->
    <table class="organizations-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Email</th>
                <th>Plan</th>
                <th>Status</th>
                <th>Properties</th>
                <th>Users</th>
                <th>Subscription Expires</th>
                <th>Days Left</th>
                <th>Created</th>
            </tr>
        </thead>
        <tbody>
            @foreach($organizations as $org)
            <tr>
                <td>{{ $org->id }}</td>
                <td>{{ $org->name }}</td>
                <td>{{ $org->email }}</td>
                <td class="plan-{{ strtolower($org->plan?->value ?? 'basic') }}">
                    {{ ucfirst($org->plan?->value ?? 'Basic') }}
                </td>
                <td class="status-{{ strtolower($org->getTenantStatus()->value) }}">
                    {{ ucfirst($org->getTenantStatus()->value) }}
                </td>
                <td>{{ $org->properties()->count() }}/{{ $org->max_properties }}</td>
                <td>{{ $org->users()->count() }}/{{ $org->max_users }}</td>
                <td>{{ $org->subscription_ends_at?->format('Y-m-d') ?? 'N/A' }}</td>
                <td>
                    @if($org->subscription_ends_at)
                        @if($org->daysUntilExpiry() < 0)
                            <span style="color: #dc2626;">Expired</span>
                        @elseif($org->daysUntilExpiry() <= 7)
                            <span style="color: #dc2626;">{{ $org->daysUntilExpiry() }}</span>
                        @elseif($org->daysUntilExpiry() <= 14)
                            <span style="color: #d97706;">{{ $org->daysUntilExpiry() }}</span>
                        @else
                            {{ $org->daysUntilExpiry() }}
                        @endif
                    @else
                        N/A
                    @endif
                </td>
                <td>{{ $org->created_at->format('Y-m-d') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <!-- Distribution Charts -->
    <div style="margin-top: 20px;">
        <h3 style="color: #1e40af; font-size: 12px;">Status Distribution</h3>
        <table style="width: 50%; border-collapse: collapse;">
            @foreach($status_distribution as $status => $count)
            <tr>
                <td style="padding: 3px 8px; border: 1px solid #e2e8f0;">{{ ucfirst($status) }}</td>
                <td style="padding: 3px 8px; border: 1px solid #e2e8f0;">{{ $count }}</td>
                <td style="padding: 3px 8px; border: 1px solid #e2e8f0;">{{ number_format(($count / $total_count) * 100, 1) }}%</td>
            </tr>
            @endforeach
        </table>
    </div>

    <div style="margin-top: 15px;">
        <h3 style="color: #1e40af; font-size: 12px;">Plan Distribution</h3>
        <table style="width: 50%; border-collapse: collapse;">
            @foreach($plan_distribution as $plan => $count)
            <tr>
                <td style="padding: 3px 8px; border: 1px solid #e2e8f0;">{{ ucfirst($plan) }}</td>
                <td style="padding: 3px 8px; border: 1px solid #e2e8f0;">{{ $count }}</td>
                <td style="padding: 3px 8px; border: 1px solid #e2e8f0;">{{ number_format(($count / $total_count) * 100, 1) }}%</td>
            </tr>
            @endforeach
        </table>
    </div>

    <div class="footer">
        <div>Vilnius Utilities Billing Platform - Organizations Report</div>
        <div>This report contains confidential information. Distribution is restricted.</div>
    </div>
</body>
</html>
