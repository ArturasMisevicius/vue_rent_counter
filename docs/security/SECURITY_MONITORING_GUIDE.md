# Security Monitoring Guide - CheckSubscriptionStatus

**Date**: December 2, 2025  
**Purpose**: Operational security monitoring for subscription middleware  
**Audience**: DevOps, Security Team, SRE

## Overview

This guide provides comprehensive monitoring strategies for detecting and responding to security incidents related to the CheckSubscriptionStatus middleware.

## Key Metrics to Monitor

### 1. Rate Limit Violations

**Metric**: `subscription_rate_limit_violations`  
**Threshold**: > 100 violations per hour  
**Severity**: WARNING → HIGH

**Query**:
```bash
# Count rate limit violations in last hour
grep "Rate limit exceeded for subscription checks" storage/logs/security.log | \
  grep "$(date -u +%Y-%m-%d)" | \
  tail -n 1000 | wc -l
```

**Alert Conditions**:
- 100-500 violations/hour: WARNING - Possible legitimate traffic spike
- 500-1000 violations/hour: HIGH - Likely attack in progress
- >1000 violations/hour: CRITICAL - Active DoS attack

**Response Actions**:
1. Identify source IPs/users from logs
2. Temporarily block repeat offenders
3. Scale infrastructure if legitimate traffic
4. Investigate for coordinated attack patterns

### 2. Invalid Redirect Attempts

**Metric**: `invalid_redirect_attempts`  
**Threshold**: > 10 attempts per hour  
**Severity**: HIGH

**Query**:
```bash
# Find invalid redirect attempts
grep "Invalid redirect route" storage/logs/laravel.log | \
  grep "$(date -u +%Y-%m-%d)"
```

**Alert Conditions**:
- Any occurrence: HIGH - Potential open redirect attack
- Multiple from same IP: CRITICAL - Active exploitation attempt

**Response Actions**:
1. Block source IP immediately
2. Review application logs for attack patterns
3. Check for compromised accounts
4. Verify redirect route whitelist is current

### 3. Cache Poisoning Attempts

**Metric**: `invalid_cache_key_attempts`  
**Threshold**: > 5 attempts per hour  
**Severity**: HIGH

**Query**:
```bash
# Find cache key validation failures
grep "Invalid user ID for cache key" storage/logs/laravel.log | \
  grep "$(date -u +%Y-%m-%d)"
```

**Alert Conditions**:
- Any occurrence: HIGH - Potential cache poisoning attack
- Multiple attempts: CRITICAL - Active attack

**Response Actions**:
1. Identify affected user accounts
2. Clear subscription cache
3. Review authentication logs
4. Check for account compromise

### 4. Subscription Enumeration

**Metric**: `subscription_check_failures`  
**Threshold**: > 50 failures per user per hour  
**Severity**: MEDIUM → HIGH

**Query**:
```bash
# Find users with excessive failed checks
grep "Subscription check performed" storage/logs/audit.log | \
  jq -r 'select(.check_result=="blocked") | .user_id' | \
  sort | uniq -c | sort -rn | head -20
```

**Alert Conditions**:
- 50-100 failures/hour: MEDIUM - Possible enumeration
- >100 failures/hour: HIGH - Active enumeration attack

**Response Actions**:
1. Rate limit affected users more aggressively
2. Require additional authentication
3. Monitor for data exfiltration
4. Review access patterns

### 5. PII Exposure

**Metric**: `pii_in_logs`  
**Threshold**: Any occurrence  
**Severity**: CRITICAL

**Query**:
```bash
# Check for unredacted emails (should find none)
grep -E "[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}" storage/logs/audit.log | \
  grep -v "EMAIL_REDACTED"
```

**Alert Conditions**:
- Any unredacted PII: CRITICAL - Privacy violation

**Response Actions**:
1. Immediately rotate affected logs
2. Verify RedactSensitiveData processor is active
3. Audit recent log entries
4. Report to privacy officer if required

## Monitoring Dashboards

### Grafana Dashboard Configuration

```yaml
# grafana-dashboard.json
{
  "dashboard": {
    "title": "Subscription Security Monitoring",
    "panels": [
      {
        "title": "Rate Limit Violations",
        "targets": [
          {
            "expr": "rate(subscription_rate_limit_violations[5m])"
          }
        ],
        "alert": {
          "conditions": [
            {
              "evaluator": {
                "params": [100],
                "type": "gt"
              }
            }
          ]
        }
      },
      {
        "title": "Invalid Redirect Attempts",
        "targets": [
          {
            "expr": "sum(invalid_redirect_attempts)"
          }
        ]
      },
      {
        "title": "Subscription Check Success Rate",
        "targets": [
          {
            "expr": "rate(subscription_checks_success[5m]) / rate(subscription_checks_total[5m])"
          }
        ]
      }
    ]
  }
}
```

### Prometheus Metrics

```yaml
# prometheus-rules.yml
groups:
  - name: subscription_security
    interval: 30s
    rules:
      - alert: HighRateLimitViolations
        expr: rate(subscription_rate_limit_violations[5m]) > 100
        for: 5m
        labels:
          severity: warning
        annotations:
          summary: "High rate limit violations detected"
          description: "{{ $value }} violations per second"
      
      - alert: InvalidRedirectAttempt
        expr: increase(invalid_redirect_attempts[1h]) > 10
        for: 1m
        labels:
          severity: high
        annotations:
          summary: "Invalid redirect attempts detected"
          description: "Potential open redirect attack"
      
      - alert: CachePoisoningAttempt
        expr: increase(invalid_cache_key_attempts[1h]) > 5
        for: 1m
        labels:
          severity: high
        annotations:
          summary: "Cache poisoning attempt detected"
          description: "Invalid cache key validation failures"
```

