<?php

class RSSSLUserEnumerationTest extends WP_UnitTestCase {
    public function setUp(): void {
        parent::setUp();
        require_once __DIR__ . '/../security/wordpress/user-enumeration.php';
    }

    /**
     * Test if rsssl_check_user_enumeration is hooked to the 'init' action.
     *
     * @return void
     */
    public function test_rsssl_check_user_enumeration() {
        $this->assertTrue(has_action('init', 'rsssl_check_user_enumeration') !== false);
    }

    /**
     * Test if rsssl_remove_author_from_yoast_sitemap filter works as expected.
     *
     * @return void
     */
    public function test_rsssl_remove_author_from_yoast_sitemap() {
        // Mock the wpseo_sitemap_exclude_author filter
        add_filter('wpseo_sitemap_exclude_author', 'rsssl_remove_author_from_yoast_sitemap', 10, 1);

        $this->assertNotFalse(has_filter('wpseo_sitemap_exclude_author', 'rsssl_remove_author_from_yoast_sitemap'));
        $this->assertSame(false, apply_filters('wpseo_sitemap_exclude_author', true));
    }

    /**
     * Test if rsssl_rest_endpoints_callback function works as expected.
     *
     * @return void
     */
    public function rsssl_test_rest_endpoints_callback($endpoints) {
        if ( !is_user_logged_in() || !current_user_can('edit_posts') ) {
            if ( isset( $endpoints['/wp/v2/users'] ) ) {
                unset( $endpoints['/wp/v2/users'] );
            }
            if ( isset( $endpoints['/wp/v2/users/(?P[\d]+)'] ) ) {
                unset( $endpoints['/wp/v2/users/(?P[\d]+)'] );
            }
        }
        return $endpoints;
    }

    /**
     * Test if rsssl_wp_sitemaps_add_provider filter works as expected.
     *
     * @return void
     */
    public function test_rsssl_rest_endpoints_filter() {
        $endpoints = [
            '/wp/v2/users' => 'some_value',
            '/wp/v2/users/(?P[\d]+)' => 'some_value',
        ];

        // Add the helper function to the 'rest_endpoints' filter
        add_filter('rest_endpoints', [$this, 'rsssl_test_rest_endpoints_callback']);

        $filtered_endpoints = apply_filters('rest_endpoints', $endpoints);
        $this->assertFalse(isset($filtered_endpoints['/wp/v2/users']));
        $this->assertFalse(isset($filtered_endpoints['/wp/v2/users/(?P[\d]+)']));
    }

    public function test_rsssl_wp_sitemaps_add_provider() {
        $provider = 'some_value';
        add_filter('wp_sitemaps_add_provider', function($provider, $name) {
            if ('users' === $name) {
                return false;
            }
            return $provider;
        }, 10, 2);
        $filtered_provider = apply_filters('wp_sitemaps_add_provider', $provider, 'users');
        $this->assertFalse($filtered_provider);
    }
}