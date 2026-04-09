# Installation Guide

This guide covers installing CIMembership v4 on your server.

## Table of Contents

1. [Requirements](#requirements)
2. [Methods](#installation-methods)
   - [Method 1: CLI Installer (Recommended)](#method-1-cli-installer-recommended)
   - [Method 2: Manual Installation](#method-2-manual-installation)
3. [Web Server Configuration](#web-server-configuration)
4. [Post-Installation](#post-installation)
5. [Troubleshooting](#troubleshooting)

## Requirements

### Server Requirements

- PHP 8.1 or higher
- MySQL 5.7+ or MariaDB 10.3+
- Apache with mod_rewrite enabled OR Nginx
- SSL certificate (recommended for production)

### PHP Extensions

Required extensions (usually enabled by default):
- json
- mbstring
- intl
- curl
- openssl
- pdo
- pdo_mysql
- gd or imagick
- fileinfo

### Composer

Required for dependency management:

```bash
# Download Composer
curl -sS https://getcomposer.org/installer | php

# Move to global bin
mv composer.phar /usr/local/bin/composer
```

## Installation Methods

### Method 1: CLI Installer (Recommended)

This is the easiest and fastest way to install CIMembership v4.

#### Step 1: Download

```bash
# Install via Composer
composer create-project cimembership/cimembership cimembership
cd cimembership
```

#### Step 2: Run Installer

```bash
php spark install:app
```

The installer will guide you through:
1. Database configuration
2. Creating database tables
3. Setting up admin account
4. Site configuration
5. Creating the `.env` file

Follow the prompts and your installation will be complete!

#### Step 3: Set Permissions

```bash
# Set writable permissions
chmod -R 755 writable/
chmod -R 755 public/uploads/

# Set secure permissions for config
chmod 644 app/Config/*.php
```

### Method 2: Manual Installation

Use this method if you prefer more control or the CLI installer doesn't work.

#### Step 1: Download and Extract

```bash
# Install via Composer
composer create-project cimembership/cimembership cimembership
cd cimembership
```

#### Step 2: Create Database

```sql
CREATE DATABASE cimembership CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'cimembership'@'localhost' IDENTIFIED BY 'your_secure_password';
GRANT ALL PRIVILEGES ON cimembership.* TO 'cimembership'@'localhost';
FLUSH PRIVILEGES;
```

#### Step 3: Configure Environment

```bash
# Copy example env file
cp .env.example .env

# Edit with your settings
nano .env
```

Configure the following:

```env
# Environment
CI_ENVIRONMENT = production

# Base URL
app.baseURL = 'https://example.com/'

# Database
database.default.hostname = localhost
database.default.database = cimembership
database.default.username = cimembership
database.default.password = your_secure_password
database.default.DBPrefix = ci_

# Timezone
app.timezone = 'UTC'
```

#### Step 4: Run Migrations

```bash
php spark migrate
```

#### Step 5: Create Admin User

You can create an admin user via tinker or by inserting directly into the database.

Or use the seeder:

```bash
php spark db:seed UserSeeder
```

Then manually update the user to group_id=1 (Super Admin).

#### Step 6: Set Permissions

```bash
chmod -R 755 writable/
chmod -R 755 public/uploads/
chmod 644 .env
```

## Web Server Configuration

### Apache

Ensure `mod_rewrite` is enabled:

```bash
sudo a2enmod rewrite
sudo service apache2 restart
```

Create `.htaccess` in your web root (usually already included):

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /

    # Redirect to public folder
    RewriteRule ^$ public/ [L]
    RewriteRule (.*) public/$1 [L]
</IfModule>
```

VirtualHost configuration:

```apache
<VirtualHost *:80>
    ServerName example.com
    DocumentRoot /var/www/cimembership/public

    <Directory /var/www/cimembership/public>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/cimembership-error.log
    CustomLog ${APACHE_LOG_DIR}/cimembership-access.log combined
</VirtualHost>
```

### Nginx

```nginx
server {
    listen 80;
    server_name example.com;
    root /var/www/cimembership/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.ht {
        deny all;
    }

    location ~* ^/(?:uploads)/.*$ {
        # Allow uploaded files
    }
}
```

### Secure with HTTPS (Let's Encrypt)

```bash
# Install certbot
sudo apt install certbot python3-certbot-apache

# Obtain certificate
sudo certbot --apache -d example.com

# Or for Nginx
sudo certbot --nginx -d example.com
```

## Post-Installation

### 1. Secure Installation

Remove installer files (already done automatically):

```bash
# The installer is safe to keep, but if you want:
rm -f app/Commands/Install.php
```

### 2. Configure Email

Edit `.env` and set up email for:
- User activation emails
- Password reset emails
- Admin notifications

```env
email.protocol = smtp
email.SMTPHost = smtp.gmail.com
email.SMTPUser = your-email@gmail.com
email.SMTPPass = your-app-password
email.SMTPPort = 587
email.SMTPCrypto = tls
```

### 3. Configure OAuth (Optional)

Set up social login:

1. Go to Admin > Settings > OAuth
2. Configure providers:
   - [Google](https://console.cloud.google.com/)
   - [Facebook](https://developers.facebook.com/)
   - [GitHub](https://github.com/settings/developers)
   - [LinkedIn](https://www.linkedin.com/developers/)

### 4. Set Up Cron Jobs

Clean up old sessions and login attempts:

```bash
# Edit crontab
crontab -e

# Add these lines
0 2 * * * cd /var/www/cimembership && php spark db:wipe --table ci_sessions --older 30
0 3 * * * cd /var/www/cimembership && php spark db:wipe --table login_attempts --older 30
```

### 5. Enable Caching (Optional)

```env
# In .env
CI_ENVIRONMENT = production
app.baseURL = 'https://example.com/'
```

### 6. Change Default Admin Password

Log in to admin and change the default password immediately.

## Troubleshooting

### Common Issues

**500 Internal Server Error**
- Check `writable/logs/` for error messages
- Ensure `.env` file exists and is readable
- Verify PHP version is 8.1+
- Check file permissions

**Database Connection Failed**
- Verify database credentials in `.env`
- Ensure MySQL is running
- Check if database user has correct privileges

**CSS/JS Not Loading**
- Check `app.baseURL` in `.env` matches your URL
- Ensure `public/` is the web root
- Clear browser cache

**OAuth Callback Not Working**
- Ensure HTTPS is configured
- Verify redirect URLs in OAuth app settings
- Check callback URL format: `https://example.com/auth/oauth/{provider}/callback`

### Getting Help

If you encounter issues:

1. Check the [FAQ](https://docs.cimembership.com/faq)
2. Search [GitHub Issues](https://github.com/cimembership/cimembership/issues)
3. Ask on [GitHub Discussions](https://github.com/cimembership/cimembership/discussions)

### Debug Mode

Enable debug mode temporarily to see detailed errors:

```bash
# In .env
CI_ENVIRONMENT = development
```

**Never use debug mode in production!**

## Next Steps

- [User Guide](https://docs.cimembership.com)
- [API Documentation](https://docs.cimembership.com/api)
- [Theming Guide](https://docs.cimembership.com/theming)

Congratulations! CIMembership v4 is now installed and ready to use.
