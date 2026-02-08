# Security Monitoring Guide

## Overview

This guide covers monitoring, alerting, and incident response for security events in the application.

## Log Locations

```bash
# Security logs (90-day retention)
storage/logs/security.log

# Audit logs (90-day retention)
storage/logs/audit.log

# Application logs (14-day retention)
storage/logs/laravel.log
```

## Real-Time Monitoring

### Viewing Security Events

```bash
# Tail security log
tail -f storage/logs/security.log

# Filter for path traversal attempts
tail -f storage/logs/security.log | grep "Path traversal"

# View last 100 security events
tail -100 storage/logs/security.log | jq '.'

# Count violations by type
grep "Security violation" storage/logs/security.log | \
  jq -r '.type' | sort | uniq -c | sort -rn
```

### Analyzing Attack Patterns

```bash
# Count attempts by IP hash
grep "Path traversal" storage/logs/security.log | \
  jq -r '.ip_hash' | sort | uniq -c | sort -rn

# View violations from specific IP hash
grep "ip_hash\":\"abc123..." storage/logs/security.log | jq '.'

# Find authenticated user violations (CRITICAL)
grep "user_id" storage/logs/security.log | \
  jq 'select(.user_id != null)' | jq -r '.user_id' | sort | uniq

# Violations in last hour
grep "$(date -u +%Y-%m-%d)" storage/logs/security.log | \
  grep "$(date -u +%H):" | wc -l
```

## Alert Thresholds

### Automatic Alerts (Implemented)

| Threshold | Severity | Action |
|-----------|----------|--------|
| 5+ violations from same IP/hour | WARNING | Log critical message |
| 10+ violations from same IP/hour | CRITICAL | Trigger alert (TODO: implement) |
| Any violation from authenticated user | IMMEDIATE | Manual investigation required |
| 100+ global violations/hour | WARNING | Possible coordinated attack |

### Manual Alert Configuration

**Slack Notifications** (recommended):

```php
// app/Listeners/AlertSecurityTeam.php
use Illuminate\Support\Facades\Http;

final class AlertSecurityTeam implements ShouldQueue
{
    public function handle(SecurityViolationDetected $event): void
    {
        $violations = cache()->get("security:violations:{$event->ipAddress}", 0);
        
        if ($violations > 10) {
            Http::post(config('services.slack.security_webhook'), [
                'text' => '🚨 Security Alert: Multiple violations detected',
                'attachments' => [[
                    'color' => 'danger',
                    'fields' => [
                        ['title' => 'IP Hash', 'value' => $event->ipAddress, 'short' => true],
                        ['title' => 'Violation Count', 'value' => $violations, 'short' => true],
                        ['title' => 'Type', 'value' => $event->violationType, 'short' => true],
                        ['title' => 'User ID', 'value' => $event->userId ?? 'Guest', 'short' => true],
                    ],
                ]],
            ]);
        }
    }
}
```

**Email Alerts**:

```php
// app/Notifications/SecurityAlert.php
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

final class SecurityAlert extends Notification
{
    public function __construct(
        private SecurityViolationDetected $event
    ) {}
    
    public function via($notifiable): array
    {
        return ['mail'];
    }
    
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Security Alert: Multiple Violations Detected')
            ->error()
            ->line('Multiple security violations detected from the same source.')
            ->line("Violation Type: {$this->event->violationType}")
            ->line("IP Hash: {$this->event->ipAddress}")
            ->action('View Security Logs', url('/admin/security-logs'));
    }
}
```

## Metrics & Dashboards

### Key Metrics to Track

1. **Violation Rate**
   - Total violations per hour/day
   - Violations by type (path_traversal, xss_attempt, etc.)
   - Trend over time

2. **Attack Sources**
   - Unique IP hashes attempting violations
   - Geographic distribution (if using GeoIP)
   - Authenticated vs. unauthenticated attempts

3. **Response Times**
   - Time to detect violation
   - Time to alert security team
   - Time to remediate

4. **False Positive Rate**
   - Legitimate requests blocked
   - User complaints about blocked actions

### Filament Dashboard Widget

```php
// app/Filament/Widgets/SecurityViolationsWidget.php
namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

final class SecurityViolationsWidget extends ChartWidget
{
    protected static ?string $heading = 'Security Violations (Last 7 Days)';
    
    protected function getData(): array
    {
        // Parse security log for last 7 days
        $violations = $this->parseSecurityLog();
        
        return [
            'datasets' => [
                [
                    'label' => 'Path Traversal',
                    'data' => $violations['path_traversal'],
                    'backgroundColor' => 'rgba(255, 99, 132, 0.2)',
                    'borderColor' => 'rgba(255, 99, 132, 1)',
                ],
                [
                    'label' => 'XSS Attempts',
                    'data' => $violations['xss_attempt'],
                    'backgroundColor' => 'rgba(54, 162, 235, 0.2)',
                    'borderColor' => 'rgba(54, 162, 235, 1)',
                ],
            ],
            'labels' => $violations['labels'],
        ];
    }
    
    protected function getType(): string
    {
        return 'line';
    }
    
    private function parseSecurityLog(): array
    {
        // Implementation: Parse storage/logs/security.log
        // Group by date and violation type
        // Return formatted data for chart
    }
}
```

## Incident Response Procedures

### Level 1: Single Violation (INFO)

**Trigger**: 1-4 violations from same IP in 1 hour

**Response**:
1. Log event (automatic)
2. No immediate action required
3. Monitor for escalation

