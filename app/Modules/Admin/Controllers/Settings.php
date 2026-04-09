<?php

declare(strict_types=1);

namespace App\Modules\Admin\Controllers;

use App\Controllers\BaseController;
use App\Models\OptionModel;
use CodeIgniter\HTTP\ResponseInterface;

class Settings extends BaseController
{
    protected ?OptionModel $optionModel = null;

    public function initController(\CodeIgniter\HTTP\RequestInterface $request, \CodeIgniter\HTTP\ResponseInterface $response, \Psr\Log\LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
        $this->optionModel = model('OptionModel');
    }

    /**
     * Settings page
     */
    public function index(): ResponseInterface|string
    {
        $data = [
            'title'     => 'Settings',
            'settings'  => $this->optionModel->getSiteSettings(),
            'oauth'     => $this->getOAuthSettings(),
            'email'     => $this->getEmailSettings(),
            'captcha'   => $this->getCaptchaSettings(),
        ];

        return $this->render('Admin::settings/index', $data);
    }

    /**
     * Update general settings
     */
    public function updateGeneral(): ResponseInterface
    {
        $rules = [
            'site_name'        => 'required|max_length[100]',
            'site_description' => 'permit_empty|max_length[255]',
            'webmaster_email'  => 'required|valid_email',
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->with('error', $this->validator->getErrors());
        }

        $this->optionModel->updateOption('site_name', $this->request->getPost('site_name'));
        $this->optionModel->updateOption('site_description', $this->request->getPost('site_description'));
        $this->optionModel->updateOption('webmaster_email', $this->request->getPost('webmaster_email'));

        return redirect()->to('/admin/settings')
            ->with('success', 'General settings updated.');
    }

    /**
     * Update auth settings
     */
    public function updateAuth(): ResponseInterface
    {
        $this->optionModel->updateOption('allow_registration', $this->request->getPost('allow_registration') ? '1' : '0');
        $this->optionModel->updateOption('require_activation', $this->request->getPost('require_activation') ? '1' : '0');
        $this->optionModel->updateOption('login_attempts_limit', $this->request->getPost('login_attempts_limit') ?: '5');
        $this->optionModel->updateOption('lockout_duration', $this->request->getPost('lockout_duration') ?: '900');
        $this->optionModel->updateOption('password_min_length', $this->request->getPost('password_min_length') ?: '8');

        return redirect()->to('/admin/settings')
            ->with('success', 'Authentication settings updated.');
    }

    /**
     * Update OAuth settings
     */
    public function updateOAuth(): ResponseInterface
    {
        $providers = ['facebook', 'google', 'github', 'linkedin', 'twitter', 'microsoft'];

        foreach ($providers as $provider) {
            $enabled = $this->request->getPost("oauth_{$provider}_enabled") ? '1' : '0';
            $clientId = $this->request->getPost("oauth_{$provider}_client_id") ?: '';
            $clientSecret = $this->request->getPost("oauth_{$provider}_client_secret") ?: '';

            $this->optionModel->updateOption("oauth_{$provider}_enabled", $enabled);
            $this->optionModel->updateOption("oauth_{$provider}_client_id", $clientId);
            $this->optionModel->updateOption("oauth_{$provider}_client_secret", $clientSecret);
        }

        return redirect()->to('/admin/settings')
            ->with('success', 'OAuth settings updated.');
    }

    /**
     * Update email settings
     */
    public function updateEmail(): ResponseInterface
    {
        $this->optionModel->updateOption('email_protocol', $this->request->getPost('email_protocol') ?: 'smtp');
        $this->optionModel->updateOption('email_smtp_host', $this->request->getPost('email_smtp_host') ?: '');
        $this->optionModel->updateOption('email_smtp_port', $this->request->getPost('email_smtp_port') ?: '587');
        $this->optionModel->updateOption('email_smtp_user', $this->request->getPost('email_smtp_user') ?: '');
        $this->optionModel->updateOption('email_smtp_pass', $this->request->getPost('email_smtp_pass') ?: '');
        $this->optionModel->updateOption('email_smtp_crypto', $this->request->getPost('email_smtp_crypto') ?: 'tls');

        return redirect()->to('/admin/settings')
            ->with('success', 'Email settings updated.');
    }

    /**
     * Update captcha settings
     */
    public function updateCaptcha(): ResponseInterface
    {
        $this->optionModel->updateOption('captcha_enabled', $this->request->getPost('captcha_enabled') ? '1' : '0');
        $this->optionModel->updateOption('recaptcha_enabled', $this->request->getPost('recaptcha_enabled') ? '1' : '0');
        $this->optionModel->updateOption('recaptcha_site_key', $this->request->getPost('recaptcha_site_key') ?: '');
        $this->optionModel->updateOption('recaptcha_secret_key', $this->request->getPost('recaptcha_secret_key') ?: '');

        return redirect()->to('/admin/settings')
            ->with('success', 'Captcha settings updated.');
    }

    /**
     * Get OAuth settings
     */
    private function getOAuthSettings(): array
    {
        $providers = ['facebook', 'google', 'github', 'linkedin', 'twitter', 'microsoft'];
        $settings = [];

        foreach ($providers as $provider) {
            $settings[$provider] = [
                'enabled'      => $this->optionModel->getOption("oauth_{$provider}_enabled", false),
                'client_id'    => $this->optionModel->getOption("oauth_{$provider}_client_id", ''),
                'client_secret'=> $this->optionModel->getOption("oauth_{$provider}_client_secret", ''),
            ];
        }

        return $settings;
    }

    /**
     * Get email settings
     */
    private function getEmailSettings(): array
    {
        return [
            'protocol'   => $this->optionModel->getOption('email_protocol', 'smtp'),
            'smtp_host'  => $this->optionModel->getOption('email_smtp_host', ''),
            'smtp_port'  => $this->optionModel->getOption('email_smtp_port', '587'),
            'smtp_user'  => $this->optionModel->getOption('email_smtp_user', ''),
            'smtp_pass'  => $this->optionModel->getOption('email_smtp_pass', ''),
            'smtp_crypto'=> $this->optionModel->getOption('email_smtp_crypto', 'tls'),
        ];
    }

    /**
     * Get captcha settings
     */
    private function getCaptchaSettings(): array
    {
        return [
            'enabled'       => $this->optionModel->getOption('captcha_enabled', false),
            'recaptcha_enabled'=> $this->optionModel->getOption('recaptcha_enabled', false),
            'recaptcha_site_key'  => $this->optionModel->getOption('recaptcha_site_key', ''),
            'recaptcha_secret_key'=> $this->optionModel->getOption('recaptcha_secret_key', ''),
        ];
    }
}
