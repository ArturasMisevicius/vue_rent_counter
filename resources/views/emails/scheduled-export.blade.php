<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $subject }}</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f8fafc;
        }
        
        .email-container {
            background: white;
            border-radius: 8px;
            padding: 30px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
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
        
        .greeting {
            font-size: 16px;
            margin-bottom: 20px;
        }
        
        .content {
            font-size: 14px;
            line-height: 1.6;
            margin-bottom: 25px;
        }
        
        .attachments-info {
            background: #f1f5f9;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 15px;
            margin: 20px 0;
        }
        
        .attachments-info h3 {
            color: #1e40af;
            font-size: 14px;
            margin: 0 0 10px 0;
        }
        
        .attachments-info ul {
            margin: 0;
            padding-left: 20px;
        }
        
        .attachments-info li {
            margin-bottom: 5px;
            color: #374151;
        }
        
        .footer {
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
            text-align: center;
            color: #6b7280;
            font-size: 12px;
        }
        
        .footer .platform-name {
            font-weight: bold;
            color: #1e40af;
        }
        
        .security-notice {
            background: #fef3c7;
            border: 1px solid #f59e0b;
            border-radius: 6px;
            padding: 12px;
            margin: 20px 0;
            font-size: 12px;
            color: #92400e;
        }
        
        .security-notice strong {
            color: #78350f;
        }
    </style>
</head>
<body>
    <div class="email-container">
        <div class="header">
            <h1>Scheduled Export Report</h1>
            <div class="subtitle">{{ now()->format('F j, Y \a\t g:i A') }}</div>
        </div>

        <div class="greeting">
            Hello {{ $user->name }},
        </div>

        <div class="content">
            {{ $body }}
        </div>

        <div class="attachments-info">
            <h3>📎 Attached Files</h3>
            <p>This email contains the following export files:</p>
            <ul>
                <li>CSV/Excel files for data analysis</li>
                <li>PDF reports for executive review</li>
                <li>JSON files for system integration (if applicable)</li>
            </ul>
        </div>

        <div class="security-notice">
            <strong>⚠️ Security Notice:</strong> These files contain confidential platform data. 
            Please handle them according to your organization's data security policies. 
            Do not forward these files to unauthorized personnel.
        </div>

        <div class="content">
            <p>If you have any questions about these reports or need additional data exports, 
            please contact the platform administrator or access the superadmin dashboard directly.</p>
            
            <p>You can also configure your export preferences and schedules through the 
            System Settings page in the superadmin dashboard.</p>
        </div>

        <div class="footer">
            <div class="platform-name">Vilnius Utilities Billing Platform</div>
            <div>Superadmin Dashboard - Automated Export System</div>
            <div style="margin-top: 10px;">
                This is an automated message. Please do not reply to this email.
            </div>
        </div>
    </div>
</body>
</html>