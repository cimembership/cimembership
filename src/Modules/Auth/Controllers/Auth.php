<?php

declare(strict_types=1);

namespace CIMembership\Modules\Auth\Controllers;

use App\Controllers\BaseController;
use CIMembership\Libraries\Auth\Authentication;
use CIMembership\Libraries\Auth\PasswordHasher;
use CIMembership\Libraries\Captcha\CaptchaService;
use CIMembership\Models\UserModel;
use CIMembership\Models\UserGroupModel;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Auth Controller
 *
 * Handles login, logout, registration, and password reset.
 *
 * @package CIMembership\Modules\Auth\Controllers
 */
class Auth extends BaseController
{
    protected $helpers = ['form', 'url'];

    protected ?Authentication $auth = null;
    protected ?UserModel $userModel = null;
    protected ?UserGroupModel $groupModel = null;
    protected ?CaptchaService $captcha = null;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);

        $this->auth = new Authentication();
        $this->userModel = model('UserModel');
        $this->groupModel = model('UserGroupModel');
        $this->captcha = new CaptchaService();
    }

    /**
     * Login page
     */
    public function login(): ResponseInterface|string
    {
        // Redirect if already logged in
        if ($this->auth->isLoggedIn()) {
            return redirect()->to('/');
        }

        $data = [
            'title'    => 'Login',
            'captcha'  => $this->captcha->isEnabled('login'),
            'oauth'    => $this->getEnabledOAuthProviders(),
        ];

        if ($data['captcha']) {
            $data['captchaImage'] = $this->captcha->generate();
        }

        return view('CIMembership\Modules\Auth\Views\login', $data);
    }

    /**
     * Process login
     */
    public function attemptLogin(): ResponseInterface
    {
        $rules = [
            'username' => 'required|min_length[3]',
            'password' => 'required',
        ];

        // Validate captcha if enabled
        if ($this->captcha->isEnabled('login')) {
            $rules['captcha'] = 'required';
        }

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('error', $this->validator->getErrors());
        }

        $username = $this->request->getPost('username');
        $password = $this->request->getPost('password');
        $remember = (bool) $this->request->getPost('remember');

        // Validate captcha
        if ($this->captcha->isEnabled('login')) {
            $captcha = $this->request->getPost('captcha');
            if (!$this->captcha->verify($captcha)) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Invalid captcha code.');
            }
        }

        // Attempt login
        $result = $this->auth->attempt([
            'username' => $username,
            'password' => $password,
        ], $remember);

        if (!$result->isOK()) {
            return redirect()->back()
                ->withInput()
                ->with('error', $result->getReason());
        }

        // Get intended URL or redirect to home
        $redirectUrl = $this->session->get('redirect_url') ?? '/';
        $this->session->remove('redirect_url');

        return redirect()->to($redirectUrl)
            ->with('success', 'Welcome back!');
    }

    /**
     * Logout
     */
    public function logout(): ResponseInterface
    {
        $this->auth->logout();

        return redirect()->to('/')
            ->with('success', 'You have been logged out successfully.');
    }

    /**
     * Register page
     */
    public function register(): ResponseInterface|string
    {
        // Check if registration is allowed
        $optionModel = model('OptionModel');
        if (!$optionModel->getOption('allow_registration', true)) {
            return redirect()->to('/')
                ->with('error', 'Registration is currently disabled.');
        }

        $data = [
            'title'    => 'Register',
            'captcha'  => $this->captcha->isEnabled('register'),
            'oauth'    => $this->getEnabledOAuthProviders(),
        ];

        if ($data['captcha']) {
            $data['captchaImage'] = $this->captcha->generate();
        }

        return view('CIMembership\Modules\Auth\Views\register', $data);
    }

    /**
     * Process registration
     */
    public function attemptRegister(): ResponseInterface
    {
        $rules = [
            'username'         => 'required|min_length[3]|max_length[50]|is_unique[users.username]',
            'email'              => 'required|valid_email|is_unique[users.email]',
            'password'           => 'required|min_length[8]',
            'password_confirm'   => 'required|matches[password]',
            'first_name'         => 'permit_empty|max_length[100]',
            'last_name'          => 'permit_empty|max_length[100]',
            'terms'              => 'required',
        ];

        if ($this->captcha->isEnabled('register')) {
            $rules['captcha'] = 'required';
        }

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('error', $this->validator->getErrors());
        }

        // Validate captcha
        if ($this->captcha->isEnabled('register')) {
            $captcha = $this->request->getPost('captcha');
            if (!$this->captcha->verify($captcha)) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Invalid captcha code.');
            }
        }

        // Get default group
        $defaultGroup = $this->groupModel->getDefault();

        $userData = [
            'username'    => $this->request->getPost('username'),
            'email'       => $this->request->getPost('email'),
            'password'    => $this->request->getPost('password'),
            'group_id'    => $defaultGroup['id'] ?? 5,
            'first_name'  => $this->request->getPost('first_name'),
            'last_name'   => $this->request->getPost('last_name'),
        ];

        // Check if activation is required
        $optionModel = model('OptionModel');
        $requireActivation = $optionModel->getOption('require_activation', true);

        if ($requireActivation) {
            $userData['status'] = 'pending';
            $userData['activation_token'] = bin2hex(random_bytes(32));
            $userData['activation_expires'] = date('Y-m-d H:i:s', strtotime('+24 hours'));
        } else {
            $userData['status'] = 'active';
        }

        try {
            $userId = $this->userModel->insert($userData);

            if (!$userId) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Failed to create account. Please try again.');
            }

            // Send activation email if required
            if ($requireActivation) {
                $this->sendActivationEmail($userData);

                return redirect()->to('/auth/login')
                    ->with('success', 'Registration successful! Please check your email to activate your account.');
            }

            // Auto login
            $this->auth->loginById($userId);

            return redirect()->to('/')
                ->with('success', 'Registration successful! Welcome to our site.');

        } catch (\Exception $e) {
            log_message('error', 'Registration error: ' . $e->getMessage());
            return redirect()->back()
                ->withInput()
                ->with('error', 'An error occurred. Please try again later.');
        }
    }

    /**
     * Activate account
     */
    public function activate(string $token): ResponseInterface
    {
        $user = $this->userModel->findByActivationToken($token);

        if (!$user) {
            return redirect()->to('/auth/login')
                ->with('error', 'Invalid or expired activation token.');
        }

        $this->userModel->activate($user['id']);

        return redirect()->to('/auth/login')
            ->with('success', 'Your account has been activated. You can now log in.');
    }

    /**
     * Forgot password page
     */
    public function forgotPassword(): ResponseInterface|string
    {
        $data = [
            'title'   => 'Forgot Password',
            'captcha' => $this->captcha->isEnabled('forgot_password'),
        ];

        if ($data['captcha']) {
            $data['captchaImage'] = $this->captcha->generate();
        }

        return view('CIMembership\Modules\Auth\Views\forgot_password', $data);
    }

    /**
     * Process forgot password
     */
    public function attemptForgotPassword(): ResponseInterface
    {
        $rules = [
            'email' => 'required|valid_email',
        ];

        if ($this->captcha->isEnabled('forgot_password')) {
            $rules['captcha'] = 'required';
        }

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->with('error', $this->validator->getErrors());
        }

        if ($this->captcha->isEnabled('forgot_password')) {
            $captcha = $this->request->getPost('captcha');
            if (!$this->captcha->verify($captcha)) {
                return redirect()->back()
                    ->with('error', 'Invalid captcha code.');
            }
        }

        $email = $this->request->getPost('email');
        $user = $this->userModel->findByEmail($email);

        if ($user) {
            // Generate reset token
            $resetToken = bin2hex(random_bytes(32));
            $this->userModel->update($user['id'], [
                'reset_token'   => $resetToken,
                'reset_expires' => date('Y-m-d H:i:s', strtotime('+1 hour')),
            ]);

            // Send reset email
            $this->sendPasswordResetEmail($user, $resetToken);
        }

        // Always show success to prevent email enumeration
        return redirect()->to('/auth/login')
            ->with('success', 'If an account exists with that email, password reset instructions have been sent.');
    }

    /**
     * Reset password page
     */
    public function resetPassword(string $token): ResponseInterface|string
    {
        $user = $this->userModel->findByResetToken($token);

        if (!$user) {
            return redirect()->to('/auth/forgot-password')
                ->with('error', 'Invalid or expired reset token.');
        }

        return view('CIMembership\Modules\Auth\Views\reset_password', [
            'title' => 'Reset Password',
            'token' => $token,
        ]);
    }

    /**
     * Process password reset
     */
    public function attemptResetPassword(string $token): ResponseInterface
    {
        $user = $this->userModel->findByResetToken($token);

        if (!$user) {
            return redirect()->to('/auth/forgot-password')
                ->with('error', 'Invalid or expired reset token.');
        }

        $rules = [
            'password'         => 'required|min_length[8]',
            'password_confirm' => 'required|matches[password]',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->with('error', $this->validator->getErrors());
        }

        // Update password
        $password = $this->request->getPost('password');
        $hasher = new PasswordHasher();

        $this->userModel->update($user['id'], [
            'password_hash' => $hasher->hash($password),
            'reset_token'   => null,
            'reset_expires' => null,
        ]);

        return redirect()->to('/auth/login')
            ->with('success', 'Your password has been reset. Please log in with your new password.');
    }

    /**
     * Resend activation email
     */
    public function resendActivation(): ResponseInterface|string
    {
        return view('CIMembership\Modules\Auth\Views\resend_activation', [
            'title' => 'Resend Activation Email',
        ]);
    }

    /**
     * Process resend activation
     */
    public function attemptResendActivation(): ResponseInterface
    {
        $rules = [
            'email' => 'required|valid_email',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->with('error', $this->validator->getErrors());
        }

        $email = $this->request->getPost('email');
        $user = $this->userModel
            ->where('email', $email)
            ->where('status', 'pending')
            ->first();

        if ($user) {
            // Generate new token
            $activationToken = bin2hex(random_bytes(32));
            $this->userModel->update($user['id'], [
                'activation_token'   => $activationToken,
                'activation_expires' => date('Y-m-d H:i:s', strtotime('+24 hours')),
            ]);

            $user['activation_token'] = $activationToken;
            $this->sendActivationEmail($user);
        }

        return redirect()->to('/auth/login')
            ->with('success', 'If an account exists with that email, activation instructions have been sent.');
    }

    /**
     * Get enabled OAuth providers
     */
    private function getEnabledOAuthProviders(): array
    {
        $providers = [];
        $optionModel = model('OptionModel');

        $oauthProviders = [
            'facebook'  => 'Facebook',
            'google'    => 'Google',
            'github'    => 'GitHub',
            'linkedin'  => 'LinkedIn',
            'twitter'   => 'Twitter',
            'microsoft' => 'Microsoft',
        ];

        foreach ($oauthProviders as $key => $name) {
            if ($optionModel->getOption("oauth_{$key}_enabled", false)) {
                $providers[$key] = $name;
            }
        }

        return $providers;
    }

    /**
     * Send activation email
     */
    private function sendActivationEmail(array $user): void
    {
        $email = service('email');
        $email->setTo($user['email']);
        $email->setSubject('Activate Your Account');
        $email->setMessage(view('CIMembership\Modules\Auth\Views\emails/activation', [
            'username' => $user['username'],
            'token'    => $user['activation_token'],
        ]));
        $email->send();
    }

    /**
     * Send password reset email
     */
    private function sendPasswordResetEmail(array $user, string $token): void
    {
        $email = service('email');
        $email->setTo($user['email']);
        $email->setSubject('Password Reset Request');
        $email->setMessage(view('CIMembership\Modules\Auth\Views\emails/reset_password', [
            'username' => $user['username'],
            'token'    => $token,
        ]));
        $email->send();
    }
}
