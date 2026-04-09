# CIMembership v4

A modern, secure membership management system built with **CodeIgniter 4**.

[![PHP Version](https://img.shields.io/badge/php-%3E%3D8.1-8892BF.svg)](https://php.net/)
[![CodeIgniter](https://img.shields.io/badge/CodeIgniter-4.x-orange.svg)](https://codeigniter.com)
[![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE)

## Features

- **Modern CodeIgniter 4** - Built on the latest CodeIgniter 4.x framework
- **PHP 8.1+** - Uses modern PHP features for better performance and security
- **Secure Authentication** - Password hashing with bcrypt, CSRF protection, rate limiting
- **OAuth Social Login** - Facebook, Google, GitHub, LinkedIn, Twitter, Microsoft
- **Role-Based Access Control** - Flexible user groups and permissions
- **User Profiles** - Complete user profile management with avatar upload
- **Admin Dashboard** - Beautiful dashboard with user management
- **Email Activation** - Optional email verification for new accounts
- **Password Reset** - Secure password reset via email
- **API Ready** - RESTful API with API key authentication
- **CLI Installer** - Modern command-line installation
- **Upgrade Path** - Migrate from CI3 CIMembership seamlessly

## Requirements

- PHP 8.1 or higher
- MySQL 5.7+ or MariaDB 10.3+
- Composer
- Web server (Apache/Nginx)

## Installation

### Quick Start

```bash
# Install via Composer
composer create-project cimembership/cimembership cimembership
cd cimembership

# Run the installer
php spark install:app
```

### Manual Installation

1. Download the latest release
2. Extract to your web server directory
3. Run `composer install`
4. Copy `.env.example` to `.env` and configure
5. Run `php spark migrate`
6. Create an admin user

For detailed instructions, see [INSTALL.md](INSTALL.md).

## Upgrading from v3 (CI3)

If you're upgrading from CIMembership v3 (CodeIgniter 3):

```bash
# After installing v4, run the upgrade command
php spark upgrade:fromv3
```

This will migrate your users, groups, profiles, and settings from CI3 to CI4.

See [UPGRADE.md](UPGRADE.md) for detailed upgrade instructions.

## Directory Structure

```
cimembership/
├── app/
│   ├── Commands/           # CLI commands (installer, upgrade)
│   ├── Config/            # Configuration files
│   ├── Controllers/       # Base controllers
│   ├── Database/
│   │   └── Migrations/    # Database migrations
│   ├── Filters/           # Authentication & security filters
│   ├── Libraries/         # Core libraries
│   │   ├── Auth/         # Authentication classes
│   │   ├── Captcha/      # Captcha handling
│   │   └── OAuth/        # OAuth providers
│   ├── Models/           # Database models
│   └── Modules/
│       ├── Admin/         # Admin module (Dashboard, Users, Settings)
│       └── Auth/          # Auth module (Login, Register, OAuth)
├── public/               # Web root
├── tests/                # Unit tests
├── writable/             # Cache, logs, uploads
├── composer.json
└── spark                 # CLI entry point
```

## Configuration

### Environment Variables

Copy `.env.example` to `.env` and configure:

```env
# Database
database.default.hostname = localhost
database.default.database = cimembership
database.default.username = root
database.default.password =
database.default.DBPrefix = ci_

# Application
app.baseURL = https://example.com/
app.timezone = UTC

# Email (for activation, password reset)
email.protocol = smtp
email.SMTPHost = smtp.example.com
email.SMTPUser = user@example.com
email.SMTPPass = password
email.SMTPPort = 587
email.SMTPCrypto = tls
```

### OAuth Configuration

Enable OAuth providers in Admin > Settings > OAuth:

1. Go to Admin Dashboard
2. Navigate to Settings > OAuth
3. Enter your Client ID and Client Secret for each provider
4. Enable the providers you want to use

**Note:** Configure redirect URLs in your OAuth app:
- Facebook: `https://example.com/auth/oauth/facebook/callback`
- Google: `https://example.com/auth/oauth/google/callback`
- GitHub: `https://example.com/auth/oauth/github/callback`
- LinkedIn: `https://example.com/auth/oauth/linkedin/callback`

## CLI Commands

```bash
# Installation
php spark install:app

# Upgrade from v3
php spark upgrade:fromv3 --source-db=old_cimembership_db --source-prefix=ci_

# Database
php spark migrate
php spark migrate:rollback
php spark migrate:refresh

# Seeds
php spark db:seed UserSeeder
```

## Security Features

- **Password Hashing**: bcrypt with cost factor 12
- **CSRF Protection**: All forms protected by default
- **Rate Limiting**: Login attempts limited per IP
- **Session Security**: Regenerated on login
- **XSS Protection**: Output escaping by default
- **SQL Injection Prevention**: Parameterized queries
- **Password Strength**: Configurable minimum requirements

## API Usage

CIMembership v4 includes a RESTful API:

```bash
# Get API key from Admin panel
curl -H "X-API-Key: your-api-key" \
     https://example.com/api/users

# Create user
curl -X POST \
     -H "X-API-Key: your-api-key" \
     -H "Content-Type: application/json" \
     -d '{"username":"john","email":"john@example.com","password":"secret"}' \
     https://example.com/api/users
```

## Troubleshooting

### Common Issues

**Installation fails with database error:**
- Check MySQL credentials in `.env`
- Ensure database exists and user has privileges
- Check MySQL version (5.7+ required)

**OAuth not working:**
- Verify redirect URLs are configured correctly
- Check Client ID and Secret are correct
- Ensure callback URLs are HTTPS in production

**Emails not sending:**
- Configure SMTP settings in `.env`
- Check server allows outgoing SMTP connections
- Review logs in `writable/logs/`

## Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

Please ensure:
- Code follows PSR-12 coding standards
- All tests pass
- Documentation is updated

## License

CIMembership is open-source software licensed under the [MIT license](LICENSE).

## Support

- **Documentation**: [https://docs.cimembership.com](https://docs.cimembership.com)
- **Issues**: [GitHub Issues](https://github.com/cimembership/cimembership/issues)
- **Discussions**: [GitHub Discussions](https://github.com/cimembership/cimembership/discussions)

---

**Note**: This is a complete rewrite from the original CIMembership v3. While we've provided an upgrade path, please backup your data before migrating.
