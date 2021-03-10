<?php

defined( 'ABSPATH' ) or die( "you do not have access to this page!" );

if ( ! function_exists( 'rsssl_user_can_manage' ) ) {
    function rsssl_user_can_manage() {
        if ( ! is_user_logged_in() ) {
            return false;
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            return false;
        }

        return true;
    }
}

if ( !function_exists('rsssl_settings_page') ) {
    function rsssl_settings_page(){
            return add_query_arg(array('page' => 'rlrsssl_really_simple_ssl'), admin_url('options-general.php?page=') );
    }
}

if ( ! function_exists( 'rsssl_get_value' ) ) {

    /**
     * Get value for an a rsssl option
     * For usage very early in the execution order, use the $page option. This bypasses the class usage.
     *
     * @param string $fieldname
     * @param bool|int $post_id
     * @param bool|string $page
     * @param bool $use_default
     * @param bool $use_translate
     *
     * @return array|bool|mixed|string
     */

    function rsssl_get_value(
        $fieldname, $post_id = false, $page = false, $use_default = true, $use_translate = true
    ) {
        if ( ! is_numeric( $post_id ) ) {
            $post_id = false;
        }

        if ( ! $page && ! isset( RSSSL()->rsssl_config->fields[ $fieldname ] ) ) {
            return false;
        }

        //if  a post id is passed we retrieve the data from the post
        if ( ! $page ) {
            $page = RSSSL()->rsssl_config->fields[ $fieldname ]['source'];
        }

        $fields = get_option( 'rsssl_options_' . $page );
        $default = ( $use_default && $page && isset( RSSSL()->rsssl_config->fields[ $fieldname ]['default'] ) )
            ? RSSSL()->rsssl_config->fields[ $fieldname ]['default'] : '';

        $value   = isset( $fields[ $fieldname ] ) ? $fields[ $fieldname ] : $default;


        /*
         * Translate output
         *
         * */
        if ($use_translate) {

            $type = isset(RSSSL()->rsssl_config->fields[$fieldname]['type'])
                ? RSSSL()->rsssl_config->fields[$fieldname]['type'] : false;
            if ($type === 'cookies' || $type === 'thirdparties'
                || $type === 'processors'
            ) {
                if (is_array($value)) {

                    //this is for example a cookie array, like ($item = cookie("name"=>"_ga")

                    foreach ($value as $item_key => $item) {
                        //contains the values of an item
                        foreach ($item as $key => $key_value) {
                            if (function_exists('pll__')) {
                                $value[$item_key][$key] = pll__($item_key . '_'
                                    . $fieldname
                                    . "_" . $key);
                            }
                            if (function_exists('icl_translate')) {
                                $value[$item_key][$key]
                                    = icl_translate('rsssl',
                                    $item_key . '_' . $fieldname . "_" . $key,
                                    $key_value);
                            }

                            $value[$item_key][$key]
                                = apply_filters('wpml_translate_single_string',
                                $key_value, 'rsssl',
                                $item_key . '_' . $fieldname . "_" . $key);
                        }
                    }
                }
            } else {
                if (isset(RSSSL()->rsssl_config->fields[$fieldname]['translatable'])
                    && RSSSL()->rsssl_config->fields[$fieldname]['translatable']
                ) {
                    if (function_exists('pll__')) {
                        $value = pll__($value);
                    }
                    if (function_exists('icl_translate')) {
                        $value = icl_translate('rsssl', $fieldname, $value);
                    }

                    $value = apply_filters('wpml_translate_single_string', $value,
                        'rsssl', $fieldname);
                }
            }

        }

        return $value;
    }
}


if ( ! function_exists( 'rsssl_intro' ) ) {

    /**
     * @param string $msg
     *
     * @return string|void
     */

    function rsssl_intro( $msg ) {
        if ( $msg == '' ) {
            return;
        }
        $html = "<div class='rsssl-panel rsssl-notification rsssl-intro'>{$msg}</div>";

        echo $html;

    }
}

