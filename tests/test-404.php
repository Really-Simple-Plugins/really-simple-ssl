<?php

class TestUrls extends WP_UnitTestCase {

	public function test_external_links() {
		// Set the base directory path where your plugin files are located
		$plugin_dir = dirname( __FILE__, 2 );

		// Define the regular expression pattern to match links
		$link_pattern = '/https:\/\/\S+(?=[\'"])/';

		// Set URLS and failed_urls
		$urls        = [];
		$failed_urls = [];

		// Use RecursiveIteratorIterator/RegexIterator classes to get all .php files in root + recursive plugin directories
		// This proved more reliable than using glob()
		$iterator  = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $plugin_dir ) );
		$php_files = new RegexIterator( $iterator, '/^.+\.php$/i', RecursiveRegexIterator::GET_MATCH );

		// Now loop through these files. Exclude /vendor and /node_modules as we only want links from the plugin itself and not from dependencies
		foreach ( $php_files as $file ) {
			if ( strpos( $file[0], 'node_modules' ) !== false || strpos( $file[0], 'vendor' ) !== false ) {
				continue;
			}

			// Use the regular expression $link_pattern to match all links
			preg_match_all( $link_pattern, file_get_contents( $file[0] ), $matches );

			// Add each matched link to the URLs array
			foreach ( $matches[0] as $link ) {
				if ( strpos( $link, 'https://' ) === 0 ) {
					// First remove excess HTML from links in PHP code using "><img src=" for example.
					$link = strip_tags( $link );
					// Then cut off the link after ? to exclude mtmcampaign etc.
					$link = preg_replace( '/^([^?]+)\??.*$/', '$1', $link );
					// Finally, replace all characters that should not be present in an URL, leaving only the following: forward /, word characters (\w), a period (.), a colon (:), a hyphen (-), or a hash symbol (#) with an empty string.
					// This is done to strip artifacts from extracted URLS in PHP code, e.g. " ' < > \ etc.
					$link   = trim( preg_replace( '/[^\/\w.:#\-]/', '', $link ) );
					$urls[] = $link;
				}
			}
		}

		// Remove duplicates
		$urls = array_unique( $urls );

		// Now filter the links to only include links from the following domains:
		// (scan.)really-simple-ssl.com, complianz.io, burst-statistics.com, really-simple-plugins.com, ziprecipes.net and wordpress.org
		$urls = array_filter( $urls, static function ( $url ) {
			return preg_match( '/^https?:\/\/(?:really-simple-ssl\.com|scan\.really-simple-ssl\.com|complianz\.io|burst-statistics\.com|really-simple-plugins\.com|ziprecipes\.net|wordpress\.org)/', $url );
		} );

		// Loop through each URL and make an HTTP request
		foreach ( $urls as $link ) {
			// Wait for a random amount of time (200-500ms) between requests to prevent hammering the servers
			usleep( rand( 200000, 500000 ) );
			// Now retrieve the webpage for a link
			$response = wp_remote_get( $link );

			// Add to failed URLs on WP error
			if ( is_wp_error( $response ) ) {
				$failed_urls[] = $link;
				error_log( "Failed to fetch URL: " . $link );
			} else {
				// Get status code
				$status_code = wp_remote_retrieve_response_code( $response );
				// Add to failed on 404
				if ( $status_code === 404 ) {
					$failed_urls[] = $link;
					error_log( "URL returned 404: " . $link );
				} else {
					// This link works!
//					error_log("Success for URL $link");
				}
			}
		}

		error_log( "Failed URLs" );
		error_log( print_r( $failed_urls, true ) );

		// Check if failed_urls is empty. Is it empty? Congratulations! Test passed!
		if ( empty( $failed_urls ) ) {
			$this->pass( "Test passed! No URLs result in a 404 error" );
		} else {
			// Fail the test if any URLs did not pass our checks
			$this->fail( "Test Failed! Failed to fetch the following URLs:\n" . implode( "\n", $failed_urls ) );
		}
	}
}