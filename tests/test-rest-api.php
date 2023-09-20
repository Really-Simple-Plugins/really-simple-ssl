<?php

/**
 * Class RSSSLAuthorizeRestApiRequestsTest
 *
 * Test class for authorize-rest-api-requests.php functions
 */
class RSSSLAuthorizeRestApiRequestsTest extends WP_UnitTestCase {
    /**
     * Set up the test environment
     */
    protected function setUp(): void {
        parent::setUp();
        require_once __DIR__ . '/../security/wordpress/rest-api.php';
    }

    /**
     * Test if the 'authorize_rest_api_requests' function is hooked to the 'rest_request_before_callbacks' filter
     */
    public function test_authorize_rest_api_requests_filter() {
        $this->assertNotFalse(has_filter('rest_request_before_callbacks', 'authorize_rest_api_requests'));
    }

    /**
     * Test the 'authorize_rest_api_requests' function
     *
     * This test checks various cases:
     * - without the 'authorization' header
     * - with the 'authorization' header but without an 'administrator' role
     * - with an 'administrator' role
     */
    public function test_authorize_rest_api_requests() {
        $request = new WP_REST_Request();
        $response = rest_ensure_response(null);
        $handler = array();

        // Test case without the 'authorization' header
        $result = authorize_rest_api_requests($response, $handler, $request);
        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertEquals('authorization', $result->get_error_code());

        // Test case with the 'authorization' header
        $request->set_header('authorization', 'Bearer some_token');
        $result = authorize_rest_api_requests($response, $handler, $request);

        // Since we don't have a user with 'administrator' role, the error will be 'forbidden'
        $this->assertInstanceOf(WP_Error::class, $result);
        $this->assertEquals('forbidden', $result->get_error_code());

        // Add a new user with 'administrator' role
        $user_id = $this->factory->user->create(array('role' => 'administrator'));
        wp_set_current_user($user_id);

        // Test case with a user with 'administrator' role
        $result = authorize_rest_api_requests($response, $handler, $request);
        $this->assertEquals($response, $result);
    }
}