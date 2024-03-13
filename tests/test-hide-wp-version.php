<?php

class RssslRemoveWPVersionTest extends WP_UnitTestCase {

    /**
     * Set up the test environment.
     *
     * @return void
     */
    public function setUp(): void {
        parent::setUp();
        require_once __DIR__ . '/../security/wordpress/hide-wp-version.php';
        add_filter('rsssl_fixer_output', array( RSSSL_SECURITY()->components['hide-wp-version'], 'replace_wp_version') );
    }

    /**
     * Test if rsssl_remove_wp_version is hooked to the 'init' action.
     *
     * @return void
     */
    public function test_rsssl_remove_wp_version_hooked() {
        $this->assertNotFalse(has_action('init', array( RSSSL_SECURITY()->components['hide-wp-version'], 'remove_wp_version')));
    }

    /**
     * Test if replace_wp_version function works as expected
     * and is hooked to the 'rsssl_fixer_output' filter.
     *
     * @return void
     */
    public function test_rsssl_replace_wp_version_hooked() {
        $wp_version = get_bloginfo('version');
	    $new_version = RSSSL_SECURITY()->components['hide-wp-version']->generate_rand_version();

        $html = '<link rel="stylesheet" href="http://example.org/wp-includes/css/style.css?ver=' . $wp_version . '" />';
        $expected_html = '<link rel="stylesheet" href="http://example.org/wp-includes/css/style.css?ver=' . $new_version . '" />';

        $result = RSSSL_SECURITY()->components['hide-wp-version']->replace_wp_version($html);
        $this->assertEquals($expected_html, $result);

        // Ensure the filter is hooked
        $this->assertNotFalse(has_filter('rsssl_fixer_output', array(RSSSL_SECURITY()->components['hide-wp-version'], 'replace_wp_version')));
    }

    /**
     * Test if rsssl_remove_css_js_version function works as expected.
     *
     * @return void
     */
    public function test_rsssl_remove_css_js_version() {
        $wp_version = get_bloginfo('version');
        $new_version = RSSSL_SECURITY()->components['hide-wp-version']->generate_rand_version();

        $test_src = trailingslashit(site_url()) . 'wp-includes/js/jquery/jquery.min.js?ver=' . $wp_version;
        $expected_src = trailingslashit(site_url()) . 'wp-includes/js/jquery/jquery.min.js?ver=' . $new_version;
        $result = RSSSL_SECURITY()->components['hide-wp-version']->remove_css_js_version($test_src);

        $this->assertEquals($expected_src, $result);

        // Test when the source does not contain 'wp-includes' or '?ver='
        $test_src = trailingslashit(site_url()) . 'some-js-file.js';
        $result = RSSSL_SECURITY()->components['hide-wp-version']->remove_css_js_version($test_src);
        $this->assertEquals($test_src, $result);
    }
}