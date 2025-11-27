# HierarchicalScope Security Monitoring Guide

## Overview

This guide provides comprehensive monitoring and alerting strategies for the HierarchicalScope security component.

---

## 📊 Key Metrics to Monitor

### 1. Scope Bypass Attempts
**Metric**: `hierarchical_scope.bypass_attempts`  
**Log Pattern**: `HierarchicalScope bypassed`  
**Threshold**: >10 attempts in 5 minutes = CRITICAL  

**Query**:
```bash
# Last hour
grep "HierarchicalScope bypassed" storage/logs/laravel-$(date +%Y-%m-%d).log | wc -l

# Real-time monitoring
tail -f storage/logs/laravel.log | grep "HierarchicalScope bypassed"
```

**Alert Rule**:
```yaml
alert: HighScopeBypassRate
expr: rate(hierarchical_scope_bypass_total[5m]) > 10
severity: critical
annotations:
  summary: "High rate of scope bypass attempts detected"
  description: "{{ $value }} bypass attempts in the last 5 minutes"
```

---

### 2. Input Validation Failures
**Metric**: `hierarchical_scope.validation_failures`  
**Log Pattern**: `Invalid tenant_id|Invalid property_id`  
**Threshold**: >50 failures in 1 hour = HIGH  

**Query**:
```bash
# Count validation failures
grep -E "Invalid tenant_id|Invalid property_id" storage/logs/laravel-$(date +%Y-%m-%d).log | wc -l

# Group by error type
grep -E "Invalid tenant_id|Invalid property_id" storage/logs/laravel-$(date +%Y-%m-%d).log | \
  awk '{print $NF}' | sort | uniq -c
```

**Alert Rule**:
```yaml
alert: HighValidationFailureRate
expr: rate(hierarchical_scope_validation_failures_total[1h]) > 50
severity: high
annotations:
  summary: "High rate of validation failures"
  description: "{{ $value }} validation failures in the last hour"
```

---

### 3. Missing Tenant Context
**Metric**: `hierarchical_scope.missing_context`  
**Log Pattern**: `Query executed without tenant context`  
**Threshold**: >5 occurrences in 10 minutes = MEDIUM  

**Query**:
```bash
# Count missing context events
grep "Query executed without tenant context" storage/logs/laravel-$(date +%Y-%m-%d).log | wc -l

# Show affected users
grep "Query executed without tenant context" storage/logs/laravel-$(date +%Y-%m-%d).log | \
  grep -oP 'user_id":\K[0-9]+' | sort | uniq -c
```

---

### 4. Schema Query Cache Performance
**Metric**: `hierarchical_scope.cache_hit_rate`  
**Target**: >90% hit rate  
**Threshold**: <80% = MEDIUM  

**Query**:
```bash
# Check cache statistics (Redis)
redis-cli INFO stats | grep keyspace_hits
redis-cli INFO stats | grep keyspace_misses

# Calculate hit rate
redis-cli INFO stats | awk '/keyspace_hits/{hits=$2} /keyspace_misses/{misses=$2} END{print hits/(hits+misses)*100"%"}'
```

---

### 5. Superadmin Access Frequency
**Metric**: `hierarchical_scope.superadmin_access`  
**Log Pattern**: `Superadmin unrestricted access`  
**Threshold**: Access outside business hours = INFO  

**Query**:
```bash
# Count superadmin access today
grep "Superadmin unrestricted access" storage/logs/laravel-$(date +%Y-%m-%d).log | wc -l

# Show access times
grep "Superadmin unrestricted access" storage/logs/laravel-$(date +%Y-%m-%d).log | \
  grep -oP '\[\d{4}-\d{2}-\d{2} \K\d{2}:\d{2}:\d{2}'
```

---

## 🚨 Alert Configuration

### Laravel Logging Configuration

Update `config/logging.php`:

