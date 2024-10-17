<?php
/**
 * Two-Factor Authentication Data Parameters helper.
 *
 * @package REALLY_SIMPLE_SSL
 * @since 0.1-dev
 */

namespace RSSSL\Security\WordPress\Two_Fa;

/**
 * Class Rsssl_Two_FA_Data_Parameters
 *
 * Represents the data parameters for the Two FA data.
 *
 * @package REALLY_SIMPLE_SSL
 */
class Rsssl_Two_FA_Data_Parameters {

	/**
	 * The current page name.
	 *
	 * @var string $page The current page name.
	 */
	public $page;
	/**
	 * The number of items to display per page.
	 *
	 * @var int $page_size The number of items to display per page.
	 */
	public $page_size;
	/**
	 * The search term entered by the user.
	 *
	 * @var string $search_term The search term entered by the user
	 */
	public $search_term;
	/**
	 * The value used for filtering.
	 *
	 * @var string|null $filter_value This variable stores the value used for filtering.
	 */
	public $filter_value;
	/**
	 * The column used for filtering.
	 *
	 * @var string|null $filter_column This variable stores the column used for filtering.
	 */
	public $filter_column;
	/**
	 * The column used for sorting.
	 *
	 * @var string|null $sort_column This variable stores the column used for sorting.
	 */
	public $sort_column;
	/**
	 * The direction of the sorting, can be 'asc' or 'desc'.
	 *
	 * @var string $sort_direction The direction of the sorting, can be 'asc' or 'desc'
	 */
	public $sort_direction;
	/**
	 * The HTTP method used for the current request, can be 'GET', 'POST', 'PUT', 'DELETE', etc.
	 *
	 * @var string $method The HTTP method used for the current request, can be 'GET', 'POST', 'PUT', 'DELETE', etc.
	 */
	public $method;

	/**
	 * The allowed filters.
	 *
	 * @var array $allowed_filters The allowed filters.
	 */
	private const allowed_filters = array( 'all', 'open', 'disabled', 'active', 'expired' );

	/**
	 * Constructs a new object with given data.
	 *
	 * @param array $data The data array.
	 */
	public function __construct( array $data ) {
		$this->page           = isset( $data['currentPage'] ) ? (int) $data['currentPage'] : 1;
		$this->page_size      = isset( $data['currentRowsPerPage'] ) ? (int) $data['currentRowsPerPage'] : 5;
		$this->search_term    = isset( $data['search'] ) ? sanitize_text_field( $data['search'] ) : '';
		$this->filter_value   = in_array( $data['filterValue'] ?? 'all', self::allowed_filters, true ) ? sanitize_text_field( $data['filterValue'] ?? 'all') : 'all';
		$this->sort_direction = in_array( strtoupper( $data['sortDirection'] ?? 'DESC' ), array( 'ASC', 'DESC' ), true ) ? strtoupper( sanitize_text_field( $data['sortDirection'] ?? 'DESC')) : 'DESC';
		$this->filter_column  = isset( $data['filterColumn'] ) ? sanitize_text_field( $data['filterColumn'] ) : 'rsssl_two_fa_status';
		$this->sort_column    = isset( $data['sortColumn'] ) ? sanitize_text_field( $data['sortColumn'] ) : 'user';
		$this->method         = isset( $data['method'] ) ? Rsssl_Two_Factor_Settings::sanitize_method( $data['method'] ) : 'email';
	}
}