if ( ! function_exists( 'rsssl_notice' ) ) {
    /**
     * Notification without arrow on the left. Should be used outside notifications center
     * @param string $msg
     * @param string $type notice | warning | success
     * @param bool   $remove_after_change
     * @param bool   $echo
     * @param array  $condition $condition['question'] $condition['answer']
     *
     * @return string|void
     */
    function rsssl_notice( $msg, $type = 'notice', $remove_after_change = false, $echo = true, $condition = false) {
        if ( $msg == '' ) {
            return;
        }

        // Condition
        $condition_check = "";
        $condition_question = "";
        $condition_answer = "";
        $rsssl_hidden = "";
        if ($condition) {
            $condition_check = "condition-check";
            $condition_question = "data-condition-question='{$condition['question']}'";
            $condition_answer = "data-condition-answer='{$condition['answer']}'";
            $args['condition'] = array($condition['question'] => $condition['answer']);
            $rsssl_hidden = rsssl_field::this()->condition_applies($args) ? "" : "rsssl-hidden";;
        }

        // Hide
        $remove_after_change_class = $remove_after_change ? "rsssl-remove-after-change" : "";

        $html = "<div class='rsssl-panel-wrap'><div class='rsssl-panel rsssl-notification rsssl-{$type} {$remove_after_change_class} {$rsssl_hidden} {$condition_check}' {$condition_question} {$condition_answer}><div>{$msg}</div></div></div>";

        if ( $echo ) {
            echo $html;
        } else {
            return $html;
        }
    }
}

if ( ! function_exists( 'rsssl_sidebar_notice' ) ) {
    /**
     * @param string $msg
     * @param string $type notice | warning | success
     * @param bool   $remove_after_change
     * @param bool   $echo
     * @param bool|array  $condition $condition['question'] $condition['answer']
     *
     * @return string|void
     */

    function rsssl_sidebar_notice( $msg, $type = 'notice', $remove_after_change = false, $echo = true, $condition = false) {
        if ( $msg == '' ) {
            return;
        }

        // Condition
        $condition_check = "";
        $condition_question = "";
        $condition_answer = "";
        $rsssl_hidden = "";
        if ($condition) {
            $condition_check = "condition-check";
            $condition_question = "data-condition-question='{$condition['question']}'";
            $condition_answer = "data-condition-answer='{$condition['answer']}'";
            $args['condition'] = array($condition['question'] => $condition['answer']);
            $rsssl_hidden = rsssl_field::this()->condition_applies($args) ? "" : "rsssl-hidden";;
        }

        // Hide
        $remove_after_change_class = $remove_after_change ? "rsssl-remove-after-change" : "";

        $html = "<div class='rsssl-help-modal rsssl-notice rsssl-{$type} {$remove_after_change_class} {$rsssl_hidden} {$condition_check}' {$condition_question} {$condition_answer}>{$msg}</div>";

        if ( $echo ) {
            echo $html;
        } else {
            return $html;
        }
    }
}

if ( ! function_exists( 'rsssl_localize_date' ) ) {

    function rsssl_localize_date( $date ) {
        $month             = date( 'F', strtotime( $date ) ); //june
        $month_localized   = __( $month ); //juni
        $date              = str_replace( $month, $month_localized, $date );
        $weekday           = date( 'l', strtotime( $date ) ); //wednesday
        $weekday_localized = __( $weekday ); //woensdag
        $date              = str_replace( $weekday, $weekday_localized, $date );

        return $date;
    }
}

if (!function_exists('rsssl_read_more')) {
    /**
     * Create a generic read more text with link for help texts.
     *
     * @param string $url
     * @param bool   $add_space
     *
     * @return string
     */
    function rsssl_read_more( $url, $add_space = true ) {
        $html
            = sprintf( __( "For more information on this subject, please read this %sarticle%s",
            'really-simple-ssl' ), '<a target="_blank" href="' . $url . '">',
            '</a>' );
        if ( $add_space ) {
            $html = '&nbsp;' . $html;
        }

        return $html;
    }
}


if ( ! function_exists( 'rsssl_get_regions' ) ) {
    /**
     * At this moment, only one document, for all regions
     * @return array
     */
    function rsssl_get_regions() {
        $output['all'] = __( 'All regions', 'really-simple-ssl' );

        return $output;
    }
}

register_activation_hook( __FILE__, 'rsssl_set_activation_time_stamp' );
if ( ! function_exists( 'rsssl_set_activation_time_stamp' ) ) {
    function rsssl_set_activation_time_stamp( $networkwide ) {
        update_option( 'rsssl_activation_time', time() );
    }
}

