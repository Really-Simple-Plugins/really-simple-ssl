<?php

declare(strict_types=1);

namespace ReallySimplePlugins\RSS\Core\Services;

use ReallySimplePlugins\RSS\Core\Support\Helpers\Storages\UriConfig;

/**
 * Builds tracked links to our website, so outbound campaign attribution stays
 * consistent. Callers decide what gets tracked: every path passed in becomes a
 * tracked URL on our website.
 */
final class OutboundUrlService
{
    private const UTM_CAMPAIGNS = [
        'documentation' => 'plugin_documentation',
        'onboarding' => 'plugin_onboarding',
        'support' => 'plugin_support',
        'trial' => 'pro_trial',
        'upgrade' => 'free_to_pro',
    ];
    private const UTM_MEDIUM = 'plugin';
    private const UTM_SOURCE_PLATFORM = 'wordpress';
    private const SOURCE_FREE = 'really_simple_security_free';
    private const SOURCE_PRO = 'really_simple_security_pro';
    private const SOURCE_MULTISITE = 'really_simple_security_multisite';
    private const SOURCES_BY_PLUGIN_FILE = [
        'rlrsssl-really-simple-ssl.php' => self::SOURCE_FREE,
        'really-simple-ssl-pro.php' => self::SOURCE_PRO,
        'really-simple-ssl-pro-multisite.php' => self::SOURCE_MULTISITE,
    ];
    private const PARTNER_REFERENCES = [
        1 => 483, // Combell
        2 => 492, // Easyhost
        3 => 673, // Extendify
    ];
    private const EXTENDIFY_PLATFORM_ID = 3;
    // Public by design. The website uses it to decode the partner ID.
    private const EXTENDIFY_CAMPAIGN_KEY = 'C6E297149ABB6';

    private string $baseUrl;
    private string $pluginFile;
    private int $hostingPlatformId;
    private string $extendifyPartnerId;
    private bool $isPro;
    /** @var array<string, int|string>|null */
    private ?array $partnerParameters = null;

    public function __construct(
        UriConfig $uriConfig,
        ?string $pluginFile = null,
        ?int $hostingPlatformId = null,
        ?string $extendifyPartnerId = null,
        ?bool $isPro = null
    ) {
        $this->baseUrl = $uriConfig->getUrl('rsp.base_url');
        $this->pluginFile = $pluginFile ?? (defined('rsssl_plugin') ? (string) rsssl_plugin : '');
        $this->hostingPlatformId = $hostingPlatformId ?? (defined('HBRW_PLATFORM_ID') ? (int) HBRW_PLATFORM_ID : 0);
        $this->extendifyPartnerId = $extendifyPartnerId
            ?? (defined('EXTENDIFY_PARTNER_ID') && EXTENDIFY_PARTNER_ID ? (string) EXTENDIFY_PARTNER_ID : '');
        $this->isPro = $isPro ?? defined('rsssl_pro');
    }

    /**
     * A link to our website, tagged so the click shows up in analytics.
     *
     * @param string $path       Page on our website relative to the root, for
     *     example "pro" or "instructions/about-hsts". Query arguments and a
     *     fragment are kept. Use "/" for the website root.
     * @param string $campaign   Semantic key from UTM_CAMPAIGNS, for example
     *     "documentation" or "upgrade".
     * @param string $utmContent Lowercase snake_case ID of the place in the
     *     plugin where the user clicks the link. Never change it once shipped.
     *
     * @throws \InvalidArgumentException When the campaign is not a key in
     *     UTM_CAMPAIGNS.
     */
    public function getTrackedUrl(string $path, string $campaign, string $utmContent): string
    {
        return $this->addTracking(
            $this->baseUrl . $this->normalizePath($path),
            $this->utmCampaign($campaign),
            $utmContent
        );
    }

