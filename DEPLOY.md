# Deployment Guide — ADMS on Ubuntu (Apache + port 8080 + Cloudflare)

This guide deploys ADMS (Laravel 10, MySQL, Bootstrap/Vite) on Ubuntu with Apache
listening on port 8080, CommKey authentication protecting the ZKTeco device
endpoints (`/iclock/*`), and Cloudflare in front.

## Prerequisites

- Ubuntu server (22.04 LTS or newer)
- A domain name whose A record points to the server's public IP
- A Cloudflare account for the domain
- Access to the repo that will be cloned on the server

## Before you begin

All local work must be committed and pushed to `origin/main` **before** cloning on
the server. In particular:

- Vite assets (`public/build`) are gitignored — they are rebuilt on the server.
- `vendor/` and `node_modules/` are gitignored — installed on the server.
- Any uncommitted migrations/controllers/views must be pushed, or the server will run old code.

```bash
git add -A && git commit -m "deploy prep" && git push origin main
```

## 1. Install the stack

```bash
sudo apt update && sudo apt upgrade -y
```

### PHP and extensions

```bash
sudo apt install -y apache2 mysql-server \
  php php-cli php-fpm php-json php-common php-mysql php-zip php-gd \
  php-mbstring php-curl php-xml php-pear php-bcmath
php -v   # requires >= 8.1
```

### Composer

```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
composer --version
```

### Node.js (via NVM)

```bash
curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.3/install.sh | bash
export NVM_DIR="$([ -z "${XDG_CONFIG_HOME-}" ] && printf %s "${HOME}/.nvm" || printf %s "${XDG_CONFIG_HOME}/nvm")"
[ -s "$NVM_DIR/nvm.sh" ] && \. "$NVM_DIR/nvm.sh"
source ~/.bashrc
nvm install --lts && nvm use --lts && nvm alias default 'lts/*'
```

## 2. Configure MySQL

```bash
sudo mysql_secure_installation
```

Create the database and a dedicated user:

```bash
sudo mysql -u root
```

```sql
CREATE DATABASE adms CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'adms'@'localhost' IDENTIFIED BY 'STRONG_PASSWORD';
GRANT ALL PRIVILEGES ON adms.* TO 'adms'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

## 3. Clone and install the app

```bash
sudo mkdir -p /var/www && cd /var/www
sudo git clone <repo_url> adms
cd adms

sudo composer install --no-dev --optimize-autoloader
npm install
npm run build
```

## 4. Configure the environment

```bash
cp .env.example .env
nano .env
```

Set the following values:

```
APP_NAME=ADMS
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=http://your-domain.com:8080

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=adms
DB_USERNAME=adms
DB_PASSWORD=STRONG_PASSWORD

QUEUE_CONNECTION=database

# Keep device I/O off the hot path. Full per-request debug dumps and DB
# logging are opt-in; enable only while troubleshooting a specific device.
ICLOCK_DEBUG=false
ICLOCK_LOG_HANDSHAKES=false
ICLOCK_LOG_PUNCHES=false
```

Generate the app key and run migrations/seeders:

```bash
php artisan key:generate
php artisan migrate --force
php artisan db:seed --force
```

> Note: `QUEUE_CONNECTION=database` moves attendance-staging processing off the
> device HTTP request into the `jobs` table, so device endpoints respond
> instantly regardless of how many devices are pushing. Run queue workers (see
> [section 5b](#5b-queue-workers-and-scheduler) below). If no worker is running,
> the `attendance:process-staging` command is scheduled as a fallback and must
> be triggered by cron (see below).

## 5. Cache config and fix permissions

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache

sudo chown -R www-data:www-data storage bootstrap/cache
```

## 5b. Queue workers and scheduler

Attendance staging is processed by background queue workers. Run **at least one
worker per expected number of simultaneously-pushing devices** (a common choice
is 4–8). The worker pulls batches from the `jobs` table and processes them
concurrently without blocking the device HTTP endpoints.

Create a systemd service:

```bash
sudo nano /etc/systemd/system/adms-queue.service
```

