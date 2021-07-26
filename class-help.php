<?php
defined('ABSPATH') or die("you do not have access to this page!");
if ( ! class_exists( 'rsssl_help' ) ) {
    class rsssl_help {
        private static $_this;

        function __construct() {
        if ( isset( self::$_this ) )
            wp_die( sprintf( __( '%s is a singleton class and you cannot create a second instance.','really-simple-ssl' ), get_class( $this ) ) );

        self::$_this = $this;
        }

        static function this() {
            return self::$_this;
        }

        public function get_help_tip($str, $return=false, $add_css = false ){
            if ($return) {
                ob_start();
            }

            if ( $add_css ) { ?>
                <style>
                    [data-rsssl-tooltip] {
                        position: relative;
                        cursor: pointer;
                    }
                    /* Base styles for the entire tooltip */
                    [data-rsssl-tooltip]:before,
                    [data-rsssl-tooltip]:after {
                        position: absolute;
                        visibility: hidden;
                        -ms-filter: "progid:DXImageTransform.Microsoft.Alpha(Opacity=0)";
                        filter: progid:DXImageTransform.Microsoft.Alpha(Opacity=0);
                        opacity: 0;
                        -webkit-transition: opacity 0.2s ease-in-out, visibility 0.2s ease-in-out, -webkit-transform 0.2s cubic-bezier(0.71, 1.7, 0.77, 1.24);
                        -moz-transition: opacity 0.2s ease-in-out, visibility 0.2s ease-in-out, -moz-transform 0.2s cubic-bezier(0.71, 1.7, 0.77, 1.24);
                        transition: opacity 0.2s ease-in-out, visibility 0.2s ease-in-out, transform 0.2s cubic-bezier(0.71, 1.7, 0.77, 1.24);
                        -webkit-transform: translate3d(0, 0, 0);
                        -moz-transform: translate3d(0, 0, 0);
                        transform: translate3d(0, 0, 0);
                        pointer-events: none;
                    }

                    /* Show the entire rsssl-tooltip on hover and focus */
                    [data-rsssl-tooltip]:hover:before,
                    [data-rsssl-tooltip]:hover:after,
                    [data-rsssl-tooltip]:focus:before,
                    [data-rsssl-tooltip]:focus:after {
                        visibility: visible;
                        -ms-filter: "progid:DXImageTransform.Microsoft.Alpha(Opacity=100)";
                        filter: progid:DXImageTransform.Microsoft.Alpha(Opacity=100);
                        opacity: 1;
                    }
                    [data-rsssl-tooltip]:before {
                        z-index: 1001;
                        border: 6px solid transparent;
                        background: transparent;
                        content: "";
                    }
                    [data-rsssl-tooltip]:after {
                        z-index: 1000;
                        padding: 8px;
                        width: 220px;
                        background-color: #000;
                        background-color: hsla(0, 0%, 20%, 0.9);
                        color: #fff;
                        content: attr(data-rsssl-tooltip);
                        font-size: 14px;
                        line-height: 1.2;
                    }

                    .rsssl-tooltip-right {
                        font-weight: normal;
                    }
                    [data-rsssl-tooltip]:before,
                    [data-rsssl-tooltip]:after {
                        bottom: 100%;
                        left: 50%;
                    }
                    .rsssl-tooltip-right:before,
                    .rsssl-tooltip-right:after {
                        bottom: 50%;
                        left: 100%;
                    }
                    [data-rsssl-tooltip]:before {
                        margin-left: -6px;
                        margin-bottom: -12px;
                        border-top-color: #000;
                        border-top-color: hsla(0, 0%, 20%, 0.9);
                    }
                    [data-rsssl-tooltip]:after{
                        margin-left: -80px;
                    }
                    .rsssl-tooltip-right:before {
                        margin-bottom: 0;
                        margin-left: -12px;
                        border-top-color: transparent;
                        border-right-color: #000;
                        border-right-color: hsla(0, 0%, 20%, 0.9);
                    }
                    .rsssl-tooltip-right:hover:before,
                    .rsssl-tooltip-right:hover:after,
                    .rsssl-tooltip-right:focus:before,
                    .rsssl-tooltip-right:focus:after {
                        -webkit-transform: translateX(12px);
                        -moz-transform: translateX(12px);
                        transform: translateX(12px);
                    }
                    .rsssl-tooltip-right:before {
                        top: 3px;
                    }
                    .rsssl-tooltip-right:after {
                        margin-left: 0;
                        margin-bottom: -25px;
                    }
                </style>
            <?php }
            ?>

            <span class="rsssl-tooltip-right tooltip-right" data-rsssl-tooltip="<?php echo $str?>">
                <span class="dashicons dashicons-editor-help"></span>
            </span>
            <?php
            if ($return) {
                $content = ob_get_clean();
                return $content;
            }
        }

	    /**
         * Break current row, and start new one.
	     * @param string $str
         * @param string $class
	     */
        public function get_comment($str, $class = false) {
            if (strlen($str) === 0) return;
            ?>
            </td></tr><tr class="rsssl-comment-text <?php echo esc_attr($class)?>"><td colspan="2"><?php echo $str;?></td></tr>
            <?php
        }

    }//class closure
} //if class exists closure
