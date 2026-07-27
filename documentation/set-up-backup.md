# Setting Up Laravel Task Scheduler on Hostinger (Option 2)

## Step 1: Verify Your Laravel Schedule Configuration

Your `bootstrap/app.php` is already configured correctly (lines 36-48):
- `attendance:close-stale-ins` at 00:05
- `backup:run --only-db` at 01:00
- `backup:clean` at 01:30

All times are in Asia/Manila timezone.

## Step 2: Update .env for Production

Ensure these settings in your `.env`:

```env
APP_ENV=production
APP_DEBUG=false

DB_CONNECTION=mysql
DB_HOST=your-db-host.hostinger.com
DB_PORT=3306
DB_DATABASE=your_database_name
DB_USERNAME=your_db_user
DB_PASSWORD=your_db_password

BACKUP_NAME=pantas-db
BACKUP_ARCHIVE_PASSWORD=your_secure_password_here
MYSQL_DUMP_BINARY_PATH=/usr/bin
```

## Step 3: Create Directories (if not exists)

SSH into Hostinger or use File Manager:
```bash
mkdir -p storage/app/private/pantas-db
mkdir -p storage/app/backup-temp
chmod -R 775 storage/app/private
chmod -R 775 storage/app/backup-temp
```

## Step 4: Set Up the Cron Job in Hostinger

### **Method A: Using hPanel (GUI)**

1. Log into **Hostinger hPanel**
2. Go to **Advanced** → **Cron Jobs**
3. Click **Add New Cron Job**
4. Set:
   - **Common schedules**: Every minute (`* * * * *`)
   - **Command**: 
   ```
   cd /home/USERNAME/domains/YOURDOMAIN.com/public_html && /usr/local/bin/php artisan schedule:run --no-interaction
   ```
5. Save

### **Method B: Using SSH**

```bash
(crontab -l 2>/dev/null; echo "* * * * * cd /home/USERNAME/domains/YOURDOMAIN.com/public_html && /usr/local/bin/php artisan schedule:run --no-interaction") | crontab -
```

**Replace:**
- `USERNAME` - your Hostinger account username
- `YOURDOMAIN.com` - your actual domain
- `/usr/local/bin/php` - verify path with `which php` in SSH

## Step 5: Find Your PHP Path

SSH into Hostinger and run:
```bash
which php
```

Common Hostinger paths:
- `/usr/local/bin/php`
- `/usr/bin/php`
- `/opt/alt/php83/usr/bin/php` (for PHP 8.3)

Use the full path in your cron command.

## Step 6: Verify Cron Job is Running

After a few minutes, check if it's set up:
```bash
crontab -l
```
You should see your cron job listed.

## Step 7: Test the Scheduler

Manually run the scheduler to test:
```bash
cd /home/USERNAME/domains/YOURDOMAIN.com/public_html
/usr/local/bin/php artisan schedule:run --no-interaction
```

You should see output like:
```
Running scheduled command for: "2026-07-16 01:00:00"
Running command: '/usr/local/bin/php' 'artisan' backup:run --only-db
```

Wait until 1:00 AM Manila time, or temporarily modify the schedule for testing:
```php
// Temporary test - change to every minute for testing
$schedule->command('backup:run --only-db')->everyMinute();
```

## Step 8: Monitor Execution

### **Check Laravel Logs**
```bash
tail -f storage/logs/laravel.log
```

### **Check Backup Directory**
```bash
ls -lh storage/app/private/pantas-db/
```

### **Check if Cron is Running**
```bash
grep CRON /var/log/syslog 2>/dev/null || grep CRON /var/log/messages 2>/dev/null
```
On Hostinger, cron logs may not be directly accessible.

## Step 9: Set Up Email Notifications (Optional but Recommended)

Update `config/backup.php` (lines 211-218):
```php
'mail' => [
    'to' => 'your-email@example.com',
    'from' => [
        'address' => 'support@pantas.org',
        'name' => 'USM PANTAS',
    ],
],
```

Also update MAIL settings in `.env` for Hostinger SMTP.

## Step 10: Secure Your Backup Directory

Add to `.htaccess` in `storage/app/private/pantas-db/`:
```apache
<Files "*.zip">
    Order Allow,Deny
    Deny from all
</Files>
```

## Troubleshooting

### **Backup Not Running**
1. Check PHP path is correct
2. Verify file permissions: `chmod -R 775 storage/`
3. Ensure `artisan` file exists in public_html root
4. Check Laravel logs for errors

### **mysqldump Not Found**
Update `MYSQL_DUMP_BINARY_PATH` in `.env`:
```bash
which mysqldump
```
Use the directory path (not the full file path).

### **Permission Denied**
```bash
chmod -R 775 storage/
chown -R USERNAME:GROUP storage/
```

### **Backup Too Large / Timeout**
Increase `max_execution_time` in `.htaccess`:
```apache
<IfModule mod_php.c>
    php_value max_execution_time 300
</IfModule>
```

## Verification Checklist

```
[ ] Cron job added in Hostinger hPanel
[ ] PHP path verified (run: which php)
[ ] Directories created with proper permissions
[ ] Manual test: php artisan schedule:run completed successfully
[ ] Backup file appears in storage/app/private/pantas-db/
[ ] Email notifications configured (optional)
[ ] Laravel logs showing no errors
```

After setup, the backup will run automatically every day at 1:00 AM Manila time. You'll receive email notifications if configured. Monitor the first few days to ensure everything works correctly.