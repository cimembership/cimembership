<?php

declare(strict_types=1);

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;

/**
 * Base Controller for the application
 */
abstract class BaseController extends Controller
{
    /**
     * Instance of the main Request object.
     */
    protected RequestInterface $request;

    /**
     * An array of helpers to be loaded automatically upon class instantiation.
     */
    protected $helpers = ['url', 'form', 'html', 'security'];

    /**
     * Constructor.
     */
    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        // Load any necessary services
        $this->session = service('session');
        $this->validation = service('validation');
    }

    /**
     * Render view with layout
     */
    protected function render(string $view, array $data = [], ?string $layout = null): string
    {
        // Determine layout based on module prefix in view name
        if ($layout === null) {
            if (str_starts_with($view, 'Admin::')) {
                $layout = 'Admin/layout';
                $data['layout'] = $layout;
            } elseif (str_starts_with($view, 'Auth::')) {
                $layout = 'Auth/layout';
                $data['layout'] = $layout;
            } else {
                $layout = 'layout';
            }
        }

        // Extract module prefix
        $viewParts = explode('::', $view);
        if (count($viewParts) === 2) {
            $module = $viewParts[0];
            $viewPath = $viewParts[1];
            $data['content'] = view("{$module}/{$viewPath}", $data, ['saveData' => true]);
        } else {
            $data['content'] = view($view, $data, ['saveData' => true]);
        }

        return view($layout, $data);
    }

    /**
     * Render JSON response
     */
    protected function json(array $data, int $status = 200): ResponseInterface
    {
        return $this->response->setJSON($data)->setStatusCode($status);
    }

    /**
     * Send success JSON response
     */
    protected function success(string $message, array $data = [], int $status = 200): ResponseInterface
    {
        return $this->json([
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ], $status);
    }

    /**
     * Send error JSON response
     */
    protected function error(string $message, array $errors = [], int $status = 400): ResponseInterface
    {
        return $this->json([
            'success' => false,
            'message' => $message,
            'errors'  => $errors,
        ], $status);
    }

    /**
     * Check if request is AJAX
     */
    protected function isAjax(): bool
    {
        return $this->request->isAJAX();
    }

    /**
     * Get current user ID from session
     */
    protected function getCurrentUserId(): ?int
    {
        return $this->session->get('user_id');
    }

    /**
     * Set flash message
     */
    protected function setFlash(string $type, string $message): void
    {
        $this->session->setFlashdata($type, $message);
    }
}