    /**
     * Our website redirects page URLs without a trailing slash (301), so every
     * page path gets exactly one, before any query or fragment. The root ("/")
     * becomes empty, because baseUrl already ends with a slash.
     */
    private function normalizePath(string $path): string
    {
        $pageLength = strcspn($path, '?#');
        $page = trim(substr($path, 0, $pageLength), '/');

        return ($page === '' ? '' : trailingslashit($page)) . substr($path, $pageLength);
    }

    /**
     * Add the UTM set to one of our URLs. On Free, also add the partner
     * parameters (ref and campaign). Existing query parameters stay.
     */
    private function addTracking(string $url, string $utmCampaign, string $utmContent): string
    {
        $parameters = array_merge(
            [
                'utm_source' => $this->utmSource(),
                'utm_medium' => self::UTM_MEDIUM,
                'utm_source_platform' => self::UTM_SOURCE_PLATFORM,
                'utm_campaign' => $utmCampaign,
                'utm_content' => $utmContent,
            ],
            $this->partnerParameters ??= $this->buildPartnerParameters()
        );

        // add_query_arg() runs urlencode_deep() over the parameters already on
        // the URL and merges ours in untouched, so encode ours the same way.
        // Skip it and the base64 partner campaign breaks: "+" arrives as a
        // space and the trailing "=" is stripped.
        return add_query_arg(urlencode_deep($parameters), $url);
    }

    /**
     * Translate a semantic campaign key into its utm_campaign value.
     * @throws \InvalidArgumentException When the key is not in UTM_CAMPAIGNS.
     */
    private function utmCampaign(string $campaign): string
    {
        if (!isset(self::UTM_CAMPAIGNS[$campaign])) {
            throw new \InvalidArgumentException(sprintf(
                'Unknown campaign "%s". Available campaigns: %s.',
                $campaign,
                implode(', ', array_keys(self::UTM_CAMPAIGNS))
            ));
        }

        return self::UTM_CAMPAIGNS[$campaign];
    }

    /**
     * The edition that sends the click: Free, Pro, or Multisite. The plugin
     * file name decides the edition. For an unknown file name, the rsssl_pro
     * constant decides between Pro and Free.
     */
    private function utmSource(): string
    {
        return self::SOURCES_BY_PLUGIN_FILE[basename($this->pluginFile)]
            ?? ($this->isPro ? self::SOURCE_PRO : self::SOURCE_FREE);
    }

    /**
     * Empty for Pro and Multisite: only Free is distributed through partners.
     * addTracking() keeps the result per instance, so the Extendify encryption
     * runs once and not for every link.
     *
     * @return array<string, int|string>
     */
    private function buildPartnerParameters(): array
    {
        if ($this->utmSource() !== self::SOURCE_FREE) {
            return [];
        }

        // Extendify takes precedence over the hosting platform this build was
        // made for.
        $partnerPlatformId = $this->extendifyPartnerId !== ''
            ? self::EXTENDIFY_PLATFORM_ID
            : $this->hostingPlatformId;

        $parameters = [];

        $reference = self::PARTNER_REFERENCES[$partnerPlatformId] ?? null;
        if ($reference !== null) {
            $parameters['ref'] = $reference;
        }

        // Empty without a partner ID or openssl. Then we send no campaign.
        $campaign = $this->encryptExtendifyPartnerId();
        if ($campaign !== '') {
            $parameters['campaign'] = $campaign;
        }

        return $parameters;
    }

    private function encryptExtendifyPartnerId(): string
    {
        if (strlen(trim($this->extendifyPartnerId)) === 0
            || !function_exists('openssl_random_pseudo_bytes')
            || !function_exists('openssl_cipher_iv_length')
            || !function_exists('openssl_encrypt')
        ) {
            return '';
        }

        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
        $encrypted = openssl_encrypt($this->extendifyPartnerId, 'aes-256-cbc', self::EXTENDIFY_CAMPAIGN_KEY, 0, $iv);
        return base64_encode($encrypted . '::' . $iv);
    }
}
