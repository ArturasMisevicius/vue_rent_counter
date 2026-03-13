<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Executive Summary Report</title>
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
        <h1>Executive Summary Report</h1>
        <div class="subtitle">
            Generated on {{ $generated_at->format('F j, Y \a\t g:i A') }} | Period: {{ $period }}
        </div>
    </div>

    <!-- Key Metrics -->
    <div class="metrics-grid">
        <div class="metric-card">
            <h3>Organizations</h3>
            <div class="metric-value">{{ number_format($metrics['organizations']['total']) }}</div>
            <div class="metric-label">Total Organizations</div>
            <div style="margin-top: 10px; font-size: 11px;">
                <div>Active: {{ number_format($metrics['organizations']['active']) }}</div>
                <div>Suspended: {{ number_format($metrics['organizations']['suspended']) }}</div>
                <div>Growth (30d): +{{ number_format($metrics['organizations']['growth']) }}</div>
            </div>
        </div>
        
        <div class="metric-card">
            <h3>Subscriptions</h3>
            <div class="metric-value">{{ number_format($metrics['subscriptions']['total']) }}</div>
            <div class="metric-label">Total Subscriptions</div>
            <div style="margin-top: 10px; font-size: 11px;">
                <div>Active: {{ number_format($metrics['subscriptions']['active']) }}</div>
                <div>Expiring Soon: {{ number_format($metrics['subscriptions']['expiring']) }}</div>
                <div>Growth (30d): +{{ number_format($metrics['subscriptions']['growth']) }}</div>
            </div>
        </div>
        
        <div class="metric-card">
            <h3>Platform Usage</h3>
            <div class="metric-value">{{ number_format($metrics['platform']['users']) }}</div>
            <div class="metric-label">Total Users</div>
            <div style="margin-top: 10px; font-size: 11px;">
                <div>Properties: {{ number_format($metrics['platform']['properties']) }}</div>
                <div>Invoices: {{ number_format($metrics['platform']['invoices']) }}</div>
            </div>
        </div>
    </div>

    <!-- Plan Distribution -->
    <div class="section">
        <h2>Subscription Plan Distribution</h2>
        <table class="distribution-table">
            <thead>
                <tr>
                    <th>Plan Type</th>
                    <th>Organizations</th>
                    <th>Percentage</th>
                </tr>
            </thead>
            <tbody>
                @foreach($plan_distribution as $plan => $count)
                <tr>
                    <td>{{ ucfirst($plan) }}</td>
                    <td>{{ number_format($count) }}</td>
                    <td>{{ number_format(($count / $metrics['organizations']['total']) * 100, 1) }}%</td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <!-- Recent Activity -->
    <div class="section">
        <h2>Recent Platform Activity</h2>
        @foreach($recent_activity as $activity)
        <div class="activity-item">
            <div class="activity-time">{{ $activity->created_at->format('M j, Y g:i A') }}</div>
            <div class="activity-action">{{ $activity->action }}</div>
            <div>
                <span class="activity-user">{{ $activity->user?->name ?? 'System' }}</span>
                @if($activity->organization)
                    in <strong>{{ $activity->organization->name }}</strong>
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
        <div>Vilnius Utilities Billing Platform - Superadmin Dashboard</div>
        <div>This report contains confidential information. Distribution is restricted.</div>
    </div>
</body>
</html>