```php
'channels' => [
    'security' => [
        'driver' => 'daily',
        'path' => storage_path('logs/security.log'),
        'level' => 'info',
        'days' => 90, // Retain for compliance
    ],
    
    'audit' => [
        'driver' => 'daily',
        'path' => storage_path('logs/audit.log'),
        'level' => 'info',
        'days' => 365, // Retain for 1 year
    ],
],
```

### Dedicated Security Logging

Create `app/Logging/SecurityLogger.php`:

```php
<?php

namespace App\Logging;

use Illuminate\Support\Facades\Log;

class SecurityLogger
{
    public static function logScopeBypass(array $context): void
    {
        Log::channel('security')->warning('Scope bypass attempt', $context);
        Log::channel('audit')->info('Scope bypass', $context);
    }
    
    public static function logValidationFailure(string $type, array $context): void
    {
        Log::channel('security')->error("Validation failure: {$type}", $context);
    }
    
    public static function logSuperadminAccess(array $context): void
    {
        Log::channel('audit')->info('Superadmin access', $context);
    }
}
```

---

## 📈 Monitoring Dashboards

### Grafana Dashboard Configuration

```json
{
  "dashboard": {
    "title": "HierarchicalScope Security",
    "panels": [
      {
        "title": "Scope Bypass Attempts",
        "targets": [
          {
            "expr": "rate(hierarchical_scope_bypass_total[5m])"
          }
        ]
      },
      {
        "title": "Validation Failures",
        "targets": [
          {
            "expr": "rate(hierarchical_scope_validation_failures_total[1h])"
          }
        ]
      },
      {
        "title": "Cache Hit Rate",
        "targets": [
          {
            "expr": "hierarchical_scope_cache_hits / (hierarchical_scope_cache_hits + hierarchical_scope_cache_misses)"
          }
        ]
      }
    ]
  }
}
```

---

## 🔍 Log Analysis Scripts

### Daily Security Report

Create `scripts/security-report.sh`:

```bash
#!/bin/bash

DATE=$(date +%Y-%m-%d)
LOG_FILE="storage/logs/laravel-${DATE}.log"
REPORT_FILE="storage/logs/security-report-${DATE}.txt"

echo "HierarchicalScope Security Report - ${DATE}" > ${REPORT_FILE}
echo "================================================" >> ${REPORT_FILE}
echo "" >> ${REPORT_FILE}

echo "Scope Bypass Attempts:" >> ${REPORT_FILE}
grep -c "HierarchicalScope bypassed" ${LOG_FILE} >> ${REPORT_FILE}
echo "" >> ${REPORT_FILE}

echo "Validation Failures:" >> ${REPORT_FILE}
grep -cE "Invalid tenant_id|Invalid property_id" ${LOG_FILE} >> ${REPORT_FILE}
echo "" >> ${REPORT_FILE}

echo "Missing Tenant Context:" >> ${REPORT_FILE}
grep -c "Query executed without tenant context" ${LOG_FILE} >> ${REPORT_FILE}
echo "" >> ${REPORT_FILE}

echo "Superadmin Access:" >> ${REPORT_FILE}
grep -c "Superadmin unrestricted access" ${LOG_FILE} >> ${REPORT_FILE}
echo "" >> ${REPORT_FILE}

echo "Top Users by Bypass Attempts:" >> ${REPORT_FILE}
grep "HierarchicalScope bypassed" ${LOG_FILE} | \
  grep -oP 'user_id":\K[0-9]+' | sort | uniq -c | sort -rn | head -10 >> ${REPORT_FILE}

# Email report
mail -s "HierarchicalScope Security Report - ${DATE}" security@example.com < ${REPORT_FILE}
```

### Real-Time Anomaly Detection

Create `scripts/anomaly-detection.sh`:

