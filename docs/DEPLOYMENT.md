# Deployment Guide

This document covers the deployment process for the Uffizi Ticket App to production on Hostinger shared hosting.

## Table of Contents

- [Overview](#overview)
- [Prerequisites](#prerequisites)
- [GitHub Secrets Configuration](#github-secrets-configuration)
- [Automated Deployment](#automated-deployment)
- [Manual Deployment](#manual-deployment)
- [Post-Deployment Verification](#post-deployment-verification)
- [Rollback Procedure](#rollback-procedure)
- [Environment Variables](#environment-variables)
- [Troubleshooting](#troubleshooting)

## Overview

| Component | Details |
|-----------|---------|
| **Production URL** | https://uffizi.deetech.cc |
| **Hosting** | Hostinger Shared Hosting |
| **PHP Version** | 8.2 (at `/opt/alt/php82/usr/bin/php`) |
| **Database** | MySQL |
| **CI/CD** | GitHub Actions |

## Prerequisites

Before deploying, ensure you have:

1. SSH access to the production server
2. GitHub repository access with admin permissions
3. All required GitHub secrets configured
4. A working local development environment for testing

## GitHub Secrets Configuration

The following secrets must be configured in GitHub repository settings:

| Secret Name | Description | Example |
|-------------|-------------|---------|
| `SSH_PRIVATE_KEY` | Private SSH key for server access | `-----BEGIN OPENSSH PRIVATE KEY-----...` |
| `SSH_HOST` | Server IP address | `82.25.82.111` |
| `SSH_PORT` | SSH port number | `65002` |
| `SSH_USER` | SSH username | `u803853690` |
| `DEPLOY_PATH` | Deployment directory path | `/home/u803853690/domains/deetech.cc/public_html/uffizi` |
| `SENTRY_DSN_FRONTEND` | Sentry DSN for frontend | `https://xxx@sentry.io/xxx` |
| `SENTRY_DSN_BACKEND` | Sentry DSN for backend | `https://xxx@sentry.io/xxx` |

### Setting Up SSH Key

1. Generate a new SSH key pair (if needed):
   ```bash
   ssh-keygen -t ed25519 -C "github-actions@uffizi" -f deploy_key
   ```

2. Add the public key to the server's `~/.ssh/authorized_keys`

3. Add the private key content to GitHub secrets as `SSH_PRIVATE_KEY`

## Automated Deployment

### Using GitHub Actions

1. Go to GitHub repository > Actions > Deploy
2. Click "Run workflow"
3. Select environment: `production`
4. Optionally check "Skip tests" (not recommended)
5. Click "Run workflow"

The workflow will:
1. Run tests (unless skipped)
2. Build the frontend
3. Deploy backend files via rsync
4. Deploy frontend build
5. Run migrations
6. Clear and rebuild caches
7. Perform health check

### Triggering from CLI

```bash
gh workflow run deploy.yml \
  --ref main \
  -f environment=production \
  -f skip_tests=false
```

## Manual Deployment

If automated deployment fails or for hotfixes:

### 1. Build Frontend Locally

```bash
cd frontend
npm ci
npm run build
```

### 2. Backup Production Database

```bash
ssh -p 65002 u803853690@82.25.82.111 \
  "cd /home/u803853690/domains/deetech.cc/public_html/uffizi/backend && \
   mysqldump -u \$DB_USERNAME -p\$DB_PASSWORD \$DB_DATABASE > backup_$(date +%Y%m%d_%H%M%S).sql"
```

### 3. Upload Backend Files

```bash
# Upload specific files
scp -P 65002 backend/app/Http/Controllers/BookingController.php \
  u803853690@82.25.82.111:/home/u803853690/domains/deetech.cc/public_html/uffizi/backend/app/Http/Controllers/

# Or sync entire backend (excluding sensitive files)
rsync -avz --delete \
  -e "ssh -p 65002" \
  --exclude='.env' \
  --exclude='storage/logs/*' \
  --exclude='vendor' \
  backend/ u803853690@82.25.82.111:/home/u803853690/domains/deetech.cc/public_html/uffizi/backend/
```

### 4. Upload Frontend Build

```bash
# Upload index.html
scp -P 65002 frontend/dist/index.html \
  u803853690@82.25.82.111:/home/u803853690/domains/deetech.cc/public_html/uffizi/

# Upload assets
rsync -avz --delete \
  -e "ssh -p 65002" \
  frontend/dist/assets/ u803853690@82.25.82.111:/home/u803853690/domains/deetech.cc/public_html/uffizi/assets/
```

### 5. Run Post-Deployment Tasks

```bash
ssh -p 65002 u803853690@82.25.82.111 "
  cd /home/u803853690/domains/deetech.cc/public_html/uffizi/backend && \
  /opt/alt/php82/usr/bin/php -d memory_limit=512M /usr/local/bin/composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev && \
  /opt/alt/php82/usr/bin/php artisan migrate --force && \
  /opt/alt/php82/usr/bin/php artisan config:cache && \
  /opt/alt/php82/usr/bin/php artisan route:cache && \
  /opt/alt/php82/usr/bin/php artisan view:cache
"
```

## Post-Deployment Verification

### Checklist

- [ ] Application loads at https://uffizi.deetech.cc
- [ ] Login works correctly
- [ ] Dashboard displays bookings
- [ ] Bokun sync functions properly
- [ ] No new errors in Sentry
- [ ] API health check passes: `curl https://uffizi.deetech.cc/api/up`

### Quick Health Check

```bash
# Check if site is responding
curl -s -o /dev/null -w "%{http_code}" https://uffizi.deetech.cc/

# Check API health endpoint
curl https://uffizi.deetech.cc/api/up

# Check Laravel logs for errors
ssh -p 65002 u803853690@82.25.82.111 \
  "tail -50 /home/u803853690/domains/deetech.cc/public_html/uffizi/backend/storage/logs/laravel.log"
```

## Rollback Procedure

If deployment causes issues:

### 1. Identify the Issue

```bash
# Check recent logs
ssh -p 65002 u803853690@82.25.82.111 \
  "tail -100 /home/u803853690/domains/deetech.cc/public_html/uffizi/backend/storage/logs/laravel.log"

# Check Sentry for new errors
```

### 2. Restore Database (if needed)

```bash
ssh -p 65002 u803853690@82.25.82.111 "
  cd /home/u803853690/domains/deetech.cc/public_html/uffizi/backend && \
  # List available backups
  ls -la backup_*.sql && \
  # Restore from backup (replace with actual filename)
  mysql -u \$DB_USERNAME -p\$DB_PASSWORD \$DB_DATABASE < backup_YYYYMMDD_HHMMSS.sql
"
```

### 3. Revert Code Changes

```bash
# Option A: Re-deploy previous commit
gh workflow run deploy.yml \
  --ref <previous-commit-sha> \
  -f environment=production \
  -f skip_tests=true

# Option B: Manual revert using git
git revert HEAD
git push origin main
# Then trigger deployment
```

### 4. Clear Caches

```bash
ssh -p 65002 u803853690@82.25.82.111 "
  cd /home/u803853690/domains/deetech.cc/public_html/uffizi/backend && \
  /opt/alt/php82/usr/bin/php artisan optimize:clear
"
```

## Environment Variables

### Production .env Requirements

The following environment variables must be set in production:

#### Application
```env
APP_NAME=UffiziDashboard
APP_ENV=production
APP_DEBUG=false
APP_URL=https://uffizi.deetech.cc
APP_TIMEZONE=Europe/Rome
```

#### Database
```env
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=u803853690_uffizi_tickets
DB_USERNAME=u803853690_uffizi
DB_PASSWORD=<database-password>
```

#### Caching (Production)
```env
LOG_CHANNEL=stack
LOG_STACK=single,sentry
LOG_LEVEL=warning
CACHE_STORE=file
SESSION_DRIVER=file
```

#### Bokun API
```env
BOKUN_ACCESS_KEY=<bokun-access-key>
BOKUN_SECRET_KEY=<bokun-secret-key>
BOKUN_BASE_URL=https://api.bokun.io
UFFIZI_PRODUCT_IDS=961802,961801,962885,962886,1130528,1135055
```

#### Twilio (Messaging)
```env
TWILIO_ACCOUNT_SID=<twilio-sid>
TWILIO_AUTH_TOKEN=<twilio-token>
TWILIO_WHATSAPP_FROM=+14155238886
TWILIO_SMS_FROM=<twilio-phone>
TWILIO_STATUS_CALLBACK_URL=https://uffizi.deetech.cc/api/webhooks/twilio/status
```

#### AWS S3 (Attachments)
```env
AWS_ACCESS_KEY_ID=<aws-key>
AWS_SECRET_ACCESS_KEY=<aws-secret>
AWS_DEFAULT_REGION=eu-west-1
AWS_BUCKET=<bucket-name>
```

#### Email
```env
MAIL_MAILER=smtp
MAIL_HOST=<smtp-host>
MAIL_PORT=587
MAIL_USERNAME=<mail-username>
MAIL_PASSWORD=<mail-password>
MAIL_FROM_ADDRESS=tickets@florencewithlocals.com
```

#### Sentry
```env
SENTRY_LARAVEL_DSN=<sentry-dsn>
SENTRY_TRACES_SAMPLE_RATE=0.1
```

## Troubleshooting

### Common Issues

#### 1. Deployment Fails at SSH Connection

**Symptoms:** "Permission denied" or "Connection refused"

**Solutions:**
- Verify SSH_PRIVATE_KEY secret is correct
- Check if server IP has changed
- Ensure public key is in server's authorized_keys
- Verify SSH port is correct (65002)

#### 2. Migrations Fail

**Symptoms:** Database error during migration

**Solutions:**
```bash
# Connect and check migration status
ssh -p 65002 u803853690@82.25.82.111 "
  cd /home/u803853690/domains/deetech.cc/public_html/uffizi/backend && \
  /opt/alt/php82/usr/bin/php artisan migrate:status
"

# Rollback if needed
/opt/alt/php82/usr/bin/php artisan migrate:rollback --step=1
```

#### 3. 500 Error After Deployment

**Symptoms:** Application returns HTTP 500

**Solutions:**
1. Check Laravel logs for specific error
2. Clear all caches:
   ```bash
   /opt/alt/php82/usr/bin/php artisan optimize:clear
   ```
3. Ensure storage directories have correct permissions:
   ```bash
   chmod -R 775 storage bootstrap/cache
   ```

#### 4. Assets Not Loading

**Symptoms:** CSS/JS files return 404

**Solutions:**
- Verify frontend build completed successfully
- Check that assets were uploaded to correct directory
- Clear browser cache
- Check `.htaccess` rules

#### 5. Composer Install Fails

**Symptoms:** "Allowed memory size exhausted"

**Solutions:**
```bash
# Use memory limit flag
/opt/alt/php82/usr/bin/php -d memory_limit=512M /usr/local/bin/composer install
```

### Getting Help

1. Check the logs:
   - Laravel: `storage/logs/laravel.log`
   - Sentry: https://sentry.io

2. Review GitHub Actions logs for deployment errors

3. Consult the main documentation in `CLAUDE.md`

---

Last updated: January 2026