if ( ! function_exists( 'rsssl_allowed_html' ) ) {
    function rsssl_allowed_html() {

        $allowed_tags = array(
            'a'          => array(
                'class'  => array(),
                'href'   => array(),
                'rel'    => array(),
                'title'  => array(),
                'target' => array(),
                'id' => array(),
            ),
            'button'     => array(
                'id'  => array(),
                'class'  => array(),
                'href'   => array(),
                'rel'    => array(),
                'title'  => array(),
                'target' => array(),
            ),
            'b'          => array(),
            'br'         => array(),
            'blockquote' => array(
                'cite' => array(),
            ),
            'div'        => array(
                'class' => array(),
                'id'    => array(),
            ),
            'h1'         => array(),
            'h2'         => array(),
            'h3'         => array(),
            'h4'         => array(),
            'h5'         => array(),
            'h6'         => array(),
            'i'          => array(),
            'input'      => array(
                'type'        => array(),
                'class'       => array(),
                'id'          => array(),
                'required'    => array(),
                'value'       => array(),
                'placeholder' => array(),
                'data-category' => array(),
                'style' => array(
                    'color' => array(),
                ),			),
            'img'        => array(
                'alt'    => array(),
                'class'  => array(),
                'height' => array(),
                'src'    => array(),
                'width'  => array(),
            ),
            'label'      => array(
                'for' => array(),
                'class' => array(),
                'style' => array(
                    'visibility' => array(),
                ),
            ),
            'li'         => array(
                'class' => array(),
                'id'    => array(),
            ),
            'ol'         => array(
                'class' => array(),
                'id'    => array(),
            ),
            'p'          => array(
                'class' => array(),
                'id'    => array(),
            ),
            'span'       => array(
                'class' => array(),
                'title' => array(),
                'style' => array(
                    'color' => array(),
                    'display' => array(),
                ),
                'id'    => array(),
            ),
            'strong'     => array(),
            'table'      => array(
                'class' => array(),
                'id'    => array(),
            ),
            'tr'         => array(),
            'svg'         => array(
                'width' => array(),
                'height' => array(),
                'viewBox' => array(),
            ),
            'polyline'    => array(
                'points' => array(),

            ),
            'path'    => array(
                'd' => array(),

            ),
            'style'      => array(),
            'td'         => array( 'colspan' => array(), 'scope' => array() ),
            'th'         => array( 'scope' => array() ),
            'ul'         => array(
                'class' => array(),
                'id'    => array(),
            ),
        );

        return apply_filters( "rsssl_allowed_html", $allowed_tags );
    }
}

/**
 * Check if this field is translatable
 *
 * @param $fieldname
 *
 * @return bool
 */

if ( ! function_exists( 'rsssl_translate' ) ) {
    function rsssl_translate( $value, $fieldname ) {
        if ( function_exists( 'pll__' ) ) {
            $value = pll__( $value );
        }

        if ( function_exists( 'icl_translate' ) ) {
            $value = icl_translate( 'rsssl', $fieldname, $value );
        }

        $value = apply_filters( 'wpml_translate_single_string', $value, 'rsssl', $fieldname );

        return $value;
    }
}

if ( ! function_exists( 'rsssl_sanitize_language' ) ) {

    /**
     * Validate a language string
     *
     * @param $language
     *
     * @return bool|string
     */

    function rsssl_sanitize_language( $language ) {
        $pattern = '/^[a-zA-Z]{2}$/';
        if ( ! is_string( $language ) ) {
            return false;
        }
        $language = substr( $language, 0, 2 );

        if ( (bool) preg_match( $pattern, $language ) ) {
            $language = strtolower( $language );

            return $language;
        }

        return false;
    }
}

if ( ! function_exists( 'rsssl_uses_gutenberg' ) ) {
    function rsssl_uses_gutenberg() {

        if ( function_exists( 'has_block' )
            && ! class_exists( 'Classic_Editor' )
        ) {
            return true;
        }

        return false;
    }
}

if ( ! function_exists( 'rsssl_array_filter_multidimensional' ) ) {
    function rsssl_array_filter_multidimensional(
        $array, $filter_key, $filter_value
    ) {
        $new = array_filter( $array,
            function ( $var ) use ( $filter_value, $filter_key ) {
                return isset( $var[ $filter_key ] ) ? ( $var[ $filter_key ]
                    == $filter_value )
                    : false;
            } );

        return $new;
    }
}

if ( ! function_exists( 'rsssl_get_non_www_domain' ) ) {
    function rsssl_get_non_www_domain() {

        $url = site_url();
        $url = str_replace('http://', '', $url);
        $url = str_replace('www', '', $url);

        return $url;
    }
}