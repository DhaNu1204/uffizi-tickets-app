# Deployment Guide for uffizi.deetech.cc

**Status**: Fully Operational (Last deployed: Jan 4, 2026)

## Server Details

| Setting | Value |
|---------|-------|
| **URL** | https://uffizi.deetech.cc |
| **Host** | Hostinger Shared Hosting |
| **Directory** | /home/u803853690/domains/deetech.cc/public_html/uffizi |
| **Database** | u803853690_uffizi_tickets |
| **SSH** | `ssh -p 65002 u803853690@82.25.82.111` |
| **PHP Version** | 8.2 (`/opt/alt/php82/usr/bin/php`) |

## Folder Structure on Server

```
/uffizi/
├── .htaccess              (from deploy/.htaccess)
├── index.html             (from frontend/dist/)
├── assets/                (from frontend/dist/assets/)
├── backend/
│   ├── app/
│   ├── bootstrap/
│   ├── config/
│   ├── database/
│   ├── public/
│   │   ├── .htaccess
│   │   └── index.php
│   ├── resources/
│   ├── routes/
│   ├── storage/
│   ├── vendor/
│   ├── .env               (production environment)
│   ├── artisan
│   └── composer.json
```

## Deployment Steps

### 1. Build Frontend
```bash
cd frontend
npm run build
```

### 2. Upload Files

**Frontend files** (upload to `/uffizi/`):
- `frontend/dist/index.html` → `/uffizi/index.html`
- `frontend/dist/assets/` → `/uffizi/assets/`

**Backend files** (upload to `/uffizi/backend/`):
- Upload changed PHP files to corresponding paths
- Don't overwrite `.env` unless updating configuration

**Root .htaccess**:
- Upload `deploy/.htaccess` to `/uffizi/.htaccess`

### 3. SSH Commands (After Upload)

```bash
# Connect to server
ssh -p 65002 u803853690@82.25.82.111

# Navigate to backend
cd /home/u803853690/domains/deetech.cc/public_html/uffizi/backend

# Run migrations (IMPORTANT: use PHP 8.2)
/opt/alt/php82/usr/bin/php artisan migrate --force

# Clear caches
/opt/alt/php82/usr/bin/php artisan config:clear
/opt/alt/php82/usr/bin/php artisan cache:clear
/opt/alt/php82/usr/bin/php artisan route:clear
/opt/alt/php82/usr/bin/php artisan view:clear

# Or clear all at once
/opt/alt/php82/usr/bin/php artisan optimize:clear
```

### 4. Verify Deployment
1. Visit https://uffizi.deetech.cc
2. Login with credentials
3. Verify bookings load
4. Test sync functionality

## First-Time Setup

### Create Admin User
```bash
# SSH to server
ssh -p 65002 u803853690@82.25.82.111

# Navigate to backend
cd /home/u803853690/domains/deetech.cc/public_html/uffizi/backend

# Open tinker
/opt/alt/php82/usr/bin/php artisan tinker

# Create user (in tinker)
App\Models\User::create([
    'name' => 'Admin',
    'email' => 'admin@example.com',
    'password' => bcrypt('your-secure-password')
]);
```

### Set Folder Permissions
```bash
chmod 755 storage/
chmod 755 bootstrap/cache/
chmod 666 storage/logs/laravel.log
```

## Quick Commands Reference

| Task | Command |
|------|---------|
| Run migrations | `/opt/alt/php82/usr/bin/php artisan migrate --force` |
| Clear all caches | `/opt/alt/php82/usr/bin/php artisan optimize:clear` |
| Sync bookings | `/opt/alt/php82/usr/bin/php artisan bokun:sync --limit=50` |
| Full sync | `/opt/alt/php82/usr/bin/php artisan bokun:sync --full` |
| Debug booking | `/opt/alt/php82/usr/bin/php artisan booking:debug {code}` |
| View logs | `tail -100 storage/logs/laravel.log` |
| Open tinker | `/opt/alt/php82/usr/bin/php artisan tinker` |

