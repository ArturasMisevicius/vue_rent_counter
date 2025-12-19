#!/bin/bash

# Security Deployment Checklist Script
# Validates security configuration before production deployment
# Usage: ./scripts/security-deployment-check.sh

set -e

echo "🔒 Security Deployment Checklist"
echo "================================="

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Track failures
FAILURES=0

# Function to check and report
check_config() {
    local description="$1"
    local condition="$2"
    local value="$3"
    local expected="$4"
    
    echo -n "Checking $description... "
    
    if [ "$condition" = "equals" ] && [ "$value" = "$expected" ]; then
        echo -e "${GREEN}✓${NC}"
    elif [ "$condition" = "not_equals" ] && [ "$value" != "$expected" ]; then
        echo -e "${GREEN}✓${NC}"
    elif [ "$condition" = "not_empty" ] && [ -n "$value" ]; then
        echo -e "${GREEN}✓${NC}"
    elif [ "$condition" = "empty" ] && [ -z "$value" ]; then
        echo -e "${GREEN}✓${NC}"
    elif [ "$condition" = "exists" ] && [ -f "$value" ]; then
        echo -e "${GREEN}✓${NC}"
    else
        echo -e "${RED}✗${NC}"
        echo "  Expected: $expected, Got: $value"
        FAILURES=$((FAILURES + 1))
    fi
}

# Load environment variables
if [ -f .env ]; then
    source .env
else
    echo -e "${RED}✗ .env file not found${NC}"
    exit 1
fi

echo ""
echo "1. Environment Security"
echo "----------------------"

check_config "APP_DEBUG is false" "equals" "$APP_DEBUG" "false"
check_config "APP_ENV is production" "equals" "$APP_ENV" "production"
check_config "APP_KEY is set" "not_empty" "$APP_KEY" ""

echo ""
echo "2. HTTPS Configuration"
echo "---------------------"

check_config "FORCE_HTTPS is enabled" "equals" "$FORCE_HTTPS" "true"
check_config "SESSION_SECURE_COOKIE is enabled" "equals" "$SESSION_SECURE_COOKIE" "true"

echo ""
echo "3. Database Security"
echo "-------------------"

# Check for weak passwords
if [[ "$DB_PASSWORD" == *"password"* ]] || [[ "$DB_PASSWORD" == *"123"* ]] || [[ "$DB_PASSWORD" == *"admin"* ]]; then
    echo -e "${RED}✗ Weak database password detected${NC}"
    FAILURES=$((FAILURES + 1))
else
    echo -e "${GREEN}✓ Database password appears strong${NC}"
fi

check_config "DB_HOST is not localhost in production" "not_equals" "$DB_HOST" "localhost"

echo ""
echo "4. Security Features"
echo "-------------------"

check_config "SECURITY_AUDIT_ENABLED" "equals" "$SECURITY_AUDIT_ENABLED" "true"
check_config "RATE_LIMITING_ENABLED" "equals" "$RATE_LIMITING_ENABLED" "true"
check_config "PII_REDACTION_ENABLED" "equals" "$PII_REDACTION_ENABLED" "true"
check_config "SECURITY_MONITORING_ENABLED" "equals" "$SECURITY_MONITORING_ENABLED" "true"

echo ""
echo "5. Session Security"
echo "------------------"

if [ -n "$SESSION_LIFETIME" ] && [ "$SESSION_LIFETIME" -le 120 ]; then
    echo -e "${GREEN}✓ Session lifetime is secure (${SESSION_LIFETIME} minutes)${NC}"
else
    echo -e "${RED}✗ Session lifetime too long or not set${NC}"
    FAILURES=$((FAILURES + 1))
fi

check_config "SESSION_SAME_SITE is strict" "equals" "$SESSION_SAME_SITE" "strict"

echo ""
echo "6. File Permissions"
echo "------------------"

# Check .env permissions
ENV_PERMS=$(stat -c "%a" .env 2>/dev/null || echo "000")
if [ "$ENV_PERMS" = "600" ] || [ "$ENV_PERMS" = "644" ]; then
    echo -e "${GREEN}✓ .env file permissions are secure ($ENV_PERMS)${NC}"
