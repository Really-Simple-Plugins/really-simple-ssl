<?php

declare(strict_types=1);

namespace ReallySimplePlugins\RSS\Core\Services;

use ReallySimplePlugins\RSS\Core\Bootstrap\App;
use ReallySimplePlugins\RSS\Core\Traits\HasEncryption;

/**
 * todo: move mailer methods to this service after full refactor.
 */
class EmailService
{
    use HasEncryption;

    private ?\rsssl_mailer $mailer = null;
    protected App $app;

    public function __construct(App $app)
    {
        $this->app = $app;
    }

    /**
     * Method is used to lazyload the mailer property. This prevents overhead
     * but most importantly prevents _load_textdomain_just_in_time error
     */
    private function getMailer(): \rsssl_mailer
    {
        if ($this->mailer instanceof \rsssl_mailer) {
            return $this->mailer;
        }

        require_once $this->app->config->getString('env.plugin.path') . '/mailer/class-mail.php';
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
            $license = $this->maybeDecryptPrefixed($license , 'really_simple_ssl_');
        }

        $payload = [
            'has_premium' => $hasPremium,
            'license' => $license,
            'email' => $sanitizedEmail,
            'domain' => esc_url_raw(site_url()),
        ];

        return wp_remote_post($this->app->config->getUrl('uri.rsp.mailinglist'), [
            'timeout' => 15,
            'sslverify' => true,
            'body' => $payload
        ]);
    }
}