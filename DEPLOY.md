# Deployment Guide — ADMS on Ubuntu (Apache + port 8080 + Cloudflare)

This guide deploys ADMS (Laravel 10, MySQL, Bootstrap/Vite) on Ubuntu with Apache
listening on port 8080, `.htaccess`-style Basic Auth protecting the ZKTeco device
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

QUEUE_CONNECTION=sync
```

Generate the app key and run migrations/seeders:

```bash
php artisan key:generate
php artisan migrate --force
php artisan db:seed --force
```

> Note: `QUEUE_CONNECTION=sync` means attendance staging is processed inline on
> each device POST, so no queue worker is required. If you later switch to a
> database queue, run `php artisan queue:work` under a service manager.

## 5. Cache config and fix permissions

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache

sudo chown -R www-data:www-data storage bootstrap/cache
```

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
sudo a2enmod rewrite auth_basic
sudo a2ensite adms.conf
sudo a2dissite 000-default.conf
sudo systemctl restart apache2
```

Open the firewall:

```bash
sudo ufw allow 8080/tcp
```

## 7. Protect the device endpoints with Basic Auth

Create the htpasswd file:

```bash
sudo mkdir -p /etc/apache2/htpasswd
sudo htpasswd -c /etc/apache2/htpasswd/.htpasswd device
```

Add more users if needed (each `sudo htpasswd` call after `-c` adds a user):

```bash
sudo htpasswd /etc/apache2/htpasswd/.htpasswd admin
```

Add `<Location>` blocks to `/etc/apache2/sites-available/adms.conf` (inside the
`<VirtualHost>`):

```apache
<Location "/iclock/cdata">
    AuthType Basic
    AuthName "Restricted Content"
    AuthUserFile /etc/apache2/htpasswd/.htpasswd
    Require valid-user
</Location>

<Location "/iclock/test">
    AuthType Basic
    AuthName "Restricted Content"
    AuthUserFile /etc/apache2/htpasswd/.htpasswd
    Require valid-user
</Location>

<Location "/iclock/getrequest">
    AuthType Basic
    AuthName "Restricted Content"
    AuthUserFile /etc/apache2/htpasswd/.htpasswd
    Require valid-user
</Location>
```

Restart Apache:

```bash
sudo systemctl restart apache2
```

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

The device (e.g. ZKTeco SpeedFace-V5L-RFID) must be pointed at the protected
endpoint. The device comm settings are:

- **Server / IP**: `your-domain.com`
- **Port**: `8080`
- **CommKey / Security key**: as configured on your device

The device endpoint uses the Basic Auth credentials in the URL:

```
http://device:DEVICE_PASSWORD@your-domain.com:8080
```

The device handshakes at `/iclock/cdata` and pushes logs there. Verify the
device registers via the "Devices pending" screen in the admin dashboard.

## 10. Verify

- Browser: `http://your-domain.com:8080` → login works.
  - Default seeded super admin: `john.doe@example.com` / `password`
- Device handshake: check `/var/log/apache2/access.log` for `GET /iclock/cdata`
  entries from the device.
- The device shows **online** in the dashboard after a handshake.

## Troubleshooting

- Check Apache errors:

  ```bash
  sudo tail -f /var/log/apache2/error.log
  ```

- Ensure Basic Auth module is enabled:

  ```bash
  sudo a2enmod auth_basic
  sudo systemctl restart apache2
  ```

- Laravel logs:

  ```bash
  sudo tail -f /var/www/adms/storage/logs/laravel.log
  ```

- After changing `.env`, re-run:

  ```bash
  php artisan config:clear && php artisan config:cache
  ```

- If `npm run build` fails, ensure Node LTS is active (`nvm use --lts`).
