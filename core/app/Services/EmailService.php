<?php

declare(strict_types=1);

namespace ReallySimplePlugins\RSS\Core\Services;

use ReallySimplePlugins\RSS\Core\Support\Helpers\Storages\EnvironmentConfig;
use ReallySimplePlugins\RSS\Core\Support\Helpers\Storages\UriConfig;
use ReallySimplePlugins\RSS\Core\Traits\HasEncryption;

/**
 * todo: move mailer methods to this service after full refactor.
 */
class EmailService
{
    use HasEncryption;

    private ?\rsssl_mailer $mailer = null;
    protected EnvironmentConfig $env;
    protected UriConfig $uriConfig;

    public function __construct(EnvironmentConfig $environmentConfig, UriConfig $uriConfig)
    {
        $this->env = $environmentConfig;
        $this->uriConfig = $uriConfig;
    }

    /**
     * Method is used to lazyload the mailer property. This prevents overhead
     * but most importantly prevents _load_textdomain_just_in_time error
     */
    protected function getMailer(): \rsssl_mailer
    {
        if ($this->mailer instanceof \rsssl_mailer) {
            return $this->mailer;
        }

        require_once $this->env->getString('plugin.path') . '/mailer/class-mail.php';
        $this->mailer = new \rsssl_mailer();

        return $this->mailer;
    }

    /**
     * Set the email of the recipient.
     * @throws \InvalidArgumentException if email address is not valid
     */
    public function setEmail(string $email): void
    {
        $sanitizedEmail = sanitize_email($email);
        if (empty($sanitizedEmail)) {
            throw new \InvalidArgumentException("Email address \"$email\" not valid in " . __METHOD__);
        }

        $this->getMailer()->set_to($sanitizedEmail);
    }

    /**
     * Trigger the verification mail
     */
    public function sendVerificationMail(): array
    {
        return $this->getMailer()->send_verification_mail();
    }

    /**
     * Signup for Tips & Tricks from Really Simple Security
     * @return array|\WP_Error
     * @throws \InvalidArgumentException if email address is not valid
     */
    public function addEmailToMailingList(string $email)
    {
        $sanitizedEmail = sanitize_email($email);
        if (empty($sanitizedEmail)) {
            throw new \InvalidArgumentException("Email address \"$email\" not valid in " . __METHOD__);
        }

        $license = '';
        $hasPremium = defined('rsssl_pro');

        if ($hasPremium) {
            $license = RSSSL()->licensing->license_key();
            $license = $this->maybeDecryptPrefixed($license, 'really_simple_ssl_');
        }

        $payload = [
            'has_premium' => $hasPremium,
            'license' => $license,
            'email' => $sanitizedEmail,
            'domain' => esc_url_raw(site_url()),
        ];

        return wp_remote_post($this->uriConfig->getUrl('rsp.mailinglist'), [
            'timeout' => 15,
            'sslverify' => true,
            'body' => $payload
        ]);
    }

    /**
     * Get the email address to which notifications should be sent, based on user configuration.
     */
    public function getNotificationsEmail(): string
    {
        if (!function_exists('rsssl_get_option')) {
            return '';
        }

        return (string) rsssl_get_option('notifications_email_address', get_bloginfo('admin_email'));
    }

    /**
     * Check if the user has enabled email notifications in their settings.
     * @return bool True if email notifications are enabled, false otherwise.
     */
    public function isNotificationsEnabled(): bool
    {
        if (!function_exists('rsssl_get_option')) {
            return false;
        }

        return (bool) rsssl_get_option('send_notifications_email', false);
    }

    /**
     * Check if the email verification flow has been completed, based on the stored option.
     *
     * @return bool True if the email verification flow has been completed, false otherwise.
     */
    public function isEmailVerified(): bool
    {
        if (!\function_exists('get_option')) {
            return false;
        }

        $status = (string) \get_option('rsssl_email_verification_status', '');
        if ($status === 'completed') {
            return true;
        }

        if (\function_exists('is_multisite') && \is_multisite() && \function_exists('get_site_option')) {
            $networkStatus = (string) \get_site_option('rsssl_email_verification_status', '');
            if ($networkStatus === 'completed') {
                return true;
            }
        }

        return false;
    }
}
