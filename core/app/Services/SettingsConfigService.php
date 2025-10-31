<?php
namespace ReallySimplePlugins\RSS\Core\Services;

/**
 * This Settings-service class does NOT do any CRUD actions on the settings. It
 * is only responsible for doing business logic based on the fields config of
 * the settings. Like returning recommended settings.
 */
class SettingsConfigService
{
    /**
     * Returns recommended settings. Also includes Pro features when enabled.
     * @param bool $includeProFeatures To add/exclude recommended pro settings
     */
    public function getRecommendedSettings(bool $includeProFeatures = false): array
    {
        $features = [
            [
                'title' => esc_html__('Vulnerability scan', 'really-simple-ssl'),
                'id' => 'vulnerability_detection',
                'options' => ['enable_vulnerability_scanner'],
                'activated' => true,
            ],
            [
                'title' => esc_html__('Essential WordPress hardening', 'really-simple-ssl'),
                'id' => 'hardening',
                'options' => $this->getRecommendedHardeningSettings(),
                'activated' => true,
            ],
            [
                'title' => esc_html__('E-mail login', 'really-simple-ssl'),
                'id' => 'two_fa',
                'options' => ['login_protection_enabled'],
                'activated' => true,
            ],
            [
                'title' => esc_html__('Mixed Content Fixer', 'really-simple-ssl'),
                'id' => 'mixed_content_fixer',
                'options' => ['mixed_content_fixer'],
                'activated' => true,
            ],
        ];

        if ($includeProFeatures === false) {
            return $features;
        }

        $proFeatures = [
            [
                'title' => esc_html__('Firewall', 'really-simple-ssl'),
                'id' => 'firewall',
                'premium' => true,
                'options' => ['enable_firewall'],
                'activated' => true,
            ],
            [
                'title' => esc_html__('Two-Factor Authentication', 'really-simple-ssl'),
                'id' => 'two_fa',
                'premium' => true,
                'options' => ['login_protection_enabled'],
                'activated' => true,
            ],
            [
                'title' => esc_html__('Limit Login Attempts', 'really-simple-ssl'),
                'id' => 'limit_login_attempts',
                'premium' => true,
                'options' => ['enable_limited_login_attempts', 'enable_limited_password_reset_attempts'],
                'activated' => true,
            ],
            [
                'title' => esc_html__('Security Headers', 'really-simple-ssl'),
                'id' => 'advanced_headers',
                'premium' => true,
                'options' => [],
                'activated' => true,
            ],
        ];

        return array_merge($features, $proFeatures);
    }

    /**
     * Method returns all recommended setting id's in array format.
     * @example [disable_anyone_can_register, disable_file_editing]
     *
     * Currently, the only settings
     * with the 'recommended' key are basic hardening settings:
     * {@see /settings/config/fields/hardening-basic.php}
     *
     * @todo Kept business logic the same, but it needs a refactor to actually
     * get the hardening settings.
     */
    public function getRecommendedHardeningSettings(): array
    {
        $fields = rsssl_fields(false);

        $recommended = array_filter($fields, static function($field) {
            return isset($field['recommended']) && $field['recommended'];
        });

        return array_map(static function($field) {
            return $field['id'];
        }, $recommended);
    }

    /**
     * Method returns grouped settings per premium feature. Each item is an
     * array containing the related settings listed in the options key.
     *
     * @todo: Kept business logic the same, but shouldn't we add these to the
     * getRecommendedSettings method when $includeProFeatures equals true?
     */
    public function getRecommendedProSettings(): array
    {
        return [
            [
                'title' => esc_html__('Firewall', 'really-simple-ssl'),
                'id' => 'firewall',
                'premium' => true,
                'options' => ['enable_firewall'],
                'activated' => true,
            ],
            [
                'title' => esc_html__('Two-Factor Authentication', 'really-simple-ssl'),
                'id' => 'two_fa',
                'premium' => true,
                'options' => ['two_fa_enabled_roles_totp'],
                'value' => ['administrator'],
                'activated' => true,
            ],
            [
                'title' => esc_html__('Limit Login Attempts', 'really-simple-ssl'),
                'id' => 'limit_login_attempts',
                'premium' => true,
                'options' => ['enable_limited_login_attempts', 'enable_limited_password_reset_attempts'],
                'activated' => true,
            ],
            [
                'title' => esc_html__('Security Headers', 'really-simple-ssl'),
                'id' => 'advanced_headers',
                'premium' => true,
                'options' => [
                    'upgrade_insecure_requests',
                    'x_content_type_options',
                    'hsts',
                    ['x_xss_protection' => 'zero'],
                    'x_content_type_options',
                    ['x_frame_options' => 'SAMEORIGIN'],
                    ['referrer_policy' => 'strict-origin-when-cross-origin'],
                    ['csp_frame_ancestors' => 'self'],
                ],
                'activated' => true,
            ],
            [
                'title' => esc_html__('Vulnerability Measures', 'really-simple-ssl'),
                'id' => 'vulnerability_measures',
                'options' => ['enable_vulnerability_scanner', 'measures_enabled'],
                'activated' => true,
            ],
            [
                'title' => esc_html__('Advanced WordPress Hardening', 'really-simple-ssl'),
                'id' => 'advanced_hardening',
                'premium' => true,
                'options' => ['change_debug_log_location', 'disable_http_methods'],
                'activated' => true,
            ],
            [
                'title' => esc_html__('Strong Password policy', 'really-simple-ssl'),
                'id' => 'password_security',
                'options' => ['enforce_password_security_enabled', 'enable_hibp_check'],
                'activated' => true,
            ],
        ];
    }
}