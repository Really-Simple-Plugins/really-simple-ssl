<?php
namespace  RSSSL\Security\WordPress\Two_Fa;

use RSSSL\Pro\Security\WordPress\Passkey\Rsssl_Public_Credential_Resource;
use WP_List_Table;

if ( ! class_exists( 'WP_List_Table' ) ) {
    require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

class RSSSL_Passkey_List_Table extends WP_List_Table {

    public function __construct() {
        parent::__construct([
            'singular' => __('Device', 'really-simple-ssl'),
            'plural'   => __('Devices', 'really-simple-ssl'),
            'ajax'     => true
        ]);
    }

	/**
	 * Get the columns
	 * @return array
	 */
    public function get_columns(): array
    {
        return [
            'device_name' => __('Device Name', 'really-simple-ssl'),
            'registered'  => __('Registered', 'really-simple-ssl'),
            'last_used'   => __('Last Used', 'really-simple-ssl'),
            'actions'     => __('Actions', 'really-simple-ssl')
        ];
    }

	/**
     * Prepare the items
     *
     * @return void
     */
    public function prepare_items(array $data = []) :void
    {
        $columns = $this->get_columns();
        $hidden = [];
        $sortable = [
            'registered' => ['registered', false],
            'last_used'  => ['last_used', false]
        ];

        $this->_column_headers = [$columns, $hidden, $sortable];
        $this->items = $data; // Assigning data to be used in display_rows()
    }

	/**
	 * Default column value
	 *
	 * @param array $item
	 * @param string $column_name
	 *
	 * @return string
	 */
    public function column_default($item, $column_name): string
    {
        switch ($column_name) {
            case 'device_name':
                return esc_html($item['device_name']);
            case 'registered':
                return esc_html($item['registered']);
            case 'last_used':
                return esc_html($item['last_used']);
            case 'actions':
                return sprintf(
                    '<form method="post" class="rsssl-remove-passkey-form" style="display:inline;">
                         <input type="hidden" name="device_id" value="%s" />
                         <button type="button" class="button rsssl-remove-passkey" data-device-id="%s">%s</button>
                     </form>',
                    esc_attr($item['id']),
                    esc_attr($item['id']),
                    esc_html__('Remove', 'really-simple-ssl')
                );
            default:
                return print_r($item, true);
        }
    }

	/**
	 * Display the rows or placeholder
	 * @return void
	 */
    public function display_rows_or_placeholder(): void
    {
        if (!empty($this->items)) {
            echo '<tbody id="rsssl-passkey-list">';
            $this->display_rows();
        } else {
            echo '<tbody id="rsssl-passkey-list" class="no-items">';
        }
	    echo '</tbody>';
    }

	/**
	 * Display the table navigation
	 * @param $which
	 *
	 * @return void
	 */
    public function display_tablenav($which): void
    {
        if ('top' === $which) {
            echo '<div class="passkey-datatable">';
            echo '<h1 class="passkey-datatable-title">' . esc_html__('Passkeys', 'really-simple-ssl') . '</h1>';
            echo '<a id="rsssl-add-passkey-button" data-skip_redirect="true" class="button passkey-registration-button">' . esc_html__('Add Device', 'really-simple-ssl') . '</a>';
            echo '</div>';
        }
    }

	/**
	 * Display the table
	 * @return void
	 */
	public function display(): void
	{
        $this->display_tablenav('top');
        echo '<table class="wp-list-table ' . implode(' ', $this->get_table_classes()) . '">';
        $this->display_header();
        $this->display_rows_or_placeholder();
        $this->display_footer();
        echo '</table>';
        $this->display_tablenav('bottom');
    }

	/**
	 * Display the header of the table
	 * @return void
	 */
    protected function display_header(): void {
        echo '<thead>';
        $this->print_column_headers();
        echo '</thead>';
    }

	/**
	 * Display the footer of the table
	 * @return void
	 */
    protected function display_footer(): void {
        echo '<tfoot>';
        $this->print_column_headers(false);
        echo '</tfoot>';
    }

	/**
     * Display the passkey table
     *
     * @return void
     */
    public static function display_table(array $data = []): void {
        $list_table = new self();
        $list_table->prepare_items($data);
        $list_table->display();
    }
}


add_action('wp_ajax_remove_passkey', 'remove_passkey_callback');

/**
 * Remove passkey callback
 *
 * @return void
 */
function remove_passkey_callback() {
    $device_id = isset($_POST['device_id']) ? (int) $_POST['device_id'] : 0;

    if ($device_id > 0) {
	    $resource = Rsssl_Public_Credential_Resource::get_instance();
	    if (is_null($resource)) {
		    wp_send_json_error(['message' => __('Resource not found', 'really-simple-ssl')]);
		    return;
	    }
	    $resource->delete($device_id);
        wp_send_json_success(['message' => __('Device removed successfully', 'really-simple-ssl')]);
    } else {
        wp_send_json_error(['message' => __('Invalid device ID', 'really-simple-ssl')]);
    }
}