```ini
[Unit]
Description=ADMS queue worker
After=network.target mysql.service

[Service]
User=www-data
Group=www-data
WorkingDirectory=/var/www/adms
ExecStart=/usr/bin/php /var/www/adms/artisan queue:work database --tries=3 --max-time=3600
Restart=always
RestartSec=5

[Install]
WantedBy=multi-user.target
```

For multiple workers, start N copies with `systemctl` template units or
Supervisor. Example with `supervisord`:

```ini
[program:adms-queue]
command=php /var/www/adms/artisan queue:work database --tries=3 --max-time=3600
directory=/var/www/adms
user=www-data
numprocs=4
process_name=%(program_name)s_%(process_num)d
autorestart=true
```

Enable and start:

```bash
sudo systemctl daemon-reload
sudo systemctl enable --now adms-queue.service
# check: sudo journalctl -u adms-queue -f
```

**Scheduler fallback** — even with workers running, install the Laravel scheduler
so the `attendance:process-staging` fallback fires (reclaims rows a worker may
have missed):

```bash
crontab -e
# add:
* * * * * cd /var/www/adms && php artisan schedule:run >> /dev/null 2>&1
```

On Windows/XAMPP, start the included worker script instead:
`scripts/start-queue-workers.ps1` (launches N background workers), and create a
Task Scheduler task to run `php artisan schedule:run` every minute.

## 6. Configure Apache

Add a listener on port 8080:

```bash
echo "Listen 8080" | sudo tee -a /etc/apache2/ports.conf
```

Create the virtual host:

```bash
sudo nano /etc/apache2/sites-available/adms.conf
```

```apache
<VirtualHost *:8080>
    ServerName your-domain.com
    ServerAdmin webmaster@your-domain.com
    DocumentRoot /var/www/adms/public

    <Directory /var/www/adms/public>
        Options Indexes FollowSymLinks MultiViews
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/error.log
    CustomLog ${APACHE_LOG_DIR}/access.log combined
</VirtualHost>
```

Enable the site and modules:

```bash
sudo a2enmod rewrite
sudo a2ensite adms.conf
sudo a2dissite 000-default.conf
sudo systemctl restart apache2
```

Open the firewall:

```bash
sudo ufw allow 8080/tcp
```

## 7. Device CommKey authentication

The `/iclock/*` device endpoints are protected by the application itself using
the ZKTeco **communication key (CommKey)** — no Apache Basic Auth is required.
Each device stores its own 4–8 digit key, and attendance data is only accepted
when the `ComKey` sent by the device matches the key stored for that device.

To set a device's key:

1. Configure the communication key on the ZKTeco device (the default is
   `88888888`).
2. In the admin dashboard, open **Devices → New Devices** and press **Assign** on
   the detected device. Enter the same key in the **Comm Key** field.
3. On handshake, the server returns `IsPushComKey=1` and `PushComKey=<key>`, so
   the device sends `ComKey=<key>` on every request. Requests with a missing or
   wrong key are rejected with `ERROR: 0`.
4. Already-registered devices without a key keep handshaking but their data is
   rejected until a key is set via **Devices → Edit → Comm Key**.

## 8. Cloudflare

In Cloudflare for your domain:

1. Create an **A record** pointing to the server's public IP, with proxy enabled
   (orange cloud) so traffic routes through Cloudflare.
