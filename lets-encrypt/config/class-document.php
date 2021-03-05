<?php
defined( 'ABSPATH' ) or die( "you do not have acces to this page!" );

if ( ! class_exists( "rsssl_document" ) ) {
    class rsssl_document {
        private static $_this;

        function __construct() {
            if ( isset( self::$_this ) ) {
                wp_die( sprintf( '%s is a singleton class and you cannot create a second instance.',
                    get_class( $this ) ) );
            }

            self::$_this = $this;
            $this->init();

        }

        static function this() {
            return self::$_this;
        }

        /**
         * Get list of documents from the field list
         * @return array
         */

        public function get_document_types(){
            $fields = RSSSL()->rsssl_config->fields();
            $documents = array();
            foreach( $fields as $fieldname => $field ){
                if ( isset($field['type']) && $field['type'] === 'document') {
                    $documents[] = $fieldname;
                }
            }

            return $documents;
        }

        /**
         * Check if the page is public
         *
         * @param string $type
         * @param string $region
         *
         * @return bool
         */

        public function is_public_page( $type, $region ) {
            if ( ! isset( RSSSL()->rsssl_config->pages[ $region ][ $type ] ) ) {
                return false;
            }

            if ( isset( RSSSL()->rsssl_config->pages[ $region ][ $type ]['public'] )
                && RSSSL()->rsssl_config->pages[ $region ][ $type ]['public']
            ) {
                return true;
            }

            return false;
        }

        /**
         * Check if a page is required. If no condition is set, return true.
         * condition is "AND", all conditions need to be met.
         *
         * @param array|string $page
         * @param string       $region
         *
         * @return bool
         */

        public function page_required( $page, $region ) {
            if ( ! is_array( $page ) ) {
                if ( ! isset( RSSSL()->rsssl_config->pages[ $region ][ $page ] ) ) {
                    return false;
                }

                $page = RSSSL()->rsssl_config->pages[ $region ][ $page ];
            }

            //if it's not public, it's not required
            if ( isset( $page['public'] ) && $page['public'] == false ) {
                return false;
            }

            //if there's no condition, we set it as required
            if ( ! isset( $page['condition'] ) ) {
                return true;
            }

            if ( isset( $page['condition'] ) ) {
                $conditions    = $page['condition'];
                $condition_met = true;
                foreach (
                    $conditions as $condition_question => $condition_answer
                ) {
                    $value  = rsssl_get_value( $condition_question, false, false, $use_default = false );
                    $invert = false;
                    if ( ! is_array( $condition_answer )
                        && strpos( $condition_answer, 'NOT ' ) !== false
                    ) {
                        $condition_answer = str_replace( 'NOT ', '', $condition_answer );
                        $invert           = true;
                    }

                    $condition_answer = is_array( $condition_answer ) ? $condition_answer : array( $condition_answer );
                    foreach ( $condition_answer as $answer_item ) {
                        if ( is_array( $value ) ) {
                            if ( ! isset( $value[ $answer_item ] )
                                || ! $value[ $answer_item ]
                            ) {
                                $condition_met = false;
                            } else {
                                $condition_met = true;
                            }

                        } else {
                            $condition_met = ( $value == $answer_item );
                        }

                        //if one condition is met, we break with this condition, so it will return true.
                        if ( $condition_met ) {
                            break;
                        }

                    }

                    //if one condition is not met, we break with this condition, so it will return false.
                    if ( ! $condition_met ) {
                        break;
                    }

                }

                $condition_met = $invert ? ! $condition_met : $condition_met;
                return $condition_met;
            }

            return false;

        }

        /**
         * Check if an element should be inserted. AND implementation s
         *
         *
         * */

        public function insert_element( $element ) {

            if ( $this->callback_condition_applies( $element )
                && $this->condition_applies( $element )
            ) {
                return true;
            }

            return false;

        }

        /**
         * @param $element
         *
         * @return bool
         */

        public function callback_condition_applies( $element ) {

            if ( isset( $element['callback_condition'] ) ) {
                $conditions = is_array( $element['callback_condition'] )
                    ? $element['callback_condition']
                    : array( $element['callback_condition'] );
                foreach ( $conditions as $func ) {
                    $invert = false;
                    if ( strpos( $func, 'NOT ' ) !== false ) {
                        $invert = true;
                        $func   = str_replace( 'NOT ', '', $func );
                    }

                    if ( ! function_exists( $func ) ) {
                        break;
                    }

                    $show_field = $func();

                    if ( $invert ) {
                        $show_field = ! $show_field;
                    }
                    if ( ! $show_field ) {
                        return false;
                    }
                }
            }

            return true;
        }

        /**
         * Check if the passed condition applies
         *
         * @param array $element
         *
         * @return bool
         */

        public function condition_applies( $element ) {
            if ( isset( $element['condition'] ) ) {
                $fields        = RSSSL()->rsssl_config->fields;
                $condition_met = true;

                foreach (
                    $element['condition'] as $question => $condition_answer
                ) {
                    //reset every loop
                    $invert        = false;

                    if ( $condition_answer === 'loop' ) {
                        continue;
                    }
                    if ( ! isset( $fields[ $question ]['type'] ) ) {
                        return false;
                    }

                    $type  = $fields[ $question ]['type'];
                    $value = rsssl_get_value( $question );

                    if ( $condition_answer !== 'NOT EMPTY' && strpos( $condition_answer, 'NOT ' ) !== false ) {
                        $condition_answer = str_replace( 'NOT ', '', $condition_answer );
                        $invert           = true;
                    }

                    if ($condition_answer === 'NOT EMPTY') {
                        if ( strlen( $value )===0 ) {
                            $current_condition_met = false;
                        } else {
                            $current_condition_met = true;
                        }
                    } else if ( $type == 'multicheckbox' ) {
                        if ( ! isset( $value[ $condition_answer ] ) || ! $value[ $condition_answer ] )
                        {
                            $current_condition_met = false;
                        } else {
                            $current_condition_met = true;
                        }
                    } else {
                        $current_condition_met = $value == $condition_answer ;
                    }

                    $current_condition_met = $invert ? !$current_condition_met : $current_condition_met;

                    $condition_met = $condition_met && $current_condition_met;
                }

                return $condition_met;

            }

            return true;
        }


        /**
         * Check if this element should loop through dynamic multiple values
         *
         * @param array $element
         *
         * @return bool
         * */

        public function is_loop_element( $element ) {
            if ( isset( $element['condition'] ) ) {
                foreach (
                    $element['condition'] as $question => $condition_answer
                ) {
                    if ( $condition_answer === 'loop' ) {
                        return true;
                    }
                }
            }

            return false;
        }

        /**
         * Build a legal document by type
         * @return string
         */

        public function get_document_html() {
            $elements         = RSSSL()->rsssl_config->pages[ 'all' ][ 'lets-encrypt' ]["document_elements"];
            $html             = "";
            $paragraph        = 0;
            $sub_paragraph    = 0;
            $annex            = 0;
            $annex_arr        = array();
            $paragraph_id_arr = array();
            foreach ( $elements as $id => $element ) {
                //count paragraphs
                if ( $this->insert_element( $element )
                    || $this->is_loop_element( $element )
                ) {

                    if ( isset( $element['title'] )
                        && ( ! isset( $element['numbering'] )
                            || $element['numbering'] )
                    ) {
                        $sub_paragraph = 0;
                        $paragraph ++;
                        $paragraph_id_arr[ $id ]['main'] = $paragraph;
                    }

                    //count subparagraphs
                    if ( isset( $element['subtitle'] ) && $paragraph > 0
                        && ( ! isset( $element['numbering'] )
                            || $element['numbering'] )
                    ) {
                        $sub_paragraph ++;
                        $paragraph_id_arr[ $id ]['main'] = $paragraph;
                        $paragraph_id_arr[ $id ]['sub']  = $sub_paragraph;
                    }

                    //count annexes
                    if ( isset( $element['annex'] ) ) {
                        $annex ++;
                        $annex_arr[ $id ] = $annex;
                    }
                }
                if ( $this->is_loop_element( $element ) && $this->insert_element( $element )
                ) {
                    $fieldname    = key( $element['condition'] );
                    $values       = rsssl_get_value( $fieldname );
                    $loop_content = '';
                    if ( ! empty( $values ) ) {
                        foreach ( $values as $value ) {
                            if ( ! is_array( $value ) ) {
                                $value = array( $value );
                            }
                            $fieldnames = array_keys( $value );
                            if ( count( $fieldnames ) == 1 && $fieldnames[0] == 'key'
                            ) {
                                continue;
                            }

                            $loop_section = $element['content'];
                            foreach ( $fieldnames as $c_fieldname ) {
                                $field_value = ( isset( $value[ $c_fieldname ] ) ) ? $value[ $c_fieldname ] : '';
                                if ( ! empty( $field_value ) && is_array( $field_value )
                                ) {
                                    $field_value = implode( ', ', $field_value );
                                }

                                $loop_section = str_replace( '[' . $c_fieldname . ']', $field_value, $loop_section );
                            }

                            $loop_content .= $loop_section;

                        }
                        $html .= $this->wrap_header( $element, $paragraph, $sub_paragraph, $annex );
                        $html .= $this->wrap_content( $loop_content );
                    }
                } elseif ( $this->insert_element( $element ) ) {
                    $html .= $this->wrap_header( $element, $paragraph, $sub_paragraph, $annex );
                    if ( isset( $element['content'] ) ) {
                        $html .= $this->wrap_content( $element['content'], $element );
                    }
                }

                if ( isset( $element['callback'] ) && function_exists( $element['callback'] )
                ) {
                    $func = $element['callback'];
                    $html .= $func();
                }
            }

            $html = $this->replace_fields( $html, $paragraph_id_arr, $annex_arr );

            $comment = apply_filters( "rsssl_document_comment", "\n"
                . "<!-- This legal document was generated by rsssl Terms & conditions https://wordpress.org/plugins/really-simple-ssl -->"
                . "\n" );

            $html         = $comment . '<div id="rsssl-document" class="rsssl-document rsssl-wizard  ">' . $html . '</div>';
            $html         = wp_kses( $html, rsssl_allowed_html() );

            //in case we still have an unprocessed shortcode
            //this may happen when a shortcode is inserted in combination with gutenberg
            $html = do_shortcode($html);

            return apply_filters( 'rsssl_document_html', $html );
        }


        /**
         * Wrap the header for a paragraph
         *
         * @param array $element
         * @param int   $paragraph
         * @param int   $sub_paragraph
         * @param int   $annex
         *
         * @return string
         */

        public function wrap_header(
            $element, $paragraph, $sub_paragraph, $annex
        ) {
            $nr = "";
            if ( isset( $element['annex'] ) ) {
                $nr = __( "Annex", 'really-simple-ssl' ) . " " . $annex . ": ";
                if ( isset( $element['title'] ) ) {
                    return '<h2 class="annex">' . esc_html( $nr )
                        . esc_html( $element['title'] ) . '</h2>';
                }
                if ( isset( $element['subtitle'] ) ) {
                    return '<p class="subtitle annex">' . esc_html( $nr )
                        . esc_html( $element['subtitle'] ) . '</p>';
                }
            }

            if ( isset( $element['title'] ) ) {
                if ( empty( $element['title'] ) ) {
                    return "";
                }
                $nr = '';
                if ( $paragraph > 0
                    && $this->is_numbered_element( $element )
                ) {
                    $nr         = $paragraph;
                    $index_char = apply_filters( 'rsssl_index_char', '.' );
                    $nr         = $nr . $index_char . ' ';
                }

                return '<h2>' . esc_html( $nr )
                    . esc_html( $element['title'] ) . '</h2>';
            }

            if ( isset( $element['subtitle'] ) ) {
                if ( $paragraph > 0 && $sub_paragraph > 0
                    && $this->is_numbered_element( $element )
                ) {
                    $nr = $paragraph . "." . $sub_paragraph . " ";
                }

                return '<p class="rsssl-subtitle">' . esc_html( $nr )
                    . esc_html( $element['subtitle'] ) . '</p>';
            }
        }

        /**
         * Check if this element should be numbered
         * if no key is set, default is true
         *
         * @param array $element
         *
         * @return bool
         */

        public function is_numbered_element( $element ) {

            if ( ! isset( $element['numbering'] ) ) {
                return true;
            }

            return $element['numbering'];
        }

        /**
         * Wrap subheader in html
         *
         * @param string $header
         * @param int    $paragraph
         * @param int    $subparagraph
         *
         * @return string $html
         */

        public function wrap_sub_header( $header, $paragraph, $subparagraph ) {
            if ( empty( $header ) ) {
                return "";
            }

            return '<b>' . esc_html( $header ) . '</b><br>';
        }

        /**
         * Wrap content in html
         *
         * @param string $content
         * @param bool   $element
         *
         * @return string
         */
        public function wrap_content( $content, $element = false ) {
            if ( empty( $content ) ) {
                return "";
            }

            $class = isset( $element['class'] ) ? 'class="'
                . esc_attr( $element['class'] )
                . '"' : '';

            return "<p $class>" . $content . "</p>";
        }

        /**
         * Replace all fields in the resulting output
         *
         * @param string $html
         * @param array  $paragraph_id_arr
         * @param array  $annex_arr
         * @param int    $post_id
         * @param string $type
         * @param string $region
         *
         * @return string $html
         */

        private function replace_fields(
            $html, $paragraph_id_arr, $annex_arr
        ) {
            //replace references
            foreach ( $paragraph_id_arr as $id => $paragraph ) {
                $html = str_replace( "[article-$id]",
                    sprintf( __( '(See paragraph %s)', 'really-simple-ssl' ),
                        esc_html( $paragraph['main'] ) ), $html );
            }

            foreach ( $annex_arr as $id => $annex ) {
                $html = str_replace( "[annex-$id]",
                    sprintf( __( '(See annex %s)', 'really-simple-ssl' ),
                        esc_html( $annex ) ), $html );
            }

            $html = str_replace( "[domain]",
                '<a href="' . esc_url_raw( get_home_url() ) . '">'
                . esc_url_raw( get_home_url() ) . '</a>', $html );

            $html = str_replace( "[site_url]", site_url(), $html );

            $single_language = rsssl_get_value('language_communication');
            if ($single_language === 'yes'){
                $languages = RSSSL()->rsssl_config->format_code_lang(get_locale());
            } else {
                $languages = array_keys(rsssl_get_value('multilanguage_communication'));
                foreach( $languages as $key => $language ) {
                    $languages[$key] = RSSSL()->rsssl_config->format_code_lang($language);
                }
                $nr = count($languages);
                $languages = implode(', ', $languages);
                if ( $nr>1) {
                    $last_comma_pos = strrpos( $languages, ',' );
                    $languages      = substr( $languages, 0, $last_comma_pos ) . ' ' . __( "and", "really-simple-ssl" ) . ' ' . substr( $languages, $last_comma_pos + 1 );
                }
            }
            $html = str_replace( "[languages]", $languages, $html );

            $checked_date = date( get_option( 'date_format' ), get_option( 'rsssl_documents_update_date' ) );
            $checked_date = rsssl_localize_date( $checked_date );
            $html         = str_replace( "[checked_date]", esc_html( $checked_date ), $html );

            $uploads    = wp_upload_dir();
            $uploads_url = $uploads['baseurl'];
            $locale = substr( get_locale(), 0, 2 );
            $with_drawal_form_link = $uploads_url . "/rsssl/withdrawal-forms/Withdrawal-Form-$locale.pdf";
            $html         = str_replace( "[withdrawal_form_link]", $with_drawal_form_link, $html );

            //replace all fields.
            foreach ( RSSSL()->rsssl_config->fields() as $fieldname => $field ) {
                if ( strpos( $html, "[$fieldname]" ) !== false ) {
                    $html = str_replace( "[$fieldname]",
                        $this->get_plain_text_value( $fieldname , true ), $html );
                    //when there's a closing shortcode it's always a link
                    $html = str_replace( "[/$fieldname]", "</a>", $html );
                }

                if ( strpos( $html, "[comma_$fieldname]" ) !== false ) {
                    $html = str_replace( "[comma_$fieldname]",
                        $this->get_plain_text_value( $fieldname, false ), $html );
                }
            }

            return $html;

        }

        /**
         *
         * Get the plain text value of an option
         *
         * @param string $fieldname
         * @param bool   $list_style
         *
         * @return string
         */

        private function get_plain_text_value( $fieldname, $list_style ) {
            $value = rsssl_get_value( $fieldname );

            $front_end_label
                = isset( RSSSL()->rsssl_config->fields[ $fieldname ]['document_label'] )
                ? RSSSL()->rsssl_config->fields[ $fieldname ]['document_label']
                : false;

            if ( RSSSL()->rsssl_config->fields[ $fieldname ]['type'] == 'url' ) {
                $value = '<a href="' . $value . '">';
            } elseif ( RSSSL()->rsssl_config->fields[ $fieldname ]['type']
                == 'email'
            ) {
                $value = apply_filters( 'rsssl_document_email', $value );
            } elseif ( RSSSL()->rsssl_config->fields[ $fieldname ]['type']
                == 'radio'
            ) {
                $options = RSSSL()->rsssl_config->fields[ $fieldname ]['options'];
                $value   = isset( $options[ $value ] ) ? $options[ $value ]
                    : '';
            } elseif ( RSSSL()->rsssl_config->fields[ $fieldname ]['type']
                == 'textarea'
            ) {
                //preserve linebreaks
                $value = nl2br( $value );
            } elseif ( is_array( $value ) ) {
                $options = RSSSL()->rsssl_config->fields[ $fieldname ]['options'];
                //array('3' => 1 );
                $value = array_filter( $value, function ( $item ) {
                    return $item == 1;
                } );
                $value = array_keys( $value );
                //array (1, 4, 6)
                $labels = "";
                foreach ( $value as $index ) {
                    //trying to fix strange issue where index is not set
                    if ( ! isset( $options[ $index ] ) ) {
                        continue;
                    }

                    if ( $list_style ) {
                        $labels .= "<li>" . esc_html( $options[ $index ] )
                            . '</li>';
                    } else {
                        $labels .= $options[ $index ] . ', ';
                    }
                }
                //if (empty($labels)) $labels = __('None','really-simple-ssl');

                if ( $list_style ) {
                    $labels = "<ul>" . $labels . "</ul>";
                } else {
                    $labels = esc_html( rtrim( $labels, ', ' ) );
                    $labels = strrev( implode( strrev( ' ' . __( 'and',
                            'really-simple-ssl' ) ),
                        explode( strrev( ',' ), strrev( $labels ), 2 ) ) );
                }

                $value = $labels;
            } else {
                if ( isset( RSSSL()->rsssl_config->fields[ $fieldname ]['options'] ) ) {
                    $options
                        = RSSSL()->rsssl_config->fields[ $fieldname ]['options'];
                    if ( isset( $options[ $value ] ) ) {
                        $value = $options[ $value ];
                    }
                }
            }

            if ( $front_end_label && ! empty( $value ) ) {
                $value = $front_end_label . $value . "<br>";
            }

            return $value;
        }



        /**
         * Initialize hooks
         * */

        public function init() {
            //this shortcode is also available as gutenberg block
            add_shortcode( 'rsssl_wizard', array( $this, 'load_document' ) );
            add_filter( 'display_post_states', array( $this, 'add_post_state') , 10, 2);

            //clear shortcode transients after post update
            add_action( 'save_post', array( $this, 'clear_shortcode_transients' ), 10, 3 );
            add_action( 'rsssl_terms_conditions_add_pages_to_menu', array( $this, 'wizard_add_pages_to_menu' ), 10, 1 );
            add_action( 'rsssl_terms_conditions_add_pages', array( $this, 'callback_wizard_add_pages' ), 10, 1 );
            add_action( 'admin_init', array( $this, 'assign_documents_to_menu' ) );

            add_filter( 'rsssl_document_email', array( $this, 'obfuscate_email' ) );
            add_filter( 'body_class', array( $this, 'add_body_class_for_rsssl_documents' ) );

            //unlinking documents
            add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
            add_action( 'save_post', array( $this, 'save_metabox_data' ) , 10, 3);

            add_action( 'wp_ajax_rsssl_create_pages', array( $this, 'ajax_create_pages' ) );
            add_action( 'admin_init', array( $this, 'maybe_generate_withdrawal_form') );
            add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
        }

        /**
         * Add document post state
         * @param array $post_states
         * @param WP_Post $post
         * @return array
         */
        public function add_post_state($post_states, $post) {
            if ( $this->is_rsssl_page( $post->ID ) ) {
                $post_states['page_for_privacy_policy'] = __("Legal Document", 'really-simple-ssl');
            }
            return $post_states;
        }

        public function add_meta_box( $post_type ) {
            global $post;

            if ( ! $post ) {
                return;
            }

            if ( $this->is_rsssl_page( $post->ID )
                && ! rsssl_uses_gutenberg()
            ) {
                add_meta_box( 'rsssl_edit_meta_box',
                    __( 'Document status', 'really-simple-ssl' ),
                    array( $this, 'metabox_unlink_from_rsssl' ), null,
                    'side', 'high', array() );
            }
        }

        /**
         * Unlink a page from the shortcode, and use the html instead
         *
         */
        function metabox_unlink_from_rsssl() {
            if ( ! current_user_can( 'manage_options' ) ) {
                return;
            }
            wp_nonce_field( 'rsssl_unlink_nonce', 'rsssl_unlink_nonce' );

            global $post;
            $sync = $this->syncStatus( $post->ID );
            ?>
            <select name="rsssl_document_status">
                <option value="sync" <?php echo $sync === 'sync'
                    ? 'selected="selected"'
                    : '' ?>><?php _e( "Synchronize document with rsssl",
                        'really-simple-ssl' ); ?></option>
                <option value="unlink" <?php echo $sync === 'unlink'
                    ? 'selected="selected"'
                    : '' ?>><?php _e( "Edit document and stop synchronization",
                        'really-simple-ssl' ); ?></option>
            </select>
            <?php

        }

        /**
         * Get sync status of post
         *
         * @param $post_id
         *
         * @return string
         */

        public function syncStatus( $post_id ) {
            $post = get_post( $post_id );
            $sync = 'unlink';

            if ( ! $post ) {
                return $sync;
            }

            $shortcode = 'rsssl_wizard';
            $block     = 'rsssltc/lets-encrypt';

            $html = $post->post_content;
            if ( rsssl_uses_gutenberg() && has_block( $block, $html ) ) {
                $elements = parse_blocks( $html );
                foreach ( $elements as $element ) {
                    if ( $element['blockName'] === $block ) {
                        if ( isset( $element['attrs']['documentSyncStatus'] )
                            && $element['attrs']['documentSyncStatus']
                            === 'unlink'
                        ) {
                            $sync = 'unlink';
                        } else {
                            $sync = 'sync';
                        }
                    }
                }
            } elseif ( has_shortcode( $post->post_content, $shortcode ) ) {
                $sync = get_post_meta( $post_id, 'rsssl_document_status',
                    true );
                if ( ! $sync ) {
                    $sync = 'sync';
                }
            }

            //default
            return $sync;
        }

        /**
         * Generate a pdf withdrawal form for each language
         * @throws \Mpdf\MpdfException
         */
        public function maybe_generate_withdrawal_form(){
            $languages_to_generate = get_option('rsssl_generate_pdf_languages');
            if (!empty( $languages_to_generate )) {
                $languages = $languages_to_generate;
                reset($languages);
                $language_to_generate = key($languages);
                unset( $languages_to_generate[$language_to_generate] );
                update_option('rsssl_generate_pdf_languages', $languages_to_generate );
                $this->generate_pdf( $language_to_generate );
            }
        }

        /**
         * Function to generate a pdf file, either saving to file, or echo to browser
         *
         * @param string $locale
         *
         * @throws \Mpdf\MpdfException
         */

        public function generate_pdf( $locale = 'en_US') {
            if ( ! is_user_logged_in() ) {
                die( "invalid command" );
            }

            if ( ! current_user_can( 'manage_options' ) ) {
                die( "invalid command" );
            }
            switch_to_locale( $locale );
            $error      = false;
            $temp_dir = false;
            $uploads    = wp_upload_dir();
            $upload_dir = $uploads['basedir'];
            $title = __("Withdrawal Form", "really-simple-ssl");

            $document_html = rsssl_get_template("withdrawal-form.php");
            $document_html = str_replace( '[address_company]', rsssl_get_value('address_company'), $document_html);
            $html = '
                    <style>
 
                    </style>

                    <body >
                    ' . $document_html . '
                    </body>';

            //==============================================================
            //==============================================================
            //==============================================================

            require rsssl_path . '/assets/vendor/autoload.php';

            //generate a token when it's not there, otherwise use the existing one.
            if ( get_option( 'rsssl_pdf_dir_token' ) ) {
                $token = get_option( 'rsssl_pdf_dir_token' );
            } else {
                $token = time();
                update_option( 'rsssl_pdf_dir_token', $token );
            }

            if ( ! is_writable( $upload_dir ) ) {
                $error = true;
            }

            if ( ! $error ) {
                if ( ! file_exists( $upload_dir . '/rsssl' ) ) {
                    mkdir( $upload_dir . '/rsssl' );
                }
                if ( ! file_exists( $upload_dir . '/rsssl/tmp' ) ) {
                    mkdir( $upload_dir . '/rsssl/tmp' );
                }
                if ( ! file_exists( $upload_dir . '/rsssl/withdrawal-forms' ) ) {
                    mkdir( $upload_dir . '/rsssl/withdrawal-forms' );
                }
                $save_dir = $upload_dir . '/rsssl/withdrawal-forms/';
                $temp_dir = $upload_dir . '/rsssl/tmp/' . $token;
                if ( ! file_exists( $temp_dir ) ) {
                    mkdir( $temp_dir );
                }
            }

            if ( ! $error && $temp_dir) {
                $mpdf = new Mpdf\Mpdf( array(
                    'setAutoTopMargin'  => 'stretch',
                    'autoMarginPadding' => 5,
                    'tempDir'           => $temp_dir,
                    'margin_left'       => 20,
                    'margin_right'      => 20,
                    'margin_top'        => 30,
                    'margin_bottom'     => 30,
                    'margin_header'     => 30,
                    'margin_footer'     => 10,
                ) );

                $mpdf->SetDisplayMode( 'fullpage' );
                $mpdf->SetTitle( $title );

                $date = date_i18n( get_option( 'date_format' ), time() );

                $footer_text = sprintf( "%s $title $date", get_bloginfo( 'name' ) );

                $mpdf->SetFooter( $footer_text );
                $mpdf->WriteHTML( $html );

                // Save the pages to a file
                $file_title = $save_dir . sanitize_file_name( "Withdrawal-Form-". $locale );
                $output_mode = 'F';
                $mpdf->Output( $file_title . ".pdf", $output_mode );
            }
        }

        /**
         * If rsssl GDPR is also installed, enqueue the document CSS.
         * For this reason we use rsssl functions here.
         */

        public function enqueue_assets() {
            if ( defined('rsssl_version') && $this->is_rsssl_page() ) {
                $min      = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? ''
                    : '.min';
                $load_css = rsssl_get_value( 'use_document_css' );
                if ( $load_css ) {
                    wp_register_style( 'rsssl-document',
                        rsssl_url . "assets/css/document$min.css", false,
                        rsssl_version );
                    wp_enqueue_style( 'rsssl-document' );
                }

                add_action( 'wp_head', array( rsssl::$document, 'inline_styles' ), 100 );
            }

        }

        /**
         * Save data posted from the metabox
         */
        public function save_metabox_data($post_id, $post, $update ) {
            if ( ! current_user_can( 'manage_options' ) ) {
                return;
            }
            // check if this isn't an auto save
            if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
                return;
            }

            // security check
            if ( ! isset( $_POST['rsssl_unlink_nonce'] )
                || ! wp_verify_nonce( $_POST['rsssl_unlink_nonce'],
                    'rsssl_unlink_nonce' )
            ) {
                return;
            }

            if ( ! isset( $_POST['rsssl_document_status'] ) ) {
                return;
            }

            global $post;

            if ( ! $post ) {
                return;
            }
            //prevent looping
            remove_action( 'save_post', array( $this, 'save_metabox_data' ) , 10, 3 );
            $sync = sanitize_text_field( $_POST['rsssl_document_status'] ) == 'unlink' ? 'unlink' : 'sync';
            //save the document's shortcode in a meta field
            if ( $sync === 'unlink' ) {
                //get shortcode from page
                $shortcode = false;

                if ( preg_match( $this->get_shortcode_pattern( "gutenberg" ),
                    $post->post_content, $matches )
                ) {
                    $shortcode = $matches[0];
                } elseif ( preg_match( $this->get_shortcode_pattern( "classic" ),
                    $post->post_content, $matches )
                ) {
                    $shortcode = $matches[0];
                } elseif ( preg_match( $this->get_shortcode_pattern( "classic"), $post->post_content, $matches )
                ) {
                    $shortcode = $matches[0];
                }

                if ( $shortcode ) {
                    //store shortcode
                    update_post_meta( $post->ID, 'rsssl_shortcode',
                        $post->post_content );
                    $document_html
                        = RSSSL()->rsssl_document->get_document_html();
                    $args = array(
                        'post_content' => $document_html,
                        'ID'           => $post->ID,
                    );
                    wp_update_post( $args );
                }
            } else {
                $shortcode = get_post_meta( $post->ID, 'rsssl_shortcode', true );
                if ( $shortcode ) {
                    $args = array(
                        'post_content' => $shortcode,
                        'ID'           => $post->ID,
                    );
                    wp_update_post( $args );
                }
                delete_post_meta( $post->ID, 'rsssl_shortcode' );
            }
            update_post_meta( $post->ID, 'rsssl_document_status', $sync );
            add_action( 'save_post', array( $this, 'save_metabox_data' ), 10, 3 );
        }

        /**
         * add a class to the body telling the page it's a rsssl doc. We use this for the soft cookie wall
         *
         * @param $classes
         *
         * @return array
         */
        public function add_body_class_for_rsssl_documents( $classes ) {
            global $post;
            if ( $post && $this->is_rsssl_page( $post->ID ) ) {
                $classes[] = 'rsssl-wizard ';
            }

            return $classes;
        }

        /**
         * obfuscate the email address
         *
         * @param $email
         *
         * @return string
         */

        public function obfuscate_email( $email ) {
            $alwaysEncode = array( '.', ':', '@' );

            $result = '';

            // Encode string using oct and hex character codes
            for ( $i = 0; $i < strlen( $email ); $i ++ ) {
                // Encode 25% of characters including several that always should be encoded
                if ( in_array( $email[ $i ], $alwaysEncode )
                    || mt_rand( 1, 100 ) < 25
                ) {
                    if ( mt_rand( 0, 1 ) ) {
                        $result .= '&#' . ord( $email[ $i ] ) . ';';
                    } else {
                        $result .= '&#x' . dechex( ord( $email[ $i ] ) ) . ';';
                    }
                } else {
                    $result .= $email[ $i ];
                }
            }

            //make clickable

            $result = '<a href="mailto:'.$result.'">'.$result.'</a>';

            return $result;
        }


        /**
         * Create legal document pages from the wizard using ajax
         */

        public function ajax_create_pages(){

            if ( ! current_user_can( 'manage_options' ) ) {
                return;
            }
            $error   = false;
            if (!isset($_POST['pages'])){
                $error = true;
            }

            if (!$error){
                $posted_pages = json_decode(stripslashes($_POST['pages']));
                foreach ($posted_pages as $region => $pages ){
                    foreach($pages as $type => $title) {
                        $title = sanitize_text_field($title);
                        $current_page_id = $this->get_shortcode_page_id(false);
                        if (!$current_page_id){
                            $this->create_page();
                        } else {
                            //if the page already exists, just update it with the title
                            $page = array(
                                'ID'           => $current_page_id,
                                'post_title'   => $title,
                                'post_type'    => "page",
                            );
                            wp_update_post( $page );
                        }
                    }
                }
            }
            $data     = array(
                'success' => !$error,
                'new_button_text' => __("Update pages",'really-simple-ssl'),
                'icon' => rsssl_icon('check', 'success'),
            );
            $response = json_encode( $data );
            header( "Content-Type: application/json" );
            echo $response;
            exit;

        }

        /**
         * Check if the site has missing pages for the auto generated documents
         * @return bool
         */

        public function has_missing_pages(){
            $pages = RSSSL()->rsssl_document->get_required_pages();
            $missing_pages = false;
            foreach ( $pages as $region => $region_pages ) {
                foreach ( $region_pages as $type => $page ) {
                    $current_page_id = $this->get_shortcode_page_id();
                    if ( ! $current_page_id ) {
                        $missing_pages = true;
                        break;
                    }
                }
            }

            return $missing_pages;
        }

        public function callback_wizard_add_pages()
        { ?>
            <div class="rsssl-wizard-intro">
                <?php if ($this->has_missing_pages()){
                    echo '<p>'.__("The pages marked with X should be added to your website. You can create these pages with a shortcode, a Gutenberg block, or use the below \"Create missing pages\" button.",'really-simple-ssl').'</p>';
                } else {
                    echo '<p>'.__("All necessary pages have been created already. You can update the page titles here if you want, then click the \"Update pages\" button.",'really-simple-ssl').'</p>';
                } ?>
            </div>

            <?php $pages = RSSSL()->rsssl_document->get_required_pages();
            $missing_pages = false;
            ?>
            <div class="field-group add-pages">
                <div class="rsssl-field">
                    <div class="rsssl-add-pages-table">
                        <?php foreach ( $pages as $region => $region_pages ) {
                            foreach ( $region_pages as $type => $page ) {
                                $current_page_id   = $this->get_shortcode_page_id(false);
                                if ( ! $current_page_id ) {
                                    $missing_pages = true;
                                    $title         = $page['title'];
                                    $icon          = rsssl_icon('check', 'failed');
                                    $class         = 'rsssl-deleted-page';
                                } else {
                                    $post          = get_post( $current_page_id );
                                    $icon          = rsssl_icon('check', 'success');
                                    $title         = $post->post_title;
                                    $class         = 'rsssl-valid-page';
                                }
                                $shortcode = $this->get_shortcode( $force_classic = true );
                                ?>
                                <div>
                                    <input
                                        name="<?php echo $type ?>"
                                        data-region="<?php echo $region ?>"
                                        class="<?php echo $class ?> rsssl-create-page-title"
                                        type="text"
                                        value="<?php echo $title ?>">
                                    <?php echo $icon ?>
                                </div>
                                <span><?php echo rsssl_icon('documents-shortcode', 'success_notooltip'); ?></span>
                                <span class="rsssl-selectable"><?php echo $shortcode; ?></span>
                                <?php
                            }
                        } ?>
                    </div>

                    <?php if ($missing_pages){
                        $btn = __("Create missing pages",'really-simple-ssl');
                    } else {
                        $btn = __("Update pages",'really-simple-ssl');
                    } ?>

                    <button type="button" class="button button-primary" id="rsssl-tcf-create_pages"><?php echo $btn ?></button>

                </div>
            </div>
            <?php

        }

        /**
         *
         * Show form to enable user to add pages to a menu
         *
         * @hooked field callback wizard_add_pages_to_menu
         * @since  1.0
         *
         */

        public function wizard_add_pages_to_menu() {
            //this function is used as of 4.9.0
            if ( ! function_exists( 'wp_get_nav_menu_name' ) ) {
                echo '<div class="field-group rsssl-link-to-menu">';
                echo '<div class="rsssl-field"></div>';
                rsssl_notice( __( 'Your WordPress version does not support the functions needed for this step. You can upgrade to the latest WordPress version, or add the pages manually to a menu.',
                    'really-simple-ssl' ), 'warning' );
                echo '</div>';
                return;
            }

            //get list of menus
            $menus = wp_list_pluck( wp_get_nav_menus(), 'name', 'term_id' );

            $link = '<a href="' . admin_url( 'nav-menus.php' ) . '">';
            if ( empty( $menus ) ) {
                rsssl_notice( sprintf( __( "No menus were found. Skip this step, or %screate a menu%s first." ), $link, '</a>' ) );
                return;
            }

            $created_pages = $this->get_created_pages();
            $required_pages = $this->get_required_pages();
            if (count($required_pages) > count($created_pages) ){
                rsssl_notice( __( 'You haven\'t created all required pages yet. You can add missing pages in the previous step, or create them manually with the shortcode. You can come back later to this step to add your pages to the desired menu, or do it manually via Appearance > Menu.', 'really-simple-ssl' )
                );
            }

            echo '<div class="rsssl-field">';
            echo '<div class="rsssl-link-to-menu-table">';
            $pages = $this->get_created_pages( 'all' );
            if ( count( $pages ) > 0 ) {
                foreach ( $pages as $page_id ) {
                    echo '<span>' . get_the_title( $page_id ) . '</span>';
                    ?>

                    <select name="rsssl_assigned_menu[<?php echo $page_id ?>]">
                        <option value=""><?php _e( "Select a menu", 'really-simple-ssl' ); ?></option>
                        <?php foreach ( $menus as $menu_id => $menu ) {
                            $selected = $this->is_assigned_this_menu($page_id, $menu_id) ? "selected" : "";
                            echo "<option {$selected} value='{$menu_id}'>{$menu}</option>";
                        } ?>
                    </select>

                    <?php
                }
            }

            echo '</div>';
            echo '</div>';


        }

        /**
         * Handle the submit of a form which assigns documents to a menu
         *
         * @hooked admin_init
         *
         */

        public function assign_documents_to_menu() {
            if ( ! current_user_can( 'manage_options' ) ) {
                return;
            }

            if ( isset( $_POST['rsssl_assigned_menu'] ) ) {
                foreach ( $_POST['rsssl_assigned_menu'] as $page_id => $menu_id ) {
                    if ( empty( $menu_id ) ) {
                        continue;
                    }
                    if ( $this->is_assigned_this_menu( $page_id, $menu_id ) ) {
                        continue;
                    }

                    $page = get_post( $page_id );

                    wp_update_nav_menu_item( $menu_id, 0, array(
                        'menu-item-title'     => get_the_title( $page ),
                        'menu-item-object-id' => $page->ID,
                        'menu-item-object'    => get_post_type( $page ),
                        'menu-item-status'    => 'publish',
                        'menu-item-type'      => 'post_type',
                    ) );
                }
            }
        }


        /**
         * Get all pages that are not assigned to any menu
         *
         * @return array|bool
         * @since 1.2
         *
         * */

        public function pages_not_in_menu() {
            //search in menus for the current post
            $menus         = wp_list_pluck( wp_get_nav_menus(), 'name',
                'term_id' );
            $pages         = $this->get_created_pages();
            $pages_in_menu = array();

            foreach ( $menus as $menu_id => $title ) {

                $menu_items = wp_get_nav_menu_items( $menu_id );
                foreach ( $menu_items as $post ) {
                    if ( in_array( $post->object_id, $pages ) ) {
                        $pages_in_menu[] = $post->object_id;
                    }
                }

            }
            $pages_not_in_menu = array_diff( $pages, $pages_in_menu );
            if ( count( $pages_not_in_menu ) == 0 ) {
                return false;
            }

            return $pages_not_in_menu;
        }


        /**
         *
         * Check if a page is assigned to a menu
         *
         * @param int $page_id
         * @param int $menu_id
         *
         * @return bool
         *
         * @since 1.2
         */

        public function is_assigned_this_menu( $page_id, $menu_id ) {
            $menu_items = wp_list_pluck( wp_get_nav_menu_items( $menu_id ),
                'object_id' );

            return ( in_array( $page_id, $menu_items ) );

        }

        /**
         * Create a page of certain type in wordpress
         *
         * @return int|bool page_id
         * @since 1.0
         */

        public function create_page(  ) {
            if ( ! current_user_can( 'manage_options' ) ) {
                return false;
            }
            $pages = RSSSL()->rsssl_config->pages;

            //only insert if there is no shortcode page of this type yet.
            $page_id = $this->get_shortcode_page_id(false);
            if ( ! $page_id ) {

                $page = $pages[ 'all' ][ 'lets-encrypt' ];
                $page = array(
                    'post_title'   => $page['title'],
                    'post_type'    => "page",
                    'post_content' => $this->get_shortcode( ),
                    'post_status'  => 'publish',
                );

                // Insert the post into the database
                $page_id = wp_insert_post( $page );
            }

            do_action( 'rsssl_create_page', $page_id );

            return $page_id;

        }

        /**
         * Delete a page of a type
         *
         * @param string $type
         * @param string $region
         *
         */

        public function delete_page( $type, $region ) {
            if ( ! current_user_can( 'manage_options' ) ) {
                return;
            }

            $page_id = $this->get_shortcode_page_id( );
            if ( $page_id ) {
                wp_delete_post( $page_id, false );
            }
        }


        /**
         *
         * Check if page of certain type exists
         *
         * @return bool
         *
         */

        public function page_exists() {
            if ( $this->get_shortcode_page_id() ) {
                return true;
            }

            return false;
        }

        /**
         * get the shortcode or block for a page type
         *
         * @param bool   $force_classic
         *
         * @return string $shortcode
         *
         */

        public function get_shortcode( $force_classic = false
        ) {
            //even if on gutenberg, with elementor we have to use classic shortcodes.
            if ( ! $force_classic && rsssl_uses_gutenberg()
                && ! $this->uses_elementor()
            ) {
                $page = RSSSL()->rsssl_config->pages[ 'all' ][ 'lets-encrypt' ];
                return '<!-- wp:rsssltc/lets-encrypt {"title":"' . $page['title'] . '"} /-->';
            } else {
                return '[rsssl-wizard ]';
            }
        }

        /**
         * Get shortcode pattern for this site, gutenberg or classic
         *
         * @param string $type
         * @param bool   $legacy
         *
         * @return string
         */
        public function get_shortcode_pattern( $type = "classic") {

            if ( $type === 'classic' ) {
                return '/\[rsssl\-terms\-conditions\]/i';
            } else {
                return '/<!-- wp:rsssltc\/lets-encrypt {.*?} \/-->/i';
            }
        }

        /**
         * Check if this site uses Elementor
         * When Elementor is used, the classic shortcode should be used, even when on Gutenberg
         *
         * @return bool $uses_elementor
         */

        public function uses_elementor() {
            if ( defined( 'ELEMENTOR_VERSION' ) ) {
                return true;
            }

            return false;
        }


        /**
         *
         * Get type of document
         *
         * @param int $post_id
         *
         * @return array
         *
         *
         */

        public function get_document_data( $post_id ) {

            $pattern = $this->get_shortcode_pattern('classic' );
            $pattern_legacy = $this->get_shortcode_pattern('classic' , true );
            $pattern_gutenberg = $this->get_shortcode_pattern('gutenberg' );
            $post    = get_post( $post_id );

            $content = $post->post_content;
            $output = array(
                'type' => '',
                'region' => false,
            );
            if ( preg_match_all( $pattern, $content, $matches, PREG_PATTERN_ORDER ) ) {
                if ( isset( $matches[1][0] ) ) {
                    $output['type'] = $matches[1][0];
                }
                if ( isset( $matches[2][0] ) ) {
                    $output['region'] = $matches[2][0];
                }
            } else if ( preg_match_all( $pattern_gutenberg, $content, $matches, PREG_PATTERN_ORDER ) ) {
                if ( isset( $matches[1][0] ) ) {
                    $output['type'] = $matches[1][0];
                }
                if ( isset( $matches[2][0] ) ) {
                    $output['region'] = $matches[2][0];
                }
            } else if ( preg_match_all( $pattern_legacy, $content, $matches, PREG_PATTERN_ORDER ) ) {
                if ( isset( $matches[1][0] ) ) {
                    $output['type'] = $matches[1][0];
                }
                if ( isset( $matches[2][0] ) ) {
                    $output['region'] = $matches[2][0];
                }
            }
            return $output;
        }

        /**
         * Get list of all created pages with page id for current setup
         *
         * @return array $pages
         *
         *
         */

        public function get_created_pages() {
            $pages          = array();
            $page_id = $this->get_shortcode_page_id( false);
            if ($page_id) $pages[] = $page_id;
            return $pages;
        }


        /**
         * Get list of all required pages for current setup
         *
         * @return array $pages
         *
         *
         */

        public function get_required_pages() {
            $regions  = rsssl_get_regions();
            $required = array();

            foreach ( $regions as $region => $label ) {
                if ( ! isset( RSSSL()->rsssl_config->pages[ $region ] ) ) {
                    continue;
                }

                $pages = RSSSL()->rsssl_config->pages[ $region ];

                foreach ( $pages as $type => $page ) {
                    if ( ! $page['public'] ) {
                        continue;
                    }
                    if ( $this->page_required( $page, $region ) ) {
                        $required[ $region ][ $type ] = $page;
                    }
                }
            }


            return $required;
        }

        /**
         * loads document content on shortcode call
         *
         * @param array  $atts
         * @param null   $content
         * @param string $tag
         *
         * @return string $html
         *
         *
         */

        public function load_document(
            $atts = array(), $content = null, $tag = ''
        ) {
            ob_start();
            $html         = $this->get_document_html( );
            $allowed_html = rsssl_allowed_html();
            echo wp_kses( $html, $allowed_html );

            return ob_get_clean();
        }

        /**
         * checks if the current page contains the shortcode.
         *
         * @param int|bool $post_id
         *
         * @return boolean
         * @since 1.0
         */

        public function is_rsssl_page( $post_id = false ) {
            $post_meta = get_post_meta( $post_id, 'rsssl_shortcode', false );
            if ( $post_meta ) {
                return true;
            }

            $shortcode = 'rsssl_wizard';
            $block     = 'rsssltc/lets-encrypt';

            if ( $post_id ) {
                $post = get_post( $post_id );
            } else {
                global $post;
            }

            if ( $post ) {
                if ( rsssl_uses_gutenberg() && has_block( $block, $post ) ) {
                    return true;
                }
                if ( has_shortcode( $post->post_content, $shortcode ) ) {
                    return true;
                }
            }
            return false;
        }

        /**
         * gets the  page that contains the shortcode or the gutenberg block
         *
         * @param bool $cache
         *
         * @return int $page_id
         * @since 1.0
         */

        public function get_shortcode_page_id( $cache = true) {
            $cache = false;
            $page_id   = $cache ? get_transient( 'rsssl_shortcode' ) : false;
            if ( ! $page_id ) {
                $pages = get_pages();
                /**
                 * Gutenberg block check
                 *
                 * */
                foreach ( $pages as $page ) {
                    $post_meta = get_post_meta( $page->ID, 'rsssl_shortcode', true );
                    if ( $post_meta ) {
                        $html = $post_meta;
                    } else {
                        $html = $page->post_content;
                    }

                    if ( preg_match( $this->get_shortcode_pattern( "gutenberg" ), $html, $matches ) ) {
                        set_transient( "rsssl_shortcode", $page->ID, HOUR_IN_SECONDS );
                        return $page->ID;
                    } elseif ( preg_match( $this->get_shortcode_pattern( "classic" ), $html, $matches ) ) {
                        set_transient( "rsssl_shortcode", $page->ID, HOUR_IN_SECONDS );
                        return $page->ID;
                    }
                }

            } else {
                return $page_id;
            }


            return false;
        }


        /**
         * clear shortcode transients after page update
         *
         * @param int|bool    $post_id
         * @param object|bool $post
         * @param bool $update
         *
         * @hooked save_post which is why the $post param is passed without being used.
         *
         * @return void
         */


        public function clear_shortcode_transients(
            $post_id = false, $post = false, $update
        ) {
            delete_transient( "rsssl_shortcode" );
        }

        /**
         *
         * get the URl of a specific page type
         *
         * @param string $type cookie-policy, privacy-statement, etc
         * @param string $region
         * @return string
         *
         *
         */

        public function get_page_url( $type, $region ) {

            if ( rsssl_get_value( $type ) === 'none' ) {
                return '#';
            } else if ( rsssl_get_value( $type ) === 'custom' ) {
                $id = get_option( "rsssl_" . $type . "_custom_page" );
                //get correct translated id
                $id = apply_filters( 'wpml_object_id', $id,
                    'page', true, substr( get_locale(), 0, 2 ) );
                return intval( $id ) == 0
                    ? '#'
                    : esc_url_raw( get_permalink( $id ) );
            } else if ( rsssl_get_value( $type ) === 'url' ) {
                $url = get_option("rsssl_".$type."_custom_page_url");
                return esc_url_raw( $url );
            } else {
                $policy_page_id = $this->get_shortcode_page_id( );

                //get correct translated id
                $policy_page_id = apply_filters( 'wpml_object_id', $policy_page_id,
                    'page', true, substr( get_locale(), 0, 2 ) );

                return get_permalink( $policy_page_id );
            }
        }


        /**
         *
         * get the title of a specific page type. Only in use for generated docs from rsssl.
         *
         * @param string $type cookie-policy, privacy-statement, etc
         * @param string $region
         *
         * @return string $title
         */

        public function get_document_title( $type, $region ) {

            if ( rsssl_get_value( $type ) === 'custom' || rsssl_get_value( $type ) === 'generated' ) {
                if ( rsssl_get_value( $type ) === 'custom' ) {
                    $policy_page_id = get_option( "rsssl_" . $type . "_custom_page" );
                } else if ( rsssl_get_value( $type ) === 'generated' ) {
                    $policy_page_id = $this->get_shortcode_page_id( );
                }

                //get correct translated id
                $policy_page_id = apply_filters( 'wpml_object_id',
                    $policy_page_id,
                    'page', true, substr( get_locale(), 0, 2 ) );

                $post = get_post( $policy_page_id );
                if ( $post ) {
                    return $post->post_title;
                }
            }

            return str_replace('-', ' ', $type);
        }

    }


} //class closure