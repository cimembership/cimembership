# CIMembership Package Structure

This document outlines the structure of the CIMembership Composer package.

## Installation

```bash
composer require cimembership/cimembership
```

## Available Commands

After installation, the following CLI commands are available:

### 1. Install Command
```bash
php spark ci-membership:install
```
Publishes migrations, seeders, and config to the host application.

### 2. Upgrade from v3 Command (As documented in UPGRADE.md)
```bash
php spark upgrade:fromv3 \
    --source-db=old_cimembership_db \
    --source-host=localhost \
    --source-user=root \
    --source-pass=your_password \
    --source-prefix=ci_
```
Migrates data from CI3 version to CI4 version.

**Options:**
- `--source-db`: Source CI3 database name
- `--source-host`: Source CI3 database host (default: localhost)
- `--source-user`: Source CI3 database user (default: root)
- `--source-pass`: Source CI3 database password
- `--source-prefix`: Source CI3 table prefix (default: ci_)
- `--dry-run`: Run without making changes

## Package Structure

```
src/
├── Commands/
│   ├── InstallCommand.php          # php spark ci-membership:install
│   └── UpgradeFromV3.php           # php spark upgrade:fromv3
├── Database/
│   ├── Migrations/                  # All database migrations
│   │   ├── 2024-01-01-000001_CreateUsersGroups.php
│   │   ├── 2024-01-01-000002_CreateUsers.php
│   │   ├── 2024-01-01-000003_CreateUserProfiles.php
│   │   ├── 2024-01-01-000004_CreateUserOauthConnections.php
│   │   ├── 2024-01-01-000005_CreateLoginAttempts.php
│   │   ├── 2024-01-01-000006_CreateOptions.php
│   │   ├── 2024-01-01-000007_CreateApiKeys.php
│   │   └── 2024-01-01-000008_CreateCiSessions.php
│   └── Seeds/
│       └── CIMembershipSeeder.php   # Creates admin user
├── Install/
│   └── Installer.php               # Post-install hooks
├── Libraries/
│   ├── Auth/
│   │   ├── Authentication.php      # Core authentication
│   │   └── PasswordHasher.php      # Password hashing
│   ├── Captcha/
│   │   └── CaptchaService.php      # CAPTCHA handling
│   ├── OAuth/
│   │   └── OAuthProviderFactory.php # OAuth providers
│   └── Settings/
│       └── SettingsService.php     # Settings management
├── Models/
│   ├── UserModel.php               # User management
│   ├── UserGroupModel.php          # User groups
│   ├── UserProfileModel.php        # User profiles
│   ├── OptionModel.php             # Settings/options
│   ├── LoginAttemptModel.php       # Login tracking
│   └── UserOauthModel.php          # OAuth connections
└── Modules/
    └── Auth/
        └── Controllers/
            ├── Auth.php            # Login, register, etc.
            └── OAuth.php           # OAuth callbacks
```

## Usage in Consumer Applications

### 1. Install the Package
```bash
composer require cimembership/cimembership
```

### 2. Run the Installer
```bash
php spark ci-membership:install
```

### 3. Run Migrations
```bash
php spark migrate --namespace CIMembership
```

### 4. Create Admin User
```bash
php spark db:seed CIMembership\\Database\\Seeds\\CIMembershipSeeder
```

### 5. Add Routes
Add to `app/Config/Routes.php`:
```php
// Auth routes
$routes->group('auth', ['namespace' => 'CIMembership\Modules\Auth\Controllers'], function ($routes) {
    $routes->get('login', 'Auth::login');
    $routes->post('login', 'Auth::attemptLogin');
    $routes->get('logout', 'Auth::logout');
    $routes->get('register', 'Auth::register');
    $routes->post('register', 'Auth::attemptRegister');
    // ... etc
});
```

## Namespace Mapping

All classes use the `CIMembership\` namespace:

| Old (app/) | New (src/) |
|------------|------------|
| `App\Libraries\Auth\Authentication` | `CIMembership\Libraries\Auth\Authentication` |
| `App\Models\UserModel` | `CIMembership\Models\UserModel` |
| `App\Modules\Auth\Controllers\Auth` | `CIMembership\Modules\Auth\Controllers\Auth` |
| `App\Commands\UpgradeFromV3` | `CIMembership\Commands\UpgradeFromV3` |

## Upgrade from v3

The upgrade command `upgrade:fromv3` is fully compatible with the instructions in UPGRADE.md:

```bash
# Step 1: Install v4
composer require cimembership/cimembership

# Step 2: Run migrations
php spark migrate --namespace CIMembership

# Step 3: Run upgrade (as documented in UPGRADE.md)
php spark upgrade:fromv3 \
    --source-db=old_cimembership_db \
    --source-host=localhost \
    --source-user=root \
    --source-pass=your_password \
    --source-prefix=ci_
```

The upgrade command migrates:
- User groups (custom groups only, system groups recreated)
- Users (with migration flag on passwords)
- User profiles
- OAuth connections
- Options/settings

## License

MIT License
