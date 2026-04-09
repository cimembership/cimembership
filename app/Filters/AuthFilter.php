<?php

declare(strict_types=1);

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;

class AuthFilter implements FilterInterface
{
    /**
     * Executes before the controller is called.
     *
     * @param array|null $arguments
     *        - 'admin' for admin-only access
     *        - 'superadmin' for super admin only
     *        - specific permission name
     */
    public function before(RequestInterface $request, $arguments = null): ?ResponseInterface
    {
        $auth = Services::authentication();

        // Check if user is logged in
        if (!$auth->isLoggedIn()) {
            return redirect()->to('/auth/login')->with('error', 'Please login to access this page.');
        }

        // Check for specific requirements
        if ($arguments !== null) {
            $user = $auth->getCurrentUser();

            foreach ($arguments as $argument) {
                switch ($argument) {
                    case 'admin':
                        if (!$auth->hasPermission('access_backend')) {
                            return redirect()->to('/')->with('error', 'You do not have permission to access the admin area.');
                        }
                        break;

                    case 'superadmin':
                        if ($user['group_id'] !== 1) {
                            return redirect()->to('/admin')->with('error', 'Only super administrators can access this feature.');
                        }
                        break;

                    default:
                        // Check specific permission
                        if (!$auth->hasPermission($argument)) {
                            return redirect()->back()->with('error', 'You do not have permission to perform this action.');
                        }
                        break;
                }
            }
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null): void
    {
        // Nothing to do here
    }
}