### Level 2: Repeated Violations (WARNING)

**Trigger**: 5-9 violations from same IP in 1 hour

**Response**:
1. Critical log entry (automatic)
2. Review violation patterns
3. Check if legitimate user affected
4. Consider temporary rate limiting

### Level 3: Sustained Attack (CRITICAL)

**Trigger**: 10+ violations from same IP in 1 hour

**Response**:
1. Alert security team (automatic)
2. **Immediate Actions**:
   - Review attack pattern
   - Identify affected resources
   - Check for data exfiltration
3. **Containment**:
   - Block IP at firewall level
   - Increase rate limiting
   - Enable additional logging
4. **Investigation**:
   - Analyze full attack timeline
   - Check for successful exploits
   - Review related logs
5. **Remediation**:
   - Patch vulnerabilities
   - Update security rules
   - Document incident

### Level 4: Authenticated User Violation (IMMEDIATE)

**Trigger**: Any violation from authenticated user

**Response**:
1. **Immediate Investigation**:
   - Review user account history
   - Check for account compromise
   - Analyze user's recent actions
2. **Containment**:
   - Suspend user account
   - Invalidate sessions
   - Notify user (if legitimate)
3. **Analysis**:
   - Determine if intentional or accidental
   - Check for privilege escalation attempts
   - Review tenant isolation
4. **Resolution**:
   - If compromised: Force password reset, enable 2FA
   - If malicious: Permanent ban, legal action
   - If accidental: User education, restore access

## Security Audit Checklist

### Daily Checks

- [ ] Review security log for critical alerts
- [ ] Check violation count trends
- [ ] Verify backup completion
- [ ] Monitor application error rates

### Weekly Checks

- [ ] Analyze violation patterns
- [ ] Review blocked IPs
- [ ] Check false positive reports
- [ ] Update security rules if needed
- [ ] Review user access logs

### Monthly Checks

- [ ] Full security log analysis
- [ ] Update threat intelligence
- [ ] Review and update alert thresholds
- [ ] Security training for team
- [ ] Penetration testing (if scheduled)
- [ ] Review and update incident response procedures

### Quarterly Checks

- [ ] Comprehensive security audit
- [ ] Review all security policies
- [ ] Update security documentation
- [ ] Third-party security assessment
- [ ] Compliance review (GDPR, CCPA)
- [ ] Disaster recovery drill

## Integration with External Tools

### Log Aggregation (Recommended)

**ELK Stack**:
```yaml
# filebeat.yml
filebeat.inputs:
  - type: log
    enabled: true
    paths:
      - /path/to/storage/logs/security.log
    json.keys_under_root: true
    json.add_error_key: true
    fields:
      log_type: security
      application: vilnius-utilities
```

**Splunk**:
```conf
[monitor:///path/to/storage/logs/security.log]
sourcetype = json
index = security
```

### SIEM Integration

**Wazuh**:
```xml
<localfile>
  <log_format>json</log_format>
  <location>/path/to/storage/logs/security.log</location>
</localfile>
```

### Alerting Services

**PagerDuty**:
```php
// config/services.php
'pagerduty' => [
    'integration_key' => env('PAGERDUTY_INTEGRATION_KEY'),
],

// Usage in listener
Http::post('https://events.pagerduty.com/v2/enqueue', [
    'routing_key' => config('services.pagerduty.integration_key'),
    'event_action' => 'trigger',
    'payload' => [
        'summary' => 'Security violation detected',
        'severity' => 'critical',
        'source' => 'vilnius-utilities',
    ],
]);
```

## Performance Monitoring

### Sanitization Performance

```bash
# Average sanitization time
grep "sanitizeIdentifier" storage/logs/laravel.log | \
  jq -r '.duration' | awk '{sum+=$1; count++} END {print sum/count}'

# Cache hit rate
grep "request_cache" storage/logs/laravel.log | \
  jq -r '.cache_hit' | grep -c "true"
```

### Rate Limiting Effectiveness

```bash
# Count 429 responses
grep "429" storage/logs/laravel.log | wc -l

# Rate limited IPs
grep "429" storage/logs/laravel.log | \
  jq -r '.ip' | sort | uniq -c | sort -rn
```

## Compliance Reporting

### GDPR Compliance Report

```bash
# Generate monthly report
php artisan security:gdpr-report --month=2024-12

# Output:
# - PII access requests: X
# - Data deletion requests: X
# - Security incidents: X
# - Data breaches: X (should be 0)
```

### Security Metrics Report

```bash
# Generate quarterly security report
php artisan security:metrics-report --quarter=Q4-2024

# Output:
# - Total violations: X
# - Blocked attacks: X
# - False positives: X
# - Mean time to detect: X minutes
# - Mean time to respond: X minutes
```

## Contact Information

### Security Team

- **Email**: security@example.com
- **Slack**: #security-alerts
- **PagerDuty**: On-call rotation
- **Emergency**: +1-XXX-XXX-XXXX

### Escalation Path

1. **Level 1**: Development team
2. **Level 2**: Security team lead
3. **Level 3**: CTO/CISO
4. **Level 4**: Executive team + legal

## References

- [OWASP Logging Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Logging_Cheat_Sheet.html)
- [NIST Incident Response Guide](https://nvlpubs.nist.gov/nistpubs/SpecialPublications/NIST.SP.800-61r2.pdf)
- [Laravel Security Best Practices](https://laravel.com/docs/12.x/security)
