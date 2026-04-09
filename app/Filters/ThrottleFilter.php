<?php

declare(strict_types=1);

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class ThrottleFilter implements FilterInterface
{
    /**
     * Rate limiting for login attempts
     */
    public function before(RequestInterface $request, $arguments = null): ?ResponseInterface
    {
        $throttler = \Config\Services::throttler();

        // Get IP address
        $ip = $request->getIPAddress();

        // Check if this IP has exceeded rate limit (5 attempts per minute)
        if (!$throttler->check('login_' . $ip, 5, MINUTE)) {
            return service('response')
                ->setJSON(['error' => 'Too many login attempts. Please try again later.'])
                ->setStatusCode(429);
        }

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null): void
    {
        // Nothing to do here
    }
}
