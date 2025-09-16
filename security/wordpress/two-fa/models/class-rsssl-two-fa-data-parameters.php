<?php
namespace RSSSL\Security\WordPress\Two_Fa\Models;

use RSSSL\Security\WordPress\Two_Fa\Rsssl_Two_Factor_Settings;
use WP_Roles as WP_RolesAlias;

class Rsssl_Two_FA_Data_Parameters {
    // Other properties initialized in your constructor...
    public int $page;
    public int $page_size;
    public string $search_term;
    public ?string $filter_value;
    public ?string $filter_column;
    public ?string $sort_column;
    public string $sort_direction;
    public string $method;
    public int $number;
    public int $offset;
    public int $negative_count;
    public string $role_filter;

    // Optional properties are declared as private and nullable.
    public ?array $enabled_roles = null;
    public ?array $forced_roles = null;
    public ?int $days_threshold = null;

    public function __construct( array $data ) {
        // Your existing initialization logic...
        $this->page        = isset($data['currentPage']) ? (int)$data['currentPage'] : 1;
        $this->page_size   = isset($data['currentRowsPerPage']) ? (int)$data['currentRowsPerPage'] : 5;
        $this->search_term = isset($data['search']) ? sanitize_text_field($data['search']) : '';

        $allowed_filters = array_map('strtolower', array_values((new WP_RolesAlias())->get_names()));
        $this->filter_value = in_array($data['filterValue'] ?? 'all', $allowed_filters, true)
            ? sanitize_text_field($data['filterValue'] ?? 'all')
            : 'all';
        $this->sort_direction = in_array(strtoupper($data['sortDirection'] ?? 'DESC'), ['ASC', 'DESC'], true)
            ? strtoupper(sanitize_text_field($data['sortDirection'] ?? 'DESC'))
            : 'DESC';
        $this->filter_column  = isset($data['filterColumn']) ? sanitize_text_field($data['filterColumn']) : 'user_role';
        $this->sort_column    = isset($data['sortColumn']) ? sanitize_text_field($data['sortColumn']) : 'user';
        $this->method         = isset($data['method']) ? Rsssl_Two_Factor_Settings::sanitize_method($data['method']) : 'email';
        $this->number         = isset($data['number']) ? (int)$data['number'] : 100;
        $this->offset         = isset($data['offset']) ? (int)$data['offset'] : 0;
        $this->negative_count = isset($data['negative_count']) ? (int)$data['negative_count'] : 0;
        $this->role_filter    = isset($data['role_filter']) ? sanitize_text_field($data['role_filter']) : 'all';
    }

    /**
     * Lazy getter for enabled roles.
     */
    public function getEnabledRoles(): array {
        if ($this->enabled_roles === null) {
            // if the passkey is enabled all roles are enabled
            if (defined('rsssl_pro') && rsssl_get_option('enable_passkey_login', false)) {
                $this->enabled_roles = array_map('strtolower', array_values((new WP_RolesAlias())->get_names()));
            } else {
                $this->enabled_roles = array_unique(array_merge(
                    defined('rsssl_pro') ? rsssl_get_option('two_fa_enabled_roles_totp', []) : [],
                    rsssl_get_option('two_fa_enabled_roles_email', [])
                ));
            }
        }
        return $this->enabled_roles;
    }

    /**
     * Lazy getter for forced roles.
     */
    public function getForcedRoles(): array {
        if ($this->forced_roles === null) {
            $this->forced_roles = rsssl_get_option('two_fa_forced_roles', []);
        }
        return $this->forced_roles;
    }

    /**
     * Lazy getter for days threshold.
     */
    public function getDaysThreshold(): int {
        if ($this->days_threshold === null) {
            $this->days_threshold = (int) rsssl_get_option('two_fa_grace_period', 30);
        }
        return $this->days_threshold;
    }

    /**
     * Set the number of items to retrieve.
     *
     * @return Rsssl_Two_FA_Data_Parameters
     */
    public function setOffset(int $offset): self {
        $this->offset = $offset;
        return $this;
    }

    /**
     * Set the number of items to retrieve.
     *
     * @return Rsssl_Two_FA_Data_Parameters
     */
    public function setNumber(int $batch_size): self
    {
        $this->number = $batch_size;
        return $this;
    }

    public function toArray() {
        return [
            'currentPage'       => $this->page,
            'currentRowsPerPage' => $this->page_size,
            'search'            => $this->search_term,
            'filterValue'       => $this->filter_value,
            'filterColumn'      => $this->filter_column,
            'sortColumn'        => $this->sort_column,
            'sortDirection'     => $this->sort_direction,
            'method'            => $this->method,
            'number'            => $this->number,
            'offset'            => $this->offset,
            'negative_count'    => $this->negative_count,
            'role_filter'       => $this->role_filter,
        ];

    }
}