2. Optionally restrict direct access by allowing port 8080 only from
   [Cloudflare IP ranges](https://www.cloudflare.com/ips/) in `ufw`:

```bash
sudo ufw allow from 173.245.48.0/20 to any port 8080 proto tcp
sudo ufw allow from 103.21.244.0/22 to any port 8080 proto tcp
sudo ufw allow from 103.22.200.0/22 to any port 8080 proto tcp
sudo ufw allow from 103.31.4.0/22 to any port 8080 proto tcp
sudo ufw allow from 141.101.64.0/18 to any port 8080 proto tcp
sudo ufw allow from 108.162.192.0/18 to any port 8080 proto tcp
sudo ufw allow from 190.93.240.0/20 to any port 8080 proto tcp
sudo ufw allow from 188.114.96.0/20 to any port 8080 proto tcp
sudo ufw allow from 197.234.240.0/22 to any port 8080 proto tcp
sudo ufw allow from 198.41.128.0/17 to any port 8080 proto tcp
sudo ufw allow from 162.158.0.0/15 to any port 8080 proto tcp
sudo ufw allow from 104.16.0.0/13 to any port 8080 proto tcp
sudo ufw allow from 104.24.0.0/14 to any port 8080 proto tcp
sudo ufw allow from 172.64.0.0/13 to any port 8080 proto tcp
sudo ufw allow from 131.0.72.0/22 to any port 8080 proto tcp
sudo ufw deny 8080/tcp
```

## 9. Configure the ZKTeco device

The device (e.g. ZKTeco SpeedFace-V5L-RFID) must be pointed at the server. The
device comm settings are:

- **Server / IP**: `your-domain.com`
- **Port**: `8080`
- **CommKey / Security key**: a 4–8 digit key (e.g. `123456`), the same value
  you will enter when assigning the device in **Devices → New Devices**.

The device URL does not need credentials:

```
http://your-domain.com:8080
```

The device handshakes at `/iclock/cdata` and pushes logs there. Verify the
device registers via the "Devices pending" screen in the admin dashboard.

## 10. Verify

- Browser: `http://your-domain.com:8080` → login works.
  - Default seeded super admin: `john.doe@example.com` / `password`
- Device handshake: check `/var/log/apache2/access.log` for `GET /iclock/cdata`
  entries from the device.
- The device shows **online** in the dashboard after a handshake.
- Confirm the handshake response contains `IsPushComKey=1` and
  `PushComKey=<key>` and that attendance rows appear after the device pushes
  with a matching `ComKey`.

## 11. Updating the app

Repeat these steps after every code update from `origin/main`. Adapt each step
to the changes actually shipped.

### Local

Commit and push all work before deploying:

```bash
git add -A && git commit -m "describe the change" && git push origin main
```

### Server

```bash
cd /var/www/adms

# 1. Optional safety backup before upgrading
sudo cp -r . ../adms.backup-$(date +%F)

# 2. Pull the new code
sudo git pull origin main

# 3. Install PHP dependencies (new/changed composer.json)
sudo composer install --no-dev --optimize-autoloader

# 4. Rebuild frontend assets ONLY if JS/CSS/Vite sources changed
sudo -u www-data npm install
sudo -u www-data npm run build

# 5. Run pending migrations (new database migrations)
sudo -u www-data php artisan migrate --force

# 6. Refresh the compiled caches (config/routes/views)
sudo -u www-data php artisan optimize:clear
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache

# 7. Fix permissions (in case files were created by root)
sudo chown -R www-data:www-data storage bootstrap/cache

# 8. Restart/reload services to pick up the new code
sudo systemctl reload apache2        # or: sudo systemctl restart apache2
sudo systemctl reload php8.*-fpm     # only if using PHP-FPM (clears opcache)
sudo systemctl restart adms-queue    # restart queue workers
```

### After the upgrade

- If the release changed server-level Apache config (e.g. virtual host,
  ports, Basic Auth `<Location>` blocks), edit
  `/etc/apache2/sites-available/adms.conf` manually and reload Apache.
- Check `/var/log/apache2/access.log` and
  `/var/www/adms/storage/logs/laravel.log` for errors.
- Verify `/iclock/cdata`, `/iclock/test`, and `/iclock/getrequest` respond as
  expected (`curl http://your-domain.com:8080/iclock/test?SN=XXXX`).
- If the release introduced a new required field (like a device Comm Key),
  complete the data-entry step in the admin UI for existing records.

## Troubleshooting

- Check Apache errors:

  ```bash
  sudo tail -f /var/log/apache2/error.log
  ```

- Device data rejected (`ERROR: 0`):

  ```bash
  sudo tail -f /var/log/apache2/access.log
  ```

  Confirm the `ComKey` sent by the device matches the **Comm Key** stored for
  the device under **Devices → New Devices → Assign** (or **Devices → Edit**).

- Laravel logs:

  ```bash
  sudo tail -f /var/www/adms/storage/logs/laravel.log
  ```

- After changing `.env`, re-run:

  ```bash
  php artisan config:clear && php artisan config:cache
  ```

- If `npm run build` fails, ensure Node LTS is active (`nvm use --lts`).
