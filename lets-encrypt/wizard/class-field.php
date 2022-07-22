<?php defined( 'ABSPATH' ) or die();

if ( ! class_exists( "rsssl_field" ) ) {
    class rsssl_field {
        private static $_this;
        public $position;
        public $fields;
        public $default_args;
        public $form_errors = array();

        function __construct() {
            if ( isset( self::$_this ) ) {
                wp_die( sprintf( '%s is a singleton class and you cannot create a second instance.',
                    get_class( $this ) ) );
            }

            self::$_this = $this;
            //safe before the fields are loaded in config, in init
            add_action( 'plugins_loaded', array( $this, 'process_save' ), 14 );
            add_action( 'rsssl_before_label', array( $this, 'before_label' ), 10, 1 );
            add_action( 'rsssl_label_html', array( $this, 'label_html' ), 10, 1 );
            add_action( 'rsssl_after_label', array( $this, 'after_label' ), 10, 1 );
            add_action( 'rsssl_after_field', array( $this, 'after_field' ), 10, 1 );

            $this->load();
        }

        static function this() {
            return self::$_this;
        }

        public function label_html( $args ) {
            ?>
            <label class="<?php if ( $args['disabled'] ) {echo 'rsssl-disabled';} ?>" for="<?php echo $args['fieldname'] ?>">
                <div class="rsssl-label-wrap"><?php echo $args['label'] ?>
                    <?php
                    if ( isset($args['tooltip']) && $args['tooltip-position'] === 'title') {
                        echo rsssl_icon('help', 'normal', $args['tooltip']);
                    }
                    ?>
                </div>
                <div class="rsssl-subtitle-wrap"><?php echo $args['sublabel'] ?></div>
            </label>
            <?php
        }

        public function load() {
            $this->default_args = array(
                "fieldname"          => '',
                "type"               => 'text',
                "required"           => false,
                'default'            => '',
                'label'              => '',
                'sublabel'           => '',
                'option_text'        => false,
                'table'              => false,
                'callback_condition' => false,
                'condition'          => false,
                'callback'           => false,
                'placeholder'        => '',
                'optional'           => false,
                'disabled'           => false,
                'hidden'             => false,
                'region'             => false,
                'media'              => true,
                'first'              => false,
                'warn'               => false,
                'cols'               => false,
                'minimum'            => 0,
                'title'              => '',
                'tooltip-position' => '',
            );


        }

        public function process_save() {
            if ( ! rsssl_user_can_manage() ) {
                return;
            }

            if ( isset( $_POST['rsssl_le_nonce'] ) ) {
                if ( ! isset( $_POST['rsssl_le_nonce'] ) || ! wp_verify_nonce( $_POST['rsssl_le_nonce'], 'rsssl_save' ) ) {
                    return;
                }

                //save data
                $posted_fields = array_filter( $_POST, array( $this, 'filter_rsssl_fields' ), ARRAY_FILTER_USE_KEY );
                foreach ( $posted_fields as $fieldname => $fieldvalue ) {
                    $this->save_field( $fieldname, $fieldvalue );
                }
                do_action('rsssl_after_saved_all_fields', $posted_fields );
            }
        }


        /**
         * santize an array for save storage
         *
         * @param $array
         *
         * @return mixed
         */

        public function sanitize_array( $array ) {
            foreach ( $array as &$value ) {
                if ( ! is_array( $value ) ) {
                    $value = sanitize_text_field( $value );
                } //if ($value === 'on') $value = true;
                else {
                    $this->sanitize_array( $value );
                }
            }

            return $array;

        }



        /**
         * Check if this is a conditional field
         *
         * @param $fieldname
         *
         * @return bool
         */

        public function is_conditional( $fieldname ) {
            $fields = RSSSL_LE()->config->fields();
            if ( isset( $fields[ $fieldname ]['condition'] )
                && $fields[ $fieldname ]['condition']
            ) {
                return true;
            }

            return false;
        }

        /**
         * Check if this is a multiple field
         *
         * @param $fieldname
         *
         * @return bool
         */

        public function is_multiple_field( $fieldname ) {
            $fields = RSSSL_LE()->config->fields();
            if ( isset( $fields[ $fieldname ]['type'] )
                && ( $fields[ $fieldname ]['type'] == 'thirdparties' )
            ) {
                return true;
            }
            if ( isset( $fields[ $fieldname ]['type'] )
                && ( $fields[ $fieldname ]['type'] == 'processors' )
            ) {
                return true;
            }

            return false;
        }


        public function save_multiple( $fieldnames ) {
            if ( ! rsssl_user_can_manage() ) {
                return;
            }

            $fields = RSSSL_LE()->config->fields();
            foreach ( $fieldnames as $fieldname => $saved_fields ) {

                if ( ! isset( $fields[ $fieldname ] ) ) {
                    return;
                }

                $page           = $fields[ $fieldname ]['source'];
                $options        = get_option( 'rsssl_options_' . $page );
                $multiple_field = $this->get_value( $fieldname, array() );


                foreach ( $saved_fields as $key => $value ) {
                    $value = is_array( $value )
                        ? array_map( 'sanitize_text_field', $value )
                        : sanitize_text_field( $value );
                    //store the fact that this value was saved from the back-end, so should not get overwritten.
                    $value['saved_by_user'] = true;
                    $multiple_field[ $key ] = $value;
                }

                $options[ $fieldname ] = $multiple_field;
                if ( ! empty( $options ) ) {
                    update_option( 'rsssl_options_' . $page, $options );
                }
            }
        }

        /**
         * Save the field
         * @param string $fieldname
         * @param mixed $fieldvalue
         */

        public function save_field( $fieldname, $fieldvalue ) {
            if ( ! rsssl_user_can_manage() ) {
                return;
            }

            $fieldvalue = apply_filters("rsssl_fieldvalue", $fieldvalue, $fieldname);
            $fields    = RSSSL_LE()->config->fields();
            $fieldname = str_replace( "rsssl_", '', $fieldname );

            //do not save callback fields
            if ( isset( $fields[ $fieldname ]['callback'] ) ) {
                return;
            }

            $type     = $fields[ $fieldname ]['type'];
            $page     = $fields[ $fieldname ]['source'];
            $required = isset( $fields[ $fieldname ]['required'] ) ? $fields[ $fieldname ]['required'] : false;
            $fieldvalue = $this->sanitize( $fieldvalue, $type );
            if ( ! $this->is_conditional( $fieldname ) && $required
                && empty( $fieldvalue )
            ) {
                $this->form_errors[] = $fieldname;
            }

            if ($type === 'password' ) {
                $fieldvalue = RSSSL_LE()->letsencrypt_handler->encode($fieldvalue);
            }

            $options = get_option( 'rsssl_options_' . $page );
            if ( ! is_array( $options ) ) {
                $options = array();
            }
            $prev_value = isset( $options[ $fieldname ] ) ? $options[ $fieldname ] : false;
            do_action( "rsssl_before_save_" . $page . "_option", $fieldname, $fieldvalue, $prev_value, $type );
            $options[ $fieldname ] = $fieldvalue;

            if ( ! empty( $options ) ) {
                update_option( 'rsssl_options_' . $page, $options );
            }

            do_action( "rsssl_after_save_" . $page . "_option", $fieldname, $fieldvalue, $prev_value, $type );
        }


        public function add_multiple_field( $fieldname, $cookie_type = false ) {
            if ( ! rsssl_user_can_manage() ) {
                return;
            }

            $fields = RSSSL_LE()->config->fields();

            $page    = $fields[ $fieldname ]['source'];
            $options = get_option( 'rsssl_options_' . $page );

            $multiple_field = $this->get_value( $fieldname, array() );
            if ( $fieldname === 'used_cookies' && ! $cookie_type ) {
                $cookie_type = 'custom_' . time();
            }
            if ( ! is_array( $multiple_field ) ) {
                $multiple_field = array( $multiple_field );
            }

            if ( $cookie_type ) {
                //prevent key from being added twice
                foreach ( $multiple_field as $index => $cookie ) {
                    if ( $cookie['key'] === $cookie_type ) {
                        return;
                    }
                }

                //don't add field if it was deleted previously
                $deleted_cookies = get_option( 'rsssl_deleted_cookies' );
                if ( ( $deleted_cookies
                    && in_array( $cookie_type, $deleted_cookies ) )
                ) {
                    return;
                }

                //don't add default wordpress cookies
                if ( strpos( $cookie_type, 'wordpress_' ) !== false ) {
                    return;
                }

                $multiple_field[] = array( 'key' => $cookie_type );
            } else {
                $multiple_field[] = array();
            }

            $options[ $fieldname ] = $multiple_field;

            if ( ! empty( $options ) ) {
                update_option( 'rsssl_options_' . $page, $options );
            }
        }

        /**
         * Sanitize a field
         * @param $value
         * @param $type
         *
         * @return array|bool|int|string|void
         */
        public function sanitize( $value, $type ) {
            if ( ! rsssl_user_can_manage() ) {
                return false;
            }

            switch ( $type ) {
                case 'colorpicker':
                    return sanitize_hex_color( $value );
                case 'text':
                    return sanitize_text_field( $value );
                case 'multicheckbox':
                    if ( ! is_array( $value ) ) {
                        $value = array( $value );
                    }

                    return array_map( 'sanitize_text_field', $value );
                case 'phone':
                    $value = sanitize_text_field( $value );

                    return $value;
                case 'email':
                    return sanitize_email( $value );
                case 'url':
                    return esc_url_raw( $value );
                case 'number':
                    return intval( $value );
                case 'css':
                case 'javascript':
                    return  $value ;
                case 'editor':
                case 'textarea':
	            case 'password':
                    return wp_kses_post( $value );
            }

            return sanitize_text_field( $value );
        }

        /**/

        private
        function filter_rsssl_fields(
            $fieldname
        ) {
            if ( strpos( $fieldname, 'rsssl_' ) !== false
                && isset( RSSSL_LE()->config->fields[ str_replace( 'rsssl_',
                        '', $fieldname ) ] )
            ) {
                return true;
            }

            return false;
        }

        public function before_label( $args )
        {
            $condition_class    = '';
            $condition_question = '';
            $condition_answer   = '';

            if ( ! empty( $args['condition'] ) ) {
                $condition_count    = 1;
                foreach ( $args['condition'] as $question => $answer ) {
                    $question = esc_attr( $question );
                    $answer = esc_attr( $answer );
                    $condition_class     .= "condition-check-{$condition_count} ";
                    $condition_question  .= "data-condition-answer-{$condition_count}='{$answer}' ";
                    $condition_answer    .= "data-condition-question-{$condition_count}='{$question}' ";
                    $condition_count++;
                }
            }

            $hidden_class    = ( $args['hidden'] ) ? 'hidden' : '';
            $rsssl_hidden    = $this->condition_applies( $args ) ? '' : 'rsssl-hidden';
            $first_class     = ( $args['first'] ) ? 'first' : '';
            $type            = $args['type'] === 'notice' ? '' : $args['type'];

            $cols_class      = isset($args['cols']) && $args['cols']  ? "rsssl-cols-{$args['cols']}" : '';
            $col_class       = isset($args['col'])                    ? "rsssl-col-{$args['col']}" : '';
            $colspan_class   = isset($args['colspan'])                ? "rsssl-colspan-{$args['colspan']}" : '';

            $this->get_master_label( $args );

            echo '<div class="field-group ' .
                esc_attr( $args['fieldname'] . ' ' .
                    esc_attr( $cols_class ) . ' ' .
                    esc_attr( $col_class ) . ' ' .
                    esc_attr( $colspan_class ) . ' ' .
                    'rsssl-'. $type . ' ' .
                    $hidden_class . ' ' .
                    $first_class . ' ' .
                    $condition_class . ' ' .
                    $rsssl_hidden )
                . '" ';

            echo $condition_question;
            echo $condition_answer;
            // Close div!
            echo '>';

            // Required to give a checkbox a title
            if ($args['title']) {
                $title = $args['title'];
                echo "<div class='rsssl-title-wrap rsssl-field'>$title</div>";
            }

            echo '<div class="rsssl-field">';

            if ($args['label']) {
                echo '<div class="rsssl-label">';
            }
        }

        public function get_master_label( $args ) {
            if ( ! isset( $args['master_label'] ) ) {
                return;
            }
            ?>
            <div class="rsssl-master-label"><h2><?php echo esc_html( $args['master_label'] ) ?></h2></div>
            <?php

        }

        /**
         * Show tooltip, if provided
         * @param $args
         */
        public function in_label($args) {
            if ( isset($args['tooltip']) ) {
                echo rsssl_icon('help', 'normal', $args['tooltip']);
            }
        }

        public
        function after_label(
            $args
        ) {
            if ($args['label'] ) {
                echo '</div>';
            }
        }

        public function after_field( $args ) {

            $this->get_comment( $args );
            echo '</div><!--close in after field-->';
            echo '<div class="rsssl-help-warning-wrap">';
            if ( isset( $args['help'] ) ) {
                rsssl_sidebar_notice( wp_kses_post( $args['help'] ) );
            }
            do_action( 'rsssl_notice_' . $args['fieldname'], $args );

            echo '</div>';
            echo '</div>';
        }


        public function text( $args )
        {
            if ( ! $this->show_field( $args ) ) {
                return;
            }

            $fieldname = 'rsssl_' . $args['fieldname'];
            $value = $this->get_value( $args['fieldname'], $args['default'] );
            $required = $args['required'] ? 'required' : '';
            $is_required = $args['required'] ? 'is-required' : '';
            $check_icon = rsssl_icon('check', 'success');
            ?>

            <?php do_action( 'rsssl_before_label', $args ); ?>
            <?php do_action( 'rsssl_label_html' , $args );?>
            <?php do_action( 'rsssl_after_label', $args ); ?>

            <input <?php echo $required ?>
                class="validation <?php echo $is_required ?>"
                placeholder="<?php echo esc_html( $args['placeholder'] ) ?>"
                type="text"
                value="<?php echo esc_html( $value ) ?>"
                name="<?php echo esc_html( $fieldname ) ?>"
                <?php if ( $args['disabled'] ) {
                    echo 'disabled';
                } ?>
            >
            <?php echo $check_icon ?>
            <?php do_action( 'rsssl_after_field', $args ); ?>

            <?php
        }

	    public function password( $args )
	    {
		    if ( ! $this->show_field( $args ) ) {
			    return;
		    }

		    $fieldname = 'rsssl_' . $args['fieldname'];
		    $value = $this->get_value( $args['fieldname'], $args['default'] );
		    $required = $args['required'] ? 'required' : '';
		    $is_required = $args['required'] ? 'is-required' : '';
		    $check_icon = rsssl_icon('check', 'success');
		    ?>

		    <?php do_action( 'rsssl_before_label', $args ); ?>
		    <?php do_action( 'rsssl_label_html' , $args );?>
		    <?php do_action( 'rsssl_after_label', $args ); ?>

            <input <?php echo $required ?>
                    class="validation <?php echo $is_required ?>"
                    placeholder="<?php echo esc_html( $args['placeholder'] ) ?>"
                    type="password"
                    value="<?php echo esc_html( $value ) ?>"
                    name="<?php echo esc_html( $fieldname ) ?>"
			    <?php if ( $args['disabled'] ) {
				    echo 'disabled';
			    } ?>
            >
		    <?php echo $check_icon ?>

		    <?php do_action( 'rsssl_after_field', $args ); ?>

		    <?php
	    }

        public function url( $args )
        {
            if ( ! $this->show_field( $args ) ) {
                return;
            }

            $fieldname = 'rsssl_' . $args['fieldname'];
            $value     = $this->get_value( $args['fieldname'], $args['default'] );
            $required = $args['required'] ? 'required' : '';
            $is_required = $args['required'] ? 'is-required' : '';
            $check_icon = rsssl_icon('check', 'success');
            $times_icon = rsssl_icon('check', 'failed');

            ?>

            <?php do_action( 'rsssl_before_label', $args ); ?>
            <?php do_action( 'rsssl_label_html' , $args );?>
            <?php do_action( 'rsssl_after_label', $args ); ?>

            <input <?php echo $required ?>
                class="validation <?php echo $is_required ?>"
                placeholder="<?php echo esc_html( $args['placeholder'] ) ?>"
                type="text"
                pattern="(http(s)?(:\/\/))?(www.)?[#a-zA-Z0-9-_\.\/:].*"
                value="<?php echo esc_html( $value ) ?>"
                name="<?php echo esc_html( $fieldname ) ?>"
            >
            <?php echo $check_icon ?>
            <?php echo $times_icon ?>

            <?php do_action( 'rsssl_after_field', $args ); ?>

            <?php
        }

        public function email( $args )
        {
            if ( ! $this->show_field( $args ) ) {
                return;
            }

            $fieldname = 'rsssl_' . $args['fieldname'];
            $value     = $this->get_value( $args['fieldname'], $args['default'] );
            $required = $args['required'] ? 'required' : '';
            $is_required = $args['required'] ? 'is-required' : '';
            $check_icon = rsssl_icon('check', 'success');
            $times_icon = rsssl_icon('check', 'failed');
            ?>

            <?php do_action( 'rsssl_before_label', $args ); ?>
            <?php do_action( 'rsssl_label_html' , $args );?>
            <?php do_action( 'rsssl_after_label', $args ); ?>

            <input <?php echo $required ?>
                class="validation <?php echo $is_required ?>"
                placeholder="<?php echo esc_html( $args['placeholder'] ) ?>"
                type="email"
                value="<?php echo esc_html( $value ) ?>"
                name="<?php echo esc_html( $fieldname ) ?>"
            >
            <?php echo $check_icon ?>
            <?php echo $times_icon ?>

            <?php do_action( 'rsssl_after_field', $args ); ?>

            <?php
        }

        public function phone( $args )
        {
            if ( ! $this->show_field( $args ) ) {
                return;
            }

            $fieldname = 'rsssl_' . $args['fieldname'];
            $value     = $this->get_value( $args['fieldname'], $args['default'] );
            $required = $args['required'] ? 'required' : '';
            $is_required = $args['required'] ? 'is-required' : '';
            $check_icon = rsssl_icon('check', 'success');
            $times_icon = rsssl_icon('check', 'failed');

            ?>

            <?php do_action( 'rsssl_before_label', $args ); ?>
            <?php do_action( 'rsssl_label_html' , $args );?>
            <?php do_action( 'rsssl_after_label', $args ); ?>

            <input autocomplete="tel" <?php echo $required ?>
                   class="validation <?php echo $is_required ?>"
                   placeholder="<?php echo esc_html( $args['placeholder'] ) ?>"
                   type="text"
                   value="<?php echo esc_html( $value ) ?>"
                   name="<?php echo esc_html( $fieldname ) ?>"
            >
            <?php echo $check_icon ?>
            <?php echo $times_icon ?>

            <?php do_action( 'rsssl_after_field', $args ); ?>

            <?php
        }

        public
        function number(
            $args
        ) {
            $fieldname = 'rsssl_' . $args['fieldname'];
            $value     = $this->get_value( $args['fieldname'],
                $args['default'] );
            if ( ! $this->show_field( $args ) ) {
                return;
            }
            ?>

            <?php do_action( 'rsssl_before_label', $args ); ?>
            <?php do_action( 'rsssl_label_html' , $args );?>
            <?php do_action( 'rsssl_after_label', $args ); ?>
            <input <?php if ( $args['required'] ) {
                echo 'required';
            } ?>
                class="validation <?php if ( $args['required'] ) {
                    echo 'is-required';
                } ?>"
                placeholder="<?php echo esc_html( $args['placeholder'] ) ?>"
                type="number"
                value="<?php echo esc_html( $value ) ?>"
                name="<?php echo esc_html( $fieldname ) ?>"
                min="<?php echo $args['minimum']?>" step="<?php echo isset($args["validation_step"]) ? intval($args["validation_step"]) : 1?>"
            >
            <?php do_action( 'rsssl_after_field', $args ); ?>
            <?php
        }


        public
        function checkbox(
            $args, $force_value = false
        ) {
            $fieldname = 'rsssl_' . $args['fieldname'];

            $value             = $force_value ? $force_value
                : $this->get_value( $args['fieldname'], $args['default'] );
            $placeholder_value = ( $args['disabled'] && $value ) ? $value : 0;
            if ( ! $this->show_field( $args ) ) {
                return;
            }
            ?>
            <?php do_action( 'rsssl_before_label', $args ); ?>
            <?php do_action( 'rsssl_label_html' , $args );?>
            <?php do_action( 'rsssl_after_label', $args );

            ?>
            <label class="rsssl-switch">
                <input name="<?php echo esc_html( $fieldname ) ?>" type="hidden"
                       value="<?php echo $placeholder_value ?>"/>

                <input name="<?php echo esc_html( $fieldname ) ?>" size="40"
                       type="checkbox"
                    <?php if ( $args['disabled'] ) {
                        echo 'disabled';
                    } ?>
                       <?php if ( $args['required'] ) {
	                       echo 'required';
                       } ?>
                       class="<?php if ( $args['required'] ) {
                           echo 'is-required';
                       } ?>"
                       value="1" <?php checked( 1, $value, true ) ?> />
                <span class="rsssl-slider rsssl-round"></span>
            </label>
            <?php if ($args['option_text'] ) {
                ?> <div class="rsssl-wizard-settings-text"><?php echo $args['option_text'] ?></div> <?php

                if (isset($args['tooltip']) && $args['tooltip-position'] === 'after') {
                    echo rsssl_icon('help', 'normal', $args['tooltip']);
                }
            }

            do_action( 'rsssl_after_field', $args ); ?>
            <?php
        }



        public function radio( $args )
        {
            if ( ! $this->show_field( $args ) ) {
                return;
            }

            $fieldname = 'rsssl_' . $args['fieldname'];
            $value     = $this->get_value( $args['fieldname'], $args['default'] );
            $options   = $args['options'];
            $required = $args['required'] ? 'required' : '';
            $check_icon = rsssl_icon('bullet', 'success');
            $disabled_index = array();
            $default_index = array();

            if ( ! empty( $options ) ) {
                // Disabled index
                foreach ($options as $option_value => $option_label) {
                    if ( is_array($args['disabled']) && in_array($option_value, $args['disabled']) || $args['disabled'] === true ) {
                        $disabled_index[$option_value] = 'rsssl-disabled';
                    } else {
                        $disabled_index[$option_value] = '';
                    }
                }
                // Default index
                foreach ($options as $option_value => $option_label) {
                    if ( is_array($args['default']) && in_array($option_value, $args['default']) ) {
                        $default_index[$option_value] = 'rsssl-default';
                    } else {
                        $default_index[$option_value] = '';
                    }
                }
            }

            ?>

            <?php do_action( 'rsssl_before_label', $args ); ?>
            <?php do_action( 'rsssl_label_html' , $args );?>
            <?php do_action( 'rsssl_after_label', $args ); ?>

            <?php
            if ( ! empty( $options ) ) {
                foreach ( $options as $option_value => $option_label )
                {
                    if ($disabled_index[$option_value] === 'rsssl-disabled') {
                        echo '<div class="rsssl-not-allowed">';
                    } ?>
                    <label class="rsssl-radio-container <?php echo $disabled_index[$option_value] ?>"><?php echo esc_html( $option_label ) ?>
                        <input
                            <?php echo $required ?>
                            type="radio"
                            id="<?php echo esc_html( $option_value ) ?>"
                            name="<?php echo esc_html( $fieldname ) ?>"
                            class="<?php echo esc_html( $fieldname ) ?>"
                            value="<?php echo esc_html( $option_value ) ?>"
                            <?php if ( $value == $option_value ) echo "checked" ?>
                        >
                        <div class="radiobtn <?php echo $default_index[$option_value] ?>"
                            <?php echo $required ?>
                        ><?php echo $check_icon ?></div>
                    </label>
                    <?php if ($disabled_index[$option_value] === 'rsssl-disabled') {
                    echo '</div>'; // class="rsssl-not-allowed"
                }
                }
            }
            ?>

            <?php do_action( 'rsssl_after_field', $args ); ?>
            <?php
        }


        public function show_field( $args ) {
            $show = ( $this->condition_applies( $args, 'callback_condition' ) );

            return $show;
        }


        public function function_callback_applies( $func ) {
            $invert = false;

            if ( strpos( $func, 'NOT ' ) !== false ) {
                $invert = true;
                $func   = str_replace( 'NOT ', '', $func );
            }
            $show_field = $func();
            if ( $invert ) {
                $show_field = ! $show_field;
            }
            if ( $show_field ) {
                return true;
            } else {
                return false;
            }
        }

	    /**
         * If multiple condition, uses AND relation
         *
	     * @param       $args
	     * @param false $type
	     *
	     * @return bool
	     */
        public function condition_applies( $args, $type = false)
        {
            $default_args = $this->default_args;
            $args         = wp_parse_args( $args, $default_args );

            if ( ! $type ) {
                if ( $args['condition'] ) {
                    $type = 'condition';
                } elseif ( $args['callback_condition'] ) {
                    $type = 'callback_condition';
                }
            }

            if ( ! $type || ! $args[ $type ] ) {
                return true;
            }

            //function callbacks
            $maybe_is_function = is_string($args[ $type ]) ? str_replace( 'NOT ', '', $args[ $type ] ) : '';
            if ( ! is_array( $args[ $type ] ) && ! empty( $args[ $type ] ) && function_exists( $maybe_is_function ) ) {
                return $this->function_callback_applies( $args[ $type ] );
            }

            $condition = $args[ $type ];

            //if we're checking the condition, but there's also a callback condition, check that one as well.
            //but only if it's an array. Otherwise it's a func.
            if ( $type === 'condition' && isset( $args['callback_condition'] ) && is_array( $args['callback_condition'] ) ) {
                $condition += $args['callback_condition'];
            }

            foreach ( $condition as $c_fieldname => $c_value_content ) {
                $c_values = $c_value_content;
                //the possible multiple values are separated with comma instead of an array, so we can add NOT.
                if ( ! is_array( $c_value_content ) && strpos( $c_value_content, ',' ) !== false ) {
                    $c_values = explode( ',', $c_value_content );
                }
                $c_values = is_array( $c_values ) ? $c_values : array( $c_values );

                foreach ( $c_values as $c_value ) {
                    $maybe_is_function = str_replace( 'NOT ', '', $c_value );
                    if ( function_exists( $maybe_is_function ) ) {
                        $match = $this->function_callback_applies( $c_value );
                        if ( ! $match ) {
                            return false;
                        }
                    } else {
                        $actual_value = rsssl_get_value( $c_fieldname );

                        if ( strpos( $c_value, 'NOT ' ) === false ) {
                            $invert = false;
                        } else {
                            $invert  = true;
                            $c_value = str_replace( "NOT ", "", $c_value );
                        }

                        //when the actual value is an array, it is enough when just one matches.
                        //to be able to return false, for no match at all, we check all items, then return false if none matched
                        //this way we can preserve the AND property of this function
                        $match = ( $c_value === $actual_value || in_array( $actual_value, $c_values ) );

                        if ( $invert ) {
                            $match = ! $match;
                        }
                        if ( ! $match ) {
                            return false;
                        }
                    }

                }
            }

            return true;
        }

        public function get_field_type( $fieldname ) {
            if ( ! isset( RSSSL_LE()->config->fields[ $fieldname ] ) ) {
                return false;
            }

            return RSSSL_LE()->config->fields[ $fieldname ]['type'];
        }

        public
        function textarea(
            $args
        ) {
            $fieldname = 'rsssl_' . $args['fieldname'];
            $check_icon = rsssl_icon('check', 'success');
            $times_icon = rsssl_icon('check', 'failed');
            $value = $this->get_value( $args['fieldname'], $args['default'] );
            if ( ! $this->show_field( $args ) ) {
                return;
            }
            ?>
            <?php do_action( 'rsssl_before_label', $args ); ?>
            <?php do_action( 'rsssl_label_html' , $args );?>
            <?php do_action( 'rsssl_after_label', $args ); ?>
            <textarea name="<?php echo esc_html( $fieldname ) ?>"
                      <?php if ( $args['required'] ) {
                          echo 'required';
                      } ?>
                        class="validation <?php if ( $args['required'] ) {
                            echo 'is-required';
                        } ?>"
                      placeholder="<?php echo esc_html( $args['placeholder'] ) ?>"><?php echo esc_html( $value ) ?></textarea>

            <?php echo $check_icon ?>
            <?php echo $times_icon ?>
            <?php do_action( 'rsssl_after_field', $args ); ?>
            <?php
        }

        /*
         * Show field with editor
         *
         *
         * */

        public function editor( $args, $step = '' ) {
            $fieldname     = 'rsssl_' . $args['fieldname'];
            $args['first'] = true;
            $media         = $args['media'] ? true : false;

            $value = $this->get_value( $args['fieldname'], $args['default'] );

            if ( ! $this->show_field( $args ) ) {
                return;
            }

            ?>

            <?php do_action( 'rsssl_before_label', $args ); ?>
            <?php do_action( 'rsssl_label_html' , $args );?>
            <?php do_action( 'rsssl_after_label', $args ); ?>

            <?php
            $settings = array(
                'media_buttons' => $media,
                'editor_height' => 300,
                // In pixels, takes precedence and has no default value
                'textarea_rows' => 15,
            );
            wp_editor( $value, $fieldname, $settings ); ?>
            <?php do_action( 'rsssl_after_field', $args ); ?>
            <?php
        }

        public
        function javascript(
            $args
        ) {
            $fieldname = 'rsssl_' . $args['fieldname'];
            $value     = $this->get_value( $args['fieldname'],
                $args['default'] );
            if ( ! $this->show_field( $args ) ) {
                return;
            }
            ?>

            <?php do_action( 'rsssl_before_label', $args ); ?>
            <?php do_action( 'rsssl_label_html' , $args );?>
            <?php do_action( 'rsssl_after_label', $args ); ?>
            <div id="<?php echo esc_html( $fieldname ) ?>editor"
                 style="height: 200px; width: 100%"><?php echo $value ?></div>
            <?php do_action( 'rsssl_after_field', $args ); ?>
            <script>
                var <?php echo esc_html( $fieldname )?> =
                ace.edit("<?php echo esc_html( $fieldname )?>editor");
                <?php echo esc_html( $fieldname )?>.setTheme("ace/theme/monokai");
                <?php echo esc_html( $fieldname )?>.session.setMode("ace/mode/javascript");
                jQuery(document).ready(function ($) {
                    var textarea = $('textarea[name="<?php echo esc_html( $fieldname )?>"]');
                    <?php echo esc_html( $fieldname )?>.
                    getSession().on("change", function () {
                        textarea.val(<?php echo esc_html( $fieldname )?>.getSession().getValue()
                    )
                    });
                });
            </script>
            <textarea style="display:none"
                      name="<?php echo esc_html( $fieldname ) ?>"><?php echo $value ?></textarea>
            <?php
        }

        public
        function css(
            $args
        ) {
            $fieldname = 'rsssl_' . $args['fieldname'];

            $value = $this->get_value( $args['fieldname'], $args['default'] );
            if ( ! $this->show_field( $args ) ) {
                return;
            }
            ?>

            <?php do_action( 'rsssl_before_label', $args ); ?>
            <?php do_action( 'rsssl_label_html' , $args );?>
            <?php do_action( 'rsssl_after_label', $args ); ?>
            <div id="<?php echo esc_html( $fieldname ) ?>editor"
                 style="height: 290px; width: 100%"><?php echo $value ?></div>
            <?php do_action( 'rsssl_after_field', $args ); ?>
            <script>
                var <?php echo esc_html( $fieldname )?> =
                ace.edit("<?php echo esc_html( $fieldname )?>editor");
                <?php echo esc_html( $fieldname )?>.setTheme("ace/theme/monokai");
                <?php echo esc_html( $fieldname )?>.session.setMode("ace/mode/css");
                jQuery(document).ready(function ($) {
                    var textarea = $('textarea[name="<?php echo esc_html( $fieldname )?>"]');
                    <?php echo esc_html( $fieldname )?>.
                    getSession().on("change", function () {
                        textarea.val(<?php echo esc_html( $fieldname )?>.getSession().getValue()
                    )
                    });
                });
            </script>
            <textarea style="display:none"
                      name="<?php echo esc_html( $fieldname ) ?>"><?php echo $value ?></textarea>
            <?php
        }

        /**
         * Check if a step has any fields
         * @param string $page
         * @param bool $step
         * @param bool $section
         *
         * @return bool
         */
        public function step_has_fields( $page, $step = false, $section = false ) {
            $fields = RSSSL_LE()->config->fields( $page, $step, $section );
            foreach ( $fields as $fieldname => $args ) {
                $default_args = $this->default_args;
                $args = wp_parse_args( $args, $default_args );
                $args['fieldname'] = $fieldname;

                if ( $this->show_field( $args ) ) {
                    return true;
                }
            }

            return false;
        }

        public
        function get_fields(
            $source, $step = false, $section = false, $get_by_fieldname = false
        ) {

            $fields = RSSSL_LE()->config->fields( $source, $step, $section,
                $get_by_fieldname );


            $i = 0;
            foreach ( $fields as $fieldname => $args ) {
                if ( $i === 0 ) {
                    $args['first'] = true;
                }
                $i ++;
                $default_args = $this->default_args;
                $args         = wp_parse_args( $args, $default_args );


                $type              = ( $args['callback'] ) ? 'callback'
                    : $args['type'];
                $args['fieldname'] = $fieldname;
                switch ( $type ) {
                    case 'callback':
                        $this->callback( $args );
                        break;
                    case 'text':
                        $this->text( $args );
                        break;
	                case 'password':
		                $this->password( $args );
		                break;
                    case 'button':
                        $this->button( $args );
                        break;
                    case 'upload':
                        $this->upload( $args );
                        break;
                    case 'url':
                        $this->url( $args );
                        break;
                    case 'select':
                        $this->select( $args );
                        break;
                    case 'checkbox':
                        $this->checkbox( $args );
                        break;
                    case 'textarea':
                        $this->textarea( $args );
                        break;
                    case 'radio':
                        $this->radio( $args );
                        break;
                    case 'javascript':
                        $this->javascript( $args );
                        break;
                    case 'css':
                        $this->css( $args );
                        break;
                    case 'email':
                        $this->email( $args );
                        break;
                    case 'phone':
                        $this->phone( $args );
                        break;
                    case 'number':
                        $this->number( $args );
                        break;
                    case 'notice':
                        $this->notice( $args );
                        break;
                    case 'editor':
                        $this->editor( $args, $step );
                        break;
                    case 'label':
                        $this->label( $args );
                        break;
                }
            }

        }

        public
        function callback(
            $args
        ) {
            $callback = $args['callback'];
            do_action( 'rsssl_before_label', $args );
            do_action( 'rsssl_label_html' , $args );
            do_action( 'rsssl_after_label', $args );

	        $file = trailingslashit(rsssl_le_wizard_path) . 'templates/' . $callback;
            if ( file_exists($file) ) {
	            echo RSSSL()->really_simple_ssl->get_template($callback, $path = rsssl_le_wizard_path);
            } else {
	            do_action( "rsssl_$callback", $args );
            }
            do_action( 'rsssl_after_field', $args );
        }

        public
        function notice(
            $args
        ) {
            if ( ! $this->show_field( $args ) ) {
                return;
            }
            do_action( 'rsssl_before_label', $args );
            rsssl_notice( $args['label'], 'warning' );
            do_action( 'rsssl_after_label', $args );
            do_action( 'rsssl_after_field', $args );
        }

        public
        function select(
            $args
        ) {

            $fieldname = 'rsssl_' . $args['fieldname'];

            $value = $this->get_value( $args['fieldname'], $args['default'] );
            if ( ! $this->show_field( $args ) ) {
                return;
            }

            ?>
            <?php do_action( 'rsssl_before_label', $args ); ?>
            <?php do_action( 'rsssl_label_html' , $args );?>
            <?php do_action( 'rsssl_after_label', $args ); ?>
            <select class="rsssl-select2" <?php if ( $args['required'] ) {
                echo 'required';
            } ?> name="<?php echo esc_html( $fieldname ) ?>">
                <?php foreach (
                    $args['options'] as $option_key => $option_label
                ) { ?>
                    <option
                        value="<?php echo esc_html( $option_key ) ?>" <?php echo ( $option_key
                        == $value )
                        ? "selected"
                        : "" ?>><?php echo esc_html( $option_label ) ?></option>
                <?php } ?>
            </select>

            <?php do_action( 'rsssl_after_field', $args ); ?>
            <?php
        }

        public
        function label(
            $args
        ) {

            $fieldname = 'rsssl_' . $args['fieldname'];
            if ( ! $this->show_field( $args ) ) {
                return;
            }

            ?>
            <?php do_action( 'rsssl_before_label', $args ); ?>
            <?php do_action( 'rsssl_label_html' , $args );?>
            <?php do_action( 'rsssl_after_label', $args ); ?>

            <?php do_action( 'rsssl_after_field', $args ); ?>
            <?php
        }

        /**
         *
         * Button/Action field
         *
         * @param $args
         *
         * @echo string $html
         */

        public
        function button(
            $args
        ) {
            $fieldname = 'rsssl_' . $args['fieldname'];
            if ( ! $this->show_field( $args ) ) {
                return;
            }

            ?>
            <?php do_action( 'rsssl_before_label', $args ); ?>
            <?php do_action( 'rsssl_label_html' , $args );?>
            <?php do_action( 'rsssl_after_label', $args ); ?>
            <?php if ( $args['post_get'] === 'get' ) { ?>
                <a <?php if ( $args['disabled'] )
                    echo "disabled" ?>href="<?php echo $args['disabled']
                    ? "#"
                    : rsssl_letsencrypt_wizard_url().'&action=' . $args['action'] ?>"
                   class="button"><?php echo esc_html( $args['label'] ) ?></a>
            <?php } else { ?>
                <input <?php if ( $args['warn'] )
                    echo 'onclick="return confirm(\'' . $args['warn']
                        . '\');"' ?> <?php if ( $args['disabled'] )
                    echo "disabled" ?> class="button" type="submit"
                                       name="<?php echo $args['action'] ?>"
                                       value="<?php echo esc_html( $args['label'] ) ?>">
            <?php } ?>

            <?php do_action( 'rsssl_after_field', $args ); ?>
            <?php
        }

        /**
         * Upload field
         *
         * @param $args
         *
         * @echo string $html
         */

        public
        function upload(
            $args
        ) {
            if ( ! $this->show_field( $args ) ) {
                return;
            }

            ?>
            <?php do_action( 'rsssl_before_label', $args ); ?>
            <?php do_action( 'rsssl_label_html' , $args );?>
            <?php do_action( 'rsssl_after_label', $args ); ?>

            <input type="file" type="submit" name="rsssl-upload-file"
                   value="<?php echo esc_html( $args['label'] ) ?>">
            <input <?php if ( $args['disabled'] )
                echo "disabled" ?> class="button" type="submit"
                                   name="<?php echo $args['action'] ?>"
                                   value="<?php _e( 'Start',
                                       'really-simple-ssl' ) ?>">
            <?php do_action( 'rsssl_after_field', $args ); ?>
            <?php
        }


        public function save_button() {
            $button_text = __( "Save", 'really-simple-ssl' );
	        $button_name = 'rsssl-save';

	        $step = RSSSL_LE()->wizard->calculate_next('step');
            $section = RSSSL_LE()->wizard->calculate_next('section');
	        $fields = RSSSL_LE()->config->fields( 'lets-encrypt', $step, $section);
	        reset($fields);
	        foreach ($fields as $key => $field ) {
		        if (isset($field['callback']) && strpos($field['callback'], '.php')!==false) {
			        $button_text = __( "Refresh", 'really-simple-ssl' );
			        $button_name = 'rsssl-refresh';
		        }
	        }
	        return '<input class="button button-secondary" type="submit" name="'.$button_name.'" value="'.$button_text.'">';
        }

        /**
         * Get value of this fieldname
         *
         * @param        $fieldname
         * @param string $default
         *
         * @return mixed
         */

        public function get_value( $fieldname, $default = '' ) {
            $fields = RSSSL_LE()->config->fields();

            if ( ! isset( $fields[ $fieldname ] ) ) {
                return false;
            }

            $source = $fields[ $fieldname ]['source'];
            $options = get_option( 'rsssl_options_' . $source );
            $value   = isset( $options[ $fieldname ] )
                ? $options[ $fieldname ] : false;


            //if no value isset, pass a default
            $value = ( $value !== false ) ? $value
                : apply_filters( 'rsssl_default_value', $default, $fieldname );

            return $value;
        }

        /**
         * Checks if a fieldname exists in the rsssl field list.
         *
         * @param string $fieldname
         *
         * @return bool
         */

        public
        function sanitize_fieldname(
            $fieldname
        ) {
            $fields = RSSSL_LE()->config->fields();
            if ( array_key_exists( $fieldname, $fields ) ) {
                return sanitize_text_field($fieldname);
            }

            return false;
        }


        public
        function get_comment(
            $args
        ) {
            if ( ! isset( $args['comment'] ) ) {
                return;
            }
            ?>
            <div class="rsssl-comment"><?php echo $args['comment'] ?></div>
            <?php
        }

        public
        function has_errors() {
            if ( count( $this->form_errors ) > 0 ) {
                return true;
            }


            return false;
        }


    }
} //class closure