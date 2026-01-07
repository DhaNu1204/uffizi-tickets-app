# Deployment Guide

This guide covers deploying the Uffizi Ticket Dashboard API to a production environment.

## Pre-Deployment Checklist

- [ ] PHP 8.2+ installed with required extensions
- [ ] MySQL 8.0+ database server
- [ ] Composer installed
- [ ] Web server (Nginx or Apache) configured
- [ ] SSL certificate for HTTPS
- [ ] Bokun API credentials ready

## Server Requirements

### PHP Extensions
- BCMath
- Ctype
- Fileinfo
- JSON
- Mbstring
- OpenSSL
- PDO (with MySQL driver)
- Tokenizer
- XML

### Recommended Server Specs
- 2 CPU cores minimum
- 2GB RAM minimum
- 20GB storage

## Deployment Steps

### 1. Clone Repository
```bash
cd /var/www
git clone <repository-url> uffizi-dashboard
cd uffizi-dashboard/backend
```

### 2. Install Dependencies
```bash
composer install --optimize-autoloader --no-dev
```

### 3. Configure Environment
```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` with production values:
```env
APP_NAME=UffiziDashboard
APP_ENV=production
APP_DEBUG=false
APP_URL=https://api.yourdomain.com

DB_CONNECTION=mysql
DB_HOST=your-db-host
DB_PORT=3306
DB_DATABASE=uffizi_production
DB_USERNAME=uffizi_user
DB_PASSWORD=secure_password_here

BOKUN_ACCESS_KEY=your_production_key
BOKUN_SECRET_KEY=your_production_secret
BOKUN_BASE_URL=https://api.bokun.io
UFFIZI_PRODUCT_IDS=961802,961801,962885,962886,1130528

QUEUE_CONNECTION=database
CACHE_STORE=database
SESSION_DRIVER=database
```

### 4. Set Permissions
```bash
chown -R www-data:www-data /var/www/uffizi-dashboard
chmod -R 755 /var/www/uffizi-dashboard
chmod -R 775 /var/www/uffizi-dashboard/backend/storage
chmod -R 775 /var/www/uffizi-dashboard/backend/bootstrap/cache
```

### 5. Run Migrations
```bash
php artisan migrate --force
```

### 6. Create Admin User
```bash
php artisan db:seed --class=AdminUserSeeder --force
```

**Important:** Change the default password immediately!

### 7. Optimize for Production
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan optimize
```

### 8. Configure Web Server

#### Nginx Configuration
```nginx
server {
    listen 80;
    listen [::]:80;
    server_name api.yourdomain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name api.yourdomain.com;

    root /var/www/uffizi-dashboard/backend/public;
    index index.php;

    ssl_certificate /etc/ssl/certs/your-cert.pem;
    ssl_certificate_key /etc/ssl/private/your-key.pem;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

#### Apache Configuration (.htaccess is included)
Ensure `mod_rewrite` is enabled.

### 9. Configure Queue Worker (Optional)

For background job processing, set up a queue worker:

#### Supervisor Configuration
```ini
[program:uffizi-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/uffizi-dashboard/backend/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/uffizi-dashboard/backend/storage/logs/worker.log
stopwaitsecs=3600
```

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start uffizi-worker:*
```

### 10. Configure Scheduled Tasks

Add to crontab (`crontab -e`):
```
* * * * * cd /var/www/uffizi-dashboard/backend && php artisan schedule:run >> /dev/null 2>&1
```

## Post-Deployment

### Configure Bokun Webhook

1. Log in to Bokun dashboard
2. Go to Settings > Webhooks
3. Add new webhook:
   - URL: `https://api.yourdomain.com/api/webhook/bokun`
   - Events: Booking created, updated, cancelled
   - Enable HMAC signature

### Import Historical Data

```bash
php artisan bookings:import --from=2024-01-01 --to=$(date +%Y-%m-%d)
```

### Change Admin Password

Log in via API and change the default password, or create a new admin user:

```bash
php artisan tinker
>>> $user = User::where('email', 'admin@uffizi-tickets.com')->first();
>>> $user->password = Hash::make('new_secure_password');
>>> $user->save();
```

## Maintenance

### Clear Caches (after updates)
```bash
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### Database Backup
```bash
mysqldump -u uffizi_user -p uffizi_production > backup_$(date +%Y%m%d).sql
```

### Log Rotation
Laravel logs are in `storage/logs/`. Configure logrotate:

```
/var/www/uffizi-dashboard/backend/storage/logs/*.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    create 0640 www-data www-data
}
```

### Webhook Cleanup
Schedule regular cleanup of old webhook logs:

```bash
# Add to crontab - run weekly
0 2 * * 0 cd /var/www/uffizi-dashboard/backend && php artisan tinker --execute="App\Models\WebhookLog::where('created_at', '<', now()->subDays(30))->where('status', 'processed')->delete();"
```

## Monitoring

### Health Check Endpoint
The API root returns 200 OK when healthy:
```bash
curl -I https://api.yourdomain.com
```

### Log Files
- Application logs: `storage/logs/laravel.log`
- Queue worker logs: `storage/logs/worker.log`
- Web server logs: Check Nginx/Apache logs

## Troubleshooting

### 500 Internal Server Error
1. Check `storage/logs/laravel.log`
2. Verify file permissions
3. Ensure `.env` is configured correctly

### Database Connection Failed
1. Verify MySQL is running
2. Check credentials in `.env`
3. Ensure database exists

### Webhook Not Receiving
1. Verify webhook URL in Bokun
2. Check HMAC secret matches
3. Review `webhook_logs` table

### Queue Jobs Not Processing
1. Check Supervisor is running
2. Verify `QUEUE_CONNECTION=database`
3. Check worker logs

## Security Recommendations

1. **Use HTTPS only** - Redirect all HTTP to HTTPS
2. **Firewall** - Only expose ports 80, 443, and SSH
3. **Database** - Don't expose MySQL publicly
4. **Environment** - Never commit `.env` to git
5. **Updates** - Keep PHP and dependencies updated
6. **Backups** - Regular automated backups
7. **Monitoring** - Set up error alerting