else
    echo -e "${RED}✗ .env file permissions are too open ($ENV_PERMS)${NC}"
    FAILURES=$((FAILURES + 1))
fi

# Check storage permissions
if [ -d "storage" ]; then
    STORAGE_PERMS=$(stat -c "%a" storage 2>/dev/null || echo "000")
    if [ "$STORAGE_PERMS" = "755" ]; then
        echo -e "${GREEN}✓ Storage directory permissions are correct${NC}"
    else
        echo -e "${YELLOW}⚠ Storage directory permissions: $STORAGE_PERMS (should be 755)${NC}"
    fi
fi

echo ""
echo "7. Security Headers Test"
echo "-----------------------"

if command -v curl >/dev/null 2>&1; then
    if [ -n "$APP_URL" ]; then
        echo "Testing security headers for $APP_URL..."
        
        # Test for security headers
        HEADERS=$(curl -I -s "$APP_URL" 2>/dev/null || echo "")
        
        if echo "$HEADERS" | grep -q "X-Frame-Options"; then
            echo -e "${GREEN}✓ X-Frame-Options header present${NC}"
        else
            echo -e "${RED}✗ X-Frame-Options header missing${NC}"
            FAILURES=$((FAILURES + 1))
        fi
        
        if echo "$HEADERS" | grep -q "Content-Security-Policy"; then
            echo -e "${GREEN}✓ Content-Security-Policy header present${NC}"
        else
            echo -e "${RED}✗ Content-Security-Policy header missing${NC}"
            FAILURES=$((FAILURES + 1))
        fi
        
        if echo "$HEADERS" | grep -q "Strict-Transport-Security"; then
            echo -e "${GREEN}✓ HSTS header present${NC}"
        else
            echo -e "${YELLOW}⚠ HSTS header missing (may be handled by proxy)${NC}"
        fi
    else
        echo -e "${YELLOW}⚠ APP_URL not set, skipping header tests${NC}"
    fi
else
    echo -e "${YELLOW}⚠ curl not available, skipping header tests${NC}"
fi

echo ""
echo "8. Laravel Security Commands"
echo "---------------------------"

# Check if security commands are registered
if php artisan list | grep -q "tokens:prune"; then
    echo -e "${GREEN}✓ Token pruning command available${NC}"
else
    echo -e "${RED}✗ Token pruning command not found${NC}"
    FAILURES=$((FAILURES + 1))
fi

if php artisan list | grep -q "security:monitor"; then
    echo -e "${GREEN}✓ Security monitoring command available${NC}"
else
    echo -e "${RED}✗ Security monitoring command not found${NC}"
    FAILURES=$((FAILURES + 1))
fi

echo ""
echo "9. Database Migrations"
echo "---------------------"

# Check if security migrations are applied
if php artisan migrate:status | grep -q "add_security_indexes"; then
    echo -e "${GREEN}✓ Security indexes migration applied${NC}"
else
    echo -e "${RED}✗ Security indexes migration not applied${NC}"
    FAILURES=$((FAILURES + 1))
fi

echo ""
echo "10. Composer Security"
echo "--------------------"

# Check for security advisories
if command -v composer >/dev/null 2>&1; then
    echo "Checking for security advisories..."
    if composer audit --no-dev 2>/dev/null; then
        echo -e "${GREEN}✓ No known security vulnerabilities${NC}"
    else
        echo -e "${RED}✗ Security vulnerabilities found in dependencies${NC}"
        FAILURES=$((FAILURES + 1))
    fi
else
    echo -e "${YELLOW}⚠ Composer not available, skipping security audit${NC}"
fi

echo ""
echo "================================="
echo "Security Check Summary"
echo "================================="

if [ $FAILURES -eq 0 ]; then
    echo -e "${GREEN}✅ All security checks passed!${NC}"
    echo "The application is ready for production deployment."
    exit 0
else
    echo -e "${RED}❌ $FAILURES security check(s) failed!${NC}"
    echo "Please fix the issues above before deploying to production."
    exit 1
fi