## Log Analysis Scripts

### Daily Security Report

```bash
#!/bin/bash
# daily-security-report.sh

DATE=$(date -u +%Y-%m-%d)
REPORT_FILE="security-report-${DATE}.txt"

echo "Security Report for ${DATE}" > ${REPORT_FILE}
echo "================================" >> ${REPORT_FILE}
echo "" >> ${REPORT_FILE}

# Rate limit violations
echo "Rate Limit Violations:" >> ${REPORT_FILE}
grep "Rate limit exceeded" storage/logs/security.log | \
  grep "${DATE}" | wc -l >> ${REPORT_FILE}
echo "" >> ${REPORT_FILE}

# Invalid redirect attempts
echo "Invalid Redirect Attempts:" >> ${REPORT_FILE}
grep "Invalid redirect route" storage/logs/laravel.log | \
  grep "${DATE}" | wc -l >> ${REPORT_FILE}
echo "" >> ${REPORT_FILE}

# Top violating IPs
echo "Top 10 Rate Limited IPs:" >> ${REPORT_FILE}
grep "Rate limit exceeded" storage/logs/security.log | \
  grep "${DATE}" | \
  jq -r '.ip' | \
  sort | uniq -c | sort -rn | head -10 >> ${REPORT_FILE}
echo "" >> ${REPORT_FILE}

# Subscription check patterns
echo "Subscription Check Summary:" >> ${REPORT_FILE}
grep "Subscription check performed" storage/logs/audit.log | \
  grep "${DATE}" | \
  jq -r '.check_result' | \
  sort | uniq -c >> ${REPORT_FILE}

# Email report
mail -s "Security Report ${DATE}" security@example.com < ${REPORT_FILE}
```

### Real-Time Monitoring

```bash
#!/bin/bash
# realtime-security-monitor.sh

# Monitor security logs in real-time
tail -f storage/logs/security.log | while read line; do
  # Check for rate limit violations
  if echo "$line" | grep -q "Rate limit exceeded"; then
    echo "[ALERT] Rate limit violation detected: $line"
    # Send to monitoring system
    curl -X POST https://monitoring.example.com/alert \
      -H "Content-Type: application/json" \
      -d "{\"type\":\"rate_limit\",\"message\":\"$line\"}"
  fi
  
  # Check for invalid redirects
  if echo "$line" | grep -q "Invalid redirect route"; then
    echo "[CRITICAL] Invalid redirect attempt: $line"
    # Send critical alert
    curl -X POST https://monitoring.example.com/alert \
      -H "Content-Type: application/json" \
      -d "{\"type\":\"invalid_redirect\",\"severity\":\"critical\",\"message\":\"$line\"}"
  fi
done
```

## Incident Response Procedures

### Rate Limit Violation Response

1. **Identify**: Extract source IP/user from logs
2. **Assess**: Determine if legitimate or malicious
3. **Contain**: Temporarily block if malicious
4. **Investigate**: Review access patterns
5. **Remediate**: Adjust rate limits if needed
6. **Document**: Record incident details

### Open Redirect Attempt Response

1. **Block**: Immediately block source IP
2. **Investigate**: Review all requests from source
3. **Audit**: Check for compromised accounts
4. **Verify**: Ensure whitelist is current
5. **Report**: Document for security team
6. **Monitor**: Watch for related attempts

### Cache Poisoning Response

1. **Clear**: Flush subscription cache immediately
2. **Identify**: Find affected user accounts
3. **Audit**: Review authentication logs
4. **Secure**: Reset affected user sessions
5. **Investigate**: Check for account compromise
6. **Document**: Record incident and response

## Compliance Reporting

### GDPR Compliance

**Log Retention**: 90 days for audit logs  
**PII Redaction**: Automatic via RedactSensitiveData processor  
**Access Controls**: Log files restricted to 0640 permissions  
**Data Subject Requests**: Process via privacy officer

### SOC 2 Compliance

**Monitoring**: 24/7 automated monitoring  
**Alerting**: Real-time alerts for security events  
**Incident Response**: Documented procedures  
**Audit Trail**: Comprehensive logging with retention

### PCI DSS (if applicable)

**Log Review**: Daily automated review  
**Access Restrictions**: Role-based log access  
**Integrity**: Log file permissions and checksums  
**Retention**: 90-day minimum retention

## Contact Information

**Security Team**: security@example.com  
**On-Call**: +1-555-SECURITY  
**Incident Response**: incidents@example.com  
**Privacy Officer**: privacy@example.com

## Appendix: Log Format Reference

### Audit Log Format

```json
{
  "message": "Subscription check performed",
  "check_result": "allowed|blocked",
  "message_type": "warning|error|null",
  "user_id": 123,
  "user_email": "[EMAIL_REDACTED]",
  "subscription_id": 456,
  "subscription_status": "active",
  "expires_at": "2025-12-31T23:59:59+00:00",
  "route": "admin.dashboard",
  "method": "GET",
  "ip": "[IP_REDACTED]",
  "timestamp": "2025-12-02T10:30:00+00:00"
}
```

### Security Log Format

```json
{
  "message": "Rate limit exceeded for subscription checks",
  "key": "subscription-check:user:123",
  "user_id": 123,
  "ip": "[IP_REDACTED]",
  "route": "admin.dashboard",
  "user_agent": "Mozilla/5.0...",
  "timestamp": "2025-12-02T10:30:00+00:00"
}
```

---

**Last Updated**: December 2, 2025  
**Next Review**: March 2, 2026  
**Version**: 1.0