## Troubleshooting

### 500 Internal Server Error
1. Check Laravel logs:
   ```bash
   tail -100 storage/logs/laravel.log
   ```
2. Verify storage folder is writable:
   ```bash
   chmod 755 storage/
   chmod 755 storage/logs/
   ```
3. Clear configuration cache:
   ```bash
   /opt/alt/php82/usr/bin/php artisan config:clear
   ```

### API Not Working
1. Verify `.htaccess` files are uploaded:
   - `/uffizi/.htaccess` (root)
   - `/uffizi/backend/public/.htaccess`
2. Check mod_rewrite is enabled in Hostinger

### Database Connection Error
1. Verify credentials in `backend/.env`:
   ```
   DB_HOST=localhost
   DB_DATABASE=u803853690_uffizi_tickets
   DB_USERNAME=u803853690_uffizi
   DB_PASSWORD=your_password
   ```
2. Test connection via tinker

### Authentication Issues
1. Ensure using Bearer token auth (not cookie-based)
2. Check `bootstrap/app.php` doesn't use `statefulApi()`
3. Verify Sanctum is configured correctly

### Permission Denied on Log File
```bash
chmod 666 storage/logs/laravel.log
chmod 775 storage/logs/
```

## Environment Configuration

### Production .env (backend/.env)
```env
APP_NAME=Uffizi
APP_ENV=production
APP_KEY=base64:...
APP_DEBUG=false
APP_URL=https://uffizi.deetech.cc

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=u803853690_uffizi_tickets
DB_USERNAME=u803853690_uffizi
DB_PASSWORD=your_password

BOKUN_ACCESS_KEY=your_key
BOKUN_SECRET_KEY=your_secret
BOKUN_BASE_URL=https://api.bokun.io
UFFIZI_PRODUCT_IDS=961802,961801,962885,962886,1130528,1135055
```

### Frontend .env.production
```env
VITE_API_URL=https://uffizi.deetech.cc/api
```

## Rollback Procedure

If deployment fails:
1. Keep backup of previous `assets/` folder
2. Revert by uploading previous build files
3. Clear caches after rollback:
   ```bash
   /opt/alt/php82/usr/bin/php artisan optimize:clear
   ```

## Database Backup

### Export from Production
```bash
# SSH to server
ssh -p 65002 u803853690@82.25.82.111
cd /home/u803853690/domains/deetech.cc/public_html/uffizi/backend
mysqldump -u u803853690_uffizi -p u803853690_uffizi_tickets > backup.sql
```

### Download Backup
```bash
scp -P 65002 u803853690@82.25.82.111:/home/u803853690/domains/deetech.cc/public_html/uffizi/backend/backup.sql ./
```

## Recent Deployments

### Jan 4, 2026
- **Changes**: Updated CORS configuration, frontend build
- **Backup**: `backup_before_deploy_20260104_210032.sql` (178KB)
- **Frontend Assets**: `index-B35f6sNz.js`, `index-BpckroFc.css`
- **Status**: Successful - 426 bookings verified

### Deployment Verification Commands
```bash
# Test frontend loads (should return 200)
ssh -p 65002 u803853690@82.25.82.111 "curl -s -o /dev/null -w '%{http_code}' https://uffizi.deetech.cc/"

# Test API works (should return JSON error for auth)
ssh -p 65002 u803853690@82.25.82.111 "curl -s -X POST https://uffizi.deetech.cc/api/login -H 'Content-Type: application/json' -H 'Accept: application/json' -d '{\"email\":\"test\",\"password\":\"test\"}'"

# Check database connection
ssh -p 65002 u803853690@82.25.82.111 "cd /home/u803853690/domains/deetech.cc/public_html/uffizi/backend && /opt/alt/php82/usr/bin/php artisan tinker --execute=\"echo App\\\\Models\\\\Booking::count() . ' bookings'\""
```
