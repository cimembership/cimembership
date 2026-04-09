<?php

declare(strict_types=1);

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

class ApiAuthFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null): ?ResponseInterface
    {
        $apiKey = $request->getHeaderLine('X-API-Key');

        if (empty($apiKey)) {
            return service('response')
                ->setJSON(['error' => 'API key is required'])
                ->setStatusCode(401);
        }

        // Validate API key against database
        $apiKeyModel = model('ApiKeyModel');
        $keyData = $apiKeyModel->getKey($apiKey);

        if (!$keyData) {
            return service('response')
                ->setJSON(['error' => 'Invalid API key'])
                ->setStatusCode(401);
        }

        // Store API key user info for controller use
        service('request')->setApiUser($keyData);

        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null): void
    {
        // Nothing to do here
    }
}
