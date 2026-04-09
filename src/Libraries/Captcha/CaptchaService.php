<?php

declare(strict_types=1);

namespace CIMembership\Libraries\Captcha;

use CIMembership\Models\OptionModel;
use Gregwar\Captcha\CaptchaBuilder;
use Gregwar\Captcha\PhraseBuilder;

/**
 * CAPTCHA Service
 *
 * Handles traditional CAPTCHA and reCAPTCHA verification.
 *
 * @package CIMembership\Libraries\Captcha
 */
class CaptchaService
{
    protected ?OptionModel $optionModel = null;
    protected ?CaptchaBuilder $builder = null;

    public function __construct()
    {
        $this->optionModel = model('OptionModel');
        $this->builder = new CaptchaBuilder();
    }

    /**
     * Check if captcha is enabled for specific action
     */
    public function isEnabled(string $action = 'login'): bool
    {
        $optionModel = $this->optionModel;

        // Check if reCAPTCHA is enabled
        if ($optionModel->getOption('recaptcha_enabled', false)) {
            return true;
        }

        // Check if traditional captcha is enabled
        return $optionModel->getOption('captcha_enabled', false);
    }

    /**
     * Check if using reCAPTCHA
     */
    public function isRecaptcha(): bool
    {
        return $this->optionModel->getOption('recaptcha_enabled', false);
    }

    /**
     * Generate captcha image
     */
    public function generate(): string
    {
        $phraseBuilder = new PhraseBuilder(6, '0123456789');
        $this->builder = new CaptchaBuilder(null, $phraseBuilder);

        $this->builder->build(200, 60);

        // Store in session
        session()->set('captcha_phrase', $this->builder->getPhrase());

        return $this->builder->inline();
    }

    /**
     * Verify captcha code
     */
    public function verify(string $code): bool
    {
        // Check reCAPTCHA first
        if ($this->isRecaptcha()) {
            return $this->verifyRecaptcha($code);
        }

        // Traditional captcha verification
        $sessionPhrase = session()->get('captcha_phrase');

        if (empty($sessionPhrase) || empty($code)) {
            return false;
        }

        $valid = strtolower($sessionPhrase) === strtolower($code);

        // Clear session after verification
        if ($valid) {
            session()->remove('captcha_phrase');
        }

        return $valid;
    }

    /**
     * Verify reCAPTCHA response
     */
    protected function verifyRecaptcha(string $token): bool
    {
        $secretKey = $this->optionModel->getOption('recaptcha_secret_key', '');

        if (empty($secretKey) || empty($token)) {
            return false;
        }

        $url = 'https://www.google.com/recaptcha/api/siteverify';
        $data = [
            'secret'   => $secretKey,
            'response' => $token,
            'remoteip' => service('request')->getIPAddress(),
        ];

        $options = [
            'http' => [
                'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
                'method'  => 'POST',
                'content' => http_build_query($data),
            ],
        ];

        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);

        if ($result === false) {
            return false;
        }

        $response = json_decode($result, true);
        return $response['success'] ?? false;
    }

    /**
     * Get reCAPTCHA site key
     */
    public function getSiteKey(): string
    {
        return $this->optionModel->getOption('recaptcha_site_key', '');
    }

    /**
     * Get reCAPTCHA script URL
     */
    public function getRecaptchaScript(): string
    {
        $siteKey = $this->getSiteKey();
        return "https://www.google.com/recaptcha/api.js?render={$siteKey}";
    }

    /**
     * Get captcha configuration for JavaScript
     */
    public function getJsConfig(): array
    {
        return [
            'enabled'    => $this->isEnabled(),
            'recaptcha'  => $this->isRecaptcha(),
            'siteKey'    => $this->getSiteKey(),
            'scriptUrl'  => $this->getRecaptchaScript(),
        ];
    }
}
