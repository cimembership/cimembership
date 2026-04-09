# CIMembership Base Platform

A CodeIgniter 4 membership management system base that can be used as a foundation for your applications.

## Installation

Install via Composer:

```bash
composer require cimembership/cimembership
```

## Setup

### 1. Install the Base

Run the installation command:

```bash
php spark ci-membership:install
```

This will:
- Publish configuration files
- Publish migration files
- Set up required directories

### 2. Configure Database

Update your `.env` file:

```env
database.default.hostname = localhost
database.default.database = your_database
database.default.username = your_username
database.default.password = your_password
database.default.DBDriver = MySQLi
```

### 3. Run Migrations

```bash
php spark migrate --namespace CIMembership
```

### 4. Create Admin User

```bash
php spark db:seed CIMembership\Database\Seeds\AdminSeeder
```

## Usage

### Authentication

Use the authentication library in your controllers:

```php
<?php

namespace App\Controllers;

use CIMembership\Libraries\Auth\Authentication;

class MyController extends BaseController
{
    protected Authentication $auth;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->auth = new Authentication();
    }

    public function protectedPage()
    {
        if (!$this->auth->isLoggedIn()) {
            return redirect()->to('/login');
        }

        $user = $this->auth->getCurrentUser();
        // Your protected code here
    }
}
```

### Checking Permissions

```php
// Check if user is logged in
if ($this->auth->isLoggedIn()) {
    // User is logged in
}

// Check if user has a specific permission
if ($this->auth->hasPermission('users.create')) {
    // User can create users
}

// Check if user is in a group
if ($this->auth->inGroup('admin')) {
    // User is an admin
}
```

### Using Models

```php
use CIMembership\Models\UserModel;

$userModel = new UserModel();
$user = $userModel->findByEmail('user@example.com');
```

## Modules

CIMembership provides modules that you can use or extend:

### Auth Module

- Login/Logout
- Registration with email activation
- Password reset
- OAuth (Google, Facebook, GitHub, LinkedIn)
- User profile management

### Admin Module

- Dashboard
- User management
- User groups management
- Settings
- API key management

## Configuration

Publish and customize the configuration:

```bash
php spark ci-membership:publish-config
```

### OAuth Configuration

Add to your `.env`:

```env
# Google OAuth
cimembership.oauth.google.clientId = your-client-id
cimembership.oauth.google.clientSecret = your-client-secret

# Facebook OAuth
cimembership.oauth.facebook.clientId = your-app-id
cimembership.oauth.facebook.clientSecret = your-app-secret

# GitHub OAuth
cimembership.oauth.github.clientId = your-client-id
cimembership.oauth.github.clientSecret = your-client-secret

# LinkedIn OAuth
cimembership.oauth.linkedin.clientId = your-client-id
cimembership.oauth.linkedin.clientSecret = your-client-secret
```

## Routes

Add to your `app/Config/Routes.php`:

```php
// CIMembership Auth routes
$routes->group('auth', ['namespace' => 'CIMembership\Modules\Auth\Controllers'], function ($routes) {
    $routes->get('login', 'Auth::login');
    $routes->post('login', 'Auth::attemptLogin');
    $routes->get('logout', 'Auth::logout');
    $routes->get('register', 'Auth::register');
    $routes->post('register', 'Auth::attemptRegister');
    $routes->get('forgot-password', 'Auth::forgotPassword');
    $routes->post('forgot-password', 'Auth::attemptForgot');
    // ... etc
});

// CIMembership Admin routes
$routes->group('admin', ['namespace' => 'CIMembership\Modules\Admin\Controllers', 'filter' => 'auth:admin'], function ($routes) {
    $routes->get('/', 'Dashboard::index');
    $routes->get('users', 'User::index');
    $routes->get('settings', 'Settings::index');
    // ... etc
});
```

## Extending

### Custom User Model

Extend the base user model:

```php
<?php

namespace App\Models;

use CIMembership\Models\UserModel as BaseUserModel;

class UserModel extends BaseUserModel
{
    protected $allowedFields = [
        'username', 'email', 'password_hash', 'status',
        'group_id', 'activation_token', 'reset_token',
        'last_login_at', 'last_active_at', 'remember_token',
        'hospital_id', // Your custom field
    ];

    // Your custom methods
    public function getByHospital(int $hospitalId)
    {
        return $this->where('hospital_id', $hospitalId)->findAll();
    }
}
```

### Custom Controllers

Extend CIMembership controllers:

```php
<?php

namespace App\Controllers\Admin;

use CIMembership\Modules\Admin\Controllers\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    public function index()
    {
        // Add your custom data
        $data['hospitals'] = model('HospitalModel')->findAll();

        return $this->view('admin/dashboard', $data);
    }
}
```

## Customization

### Views

Publish views to customize them:

```bash
php spark ci-membership:publish-views
```

Views will be copied to `app/Views/cimembership/` where you can customize them.

### Language Files

```bash
php spark ci-membership:publish-language
```

## API Usage

CIMembership includes API authentication:

```bash
# Get API key from admin panel
# Then use it in requests:

curl -H "X-API-Key: your-api-key" \
     https://example.com/api/users
```

## Security

- All passwords hashed with bcrypt (cost factor 12)
- CSRF protection on all forms
- Rate limiting on login attempts
- Session security with regeneration on login
- XSS and SQL injection prevention

## License

MIT License - see LICENSE file for details.

## Support

- GitHub: https://github.com/cimembership/cimembership
- Documentation: https://docs.cimembership.com