```bash
#!/bin/bash

# Monitor for suspicious patterns
tail -f storage/logs/laravel.log | while read line; do
    # Detect rapid bypass attempts from same user
    if echo "$line" | grep -q "HierarchicalScope bypassed"; then
        USER_ID=$(echo "$line" | grep -oP 'user_id":\K[0-9]+')
        COUNT=$(grep "HierarchicalScope bypassed" storage/logs/laravel-$(date +%Y-%m-%d).log | \
                grep "user_id\":${USER_ID}" | wc -l)
        
        if [ $COUNT -gt 10 ]; then
            echo "ALERT: User ${USER_ID} has ${COUNT} bypass attempts today"
            # Send alert
            curl -X POST https://alerts.example.com/webhook \
                -d "user_id=${USER_ID}&count=${COUNT}&type=bypass"
        fi
    fi
    
    # Detect validation attacks
    if echo "$line" | grep -qE "Invalid tenant_id|Invalid property_id"; then
        IP=$(echo "$line" | grep -oP 'ip":\K[^"]+')
        COUNT=$(grep -E "Invalid tenant_id|Invalid property_id" storage/logs/laravel-$(date +%Y-%m-%d).log | \
                grep "ip\":\"${IP}\"" | wc -l)
        
        if [ $COUNT -gt 50 ]; then
            echo "ALERT: IP ${IP} has ${COUNT} validation failures today"
            # Consider IP blocking
            curl -X POST https://firewall.example.com/block \
                -d "ip=${IP}&reason=validation_attack"
        fi
    fi
done
```

---

## 🛡️ Incident Response Procedures

### Scope Bypass Attack

**Detection**: >10 bypass attempts in 5 minutes

**Response**:
1. Identify affected user: `grep "HierarchicalScope bypassed" logs | grep -oP 'user_id":\K[0-9]+'`
2. Review user's recent activity
3. Check if user has legitimate superadmin access
4. If suspicious, disable user account: `php artisan user:disable {user_id}`
5. Review all queries from that user in the last 24 hours
6. Document incident in security log

### Validation Attack

**Detection**: >50 validation failures in 1 hour from same IP

**Response**:
1. Identify attacking IP: `grep "Invalid tenant_id" logs | grep -oP 'ip":\K[^"]+'`
2. Block IP at firewall level
3. Review all requests from that IP
4. Check for data exfiltration attempts
5. Update WAF rules if needed

### Missing Tenant Context

**Detection**: Repeated occurrences for same user

**Response**:
1. Identify affected user
2. Check user's tenant_id assignment
3. Review user's authentication flow
4. Verify TenantContext service is working
5. Fix user's tenant assignment if needed

---

## 📋 Compliance & Audit

### GDPR Compliance

- **Data Retention**: Audit logs retained for 365 days
- **PII Redaction**: User emails redacted via RedactSensitiveData processor
- **Right to Access**: Users can request their audit logs
- **Right to Erasure**: Logs anonymized after user deletion

### SOC 2 Compliance

- **Access Logging**: All superadmin access logged
- **Change Tracking**: All scope bypass attempts logged
- **Incident Response**: Documented procedures above
- **Regular Reviews**: Monthly security log reviews

### Audit Trail Requirements

All logs include:
- Timestamp (ISO 8601 format)
- User ID (if authenticated)
- IP address
- User agent
- Action performed
- Result (success/failure)

---

## 🔄 Regular Maintenance

### Daily
- Review security report
- Check for anomalies
- Verify monitoring is active

### Weekly
- Analyze trends in bypass attempts
- Review cache hit rates
- Update alert thresholds if needed

### Monthly
- Full security log review
- Update monitoring dashboards
- Test incident response procedures

### Quarterly
- Security audit
- Penetration testing
- Update threat model

---

## 📞 Contact Information

**Security Team**: security@example.com  
**On-Call**: +1-XXX-XXX-XXXX  
**Incident Reporting**: https://security.example.com/report  

---

**Last Updated**: 2024-11-26  
**Next Review**: 2025-02-26
