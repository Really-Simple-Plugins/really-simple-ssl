<?php
defined( 'ABSPATH' ) or die( "you do not have acces to this page!" );

if ( ! class_exists( "rsssl_config" ) ) {

    class rsssl_config {
        private static $_this;
        public $fields = array();
        public $sections;
        public $pages;
        public $warning_types;
        public $yes_no;
        public $countries;
        public $regions;
        public $eu_countries;
        public $languages;

        function __construct() {
            if ( isset( self::$_this ) ) {
                wp_die( sprintf( '%s is a singleton class and you cannot create a second instance.',
                    get_class( $this ) ) );
            }

            self::$_this = $this;


            //common options type
            $this->yes_no = array(
                'yes' => __( 'Yes', 'really-simple-ssl' ),
                'no'  => __( 'No', 'really-simple-ssl' ),
            );

            $this->languages = $this->get_supported_languages();


            /* config files */
//            require_once( rsssl_path . '/config/countries.php' );
            require_once( rsssl_path . '/lets-encrypt/config/steps.php' );
            require_once( rsssl_path . '/lets-encrypt/config/questions-wizard.php' );
//            require_once( rsssl_path . '/config/documents/documents.php' );
//            require_once( rsssl_path . '/config/documents/terms-conditions.php' );

            /**
             * Preload fields with a filter, to allow for overriding types
             */
            add_action( 'plugins_loaded', array( $this, 'preload_init' ), 10 );

            /**
             * The integrations are loaded with priority 10
             * Because we want to initialize after that, we use 15 here
             */
            add_action( 'plugins_loaded', array( $this, 'init' ), 15 );
        }

        static function this() {
            return self::$_this;
        }


        public function get_section_by_id( $id ) {

            $steps = $this->steps['terms-conditions'];
            foreach ( $steps as $step ) {
                if ( ! isset( $step['sections'] ) ) {
                    continue;
                }
                $sections = $step['sections'];

                //because the step arrays start with one instead of 0, we increase with one
                return array_search( $id, array_column( $sections, 'id' ) ) + 1;
            }

        }

        public function get_step_by_id( $id ) {
            $steps = $this->steps['terms-conditions'];

            //because the step arrays start with one instead of 0, we increase with one
            return array_search( $id, array_column( $steps, 'id' ) ) + 1;
        }


        public function fields(
            $page = false, $step = false, $section = false,
            $get_by_fieldname = false
        ) {

            $output = array();
            $fields = $this->fields;
            if ( $page ) {
                $fields = cmplz_tc_array_filter_multidimensional( $this->fields,
                    'source', $page );
            }

            foreach ( $fields as $fieldname => $field ) {
                if ( $get_by_fieldname && $fieldname !== $get_by_fieldname ) {
                    continue;
                }

                if ( $step ) {
                    if ( $section && isset( $field['section'] ) ) {
                        if ( ( $field['step'] == $step
                                || ( is_array( $field['step'] )
                                    && in_array( $step, $field['step'] ) ) )
                            && ( $field['section'] == $section )
                        ) {
                            $output[ $fieldname ] = $field;
                        }
                    } else {
                        if ( ( $field['step'] == $step )
                            || ( is_array( $field['step'] )
                                && in_array( $step, $field['step'] ) )
                        ) {
                            $output[ $fieldname ] = $field;
                        }
                    }
                }
                if ( ! $step ) {
                    $output[ $fieldname ] = $field;
                }

            }

            return $output;
        }

        public function has_sections( $page, $step ) {
            if ( isset( $this->steps[ $page ][ $step ]["sections"] ) ) {
                return true;
            }

            return false;
        }

        public function preload_init(){
            $this->fields = apply_filters( 'cmplz_fields_load_types', $this->fields );
        }

        public function init() {
            $this->fields = apply_filters( 'cmplz_fields', $this->fields );
            if ( ! is_admin() ) {
                $regions = cmplz_tc_get_regions();
                foreach ( $regions as $region => $label ) {
                    if ( !isset( $this->pages[ $region ] ) ) continue;

                    foreach ( $this->pages[ $region ] as $type => $data ) {
                        $this->pages[ $region ][ $type ]['document_elements']
                            = apply_filters( 'cmplz_document_elements',
                            $this->pages[ $region ][ $type ]['document_elements'],
                            $region, $type, $this->fields() );
                    }
                }
            }
        }

        /**
         * Get an array of languages used on this site in format array('en' => 'en')
         *
         * @param bool $count
         *
         * @return int|array
         */

        public function get_supported_languages( $count = false ) {
            $site_locale = cmplz_tc_sanitize_language( get_locale() );

            $languages = array( $site_locale => $site_locale );

            if ( function_exists( 'icl_register_string' ) ) {
                $wpml = apply_filters( 'wpml_active_languages', null, array( 'skip_missing' => 0 ) );
                /**
                 * WPML has changed the index from 'language_code' to 'code' so
                 * we check for both.
                 */
                $wpml_test_index = reset( $wpml );
                if ( isset( $wpml_test_index['language_code'] ) ) {
                    $wpml = wp_list_pluck( $wpml, 'language_code' );
                } elseif ( isset( $wpml_test_index['code'] ) ) {
                    $wpml = wp_list_pluck( $wpml, 'code' );
                } else {
                    $wpml = array();
                }
                $languages = array_merge( $wpml, $languages );
            }

            /**
             * TranslatePress support
             * There does not seem to be an easy accessible API to get the languages, so we retrieve from the settings directly
             */

            if ( class_exists( 'TRP_Translate_Press' ) ) {
                $trp_settings = get_option( 'trp_settings', array() );
                if ( isset( $trp_settings['translation-languages'] ) ) {
                    $trp_languages = $trp_settings['translation-languages'];
                    foreach ( $trp_languages as $language_code ) {
                        $key               = substr( $language_code, 0, 2 );
                        $languages[ $key ] = $key;
                    }
                }
            }

            if ( $count ) {
                return count( $languages );
            }

            $languages = array_map(array($this, 'format_code_lang'), $languages);
            return $languages;
        }


        /**
         * Returns the language for a language code.
         *
         * @since 3.0.0
         *
         * @param string $code Optional. The two-letter language code. Default empty.
         * @return string The language corresponding to $code if it exists. If it does not exist,
         *                then the first two letters of $code is returned.
         */
        public function format_code_lang( $code = '' ) {
            $code       = strtolower( substr( $code, 0, 2 ) );
            $lang_codes = array(
                'aa' => 'Afar',
                'ab' => 'Abkhazian',
                'af' => 'Afrikaans',
                'ak' => 'Akan',
                'sq' => 'Albanian',
                'am' => 'Amharic',
                'ar' => 'Arabic',
                'an' => 'Aragonese',
                'hy' => 'Armenian',
                'as' => 'Assamese',
                'av' => 'Avaric',
                'ae' => 'Avestan',
                'ay' => 'Aymara',
                'az' => 'Azerbaijani',
                'ba' => 'Bashkir',
                'bm' => 'Bambara',
                'eu' => 'Basque',
                'be' => 'Belarusian',
                'bn' => 'Bengali',
                'bh' => 'Bihari',
                'bi' => 'Bislama',
                'bs' => 'Bosnian',
                'br' => 'Breton',
                'bg' => 'Bulgarian',
                'my' => 'Burmese',
                'ca' => 'Catalan; Valencian',
                'ch' => 'Chamorro',
                'ce' => 'Chechen',
                'zh' => 'Chinese',
                'cu' => 'Church Slavic; Old Slavonic; Church Slavonic; Old Bulgarian; Old Church Slavonic',
                'cv' => 'Chuvash',
                'kw' => 'Cornish',
                'co' => 'Corsican',
                'cr' => 'Cree',
                'cs' => 'Czech',
                'da' => 'Danish',
                'dv' => 'Divehi; Dhivehi; Maldivian',
                'nl' => 'Dutch',
                'dz' => 'Dzongkha',
                'en' => 'English',
                'eo' => 'Esperanto',
                'et' => 'Estonian',
                'ee' => 'Ewe',
                'fo' => 'Faroese',
                'fj' => 'Fijjian',
                'fi' => 'Finnish',
                'fr' => 'French',
                'fy' => 'Western Frisian',
                'ff' => 'Fulah',
                'ka' => 'Georgian',
                'de' => 'German',
                'gd' => 'Gaelic; Scottish Gaelic',
                'ga' => 'Irish',
                'gl' => 'Galician',
                'gv' => 'Manx',
                'el' => 'Greek, Modern',
                'gn' => 'Guarani',
                'gu' => 'Gujarati',
                'ht' => 'Haitian; Haitian Creole',
                'ha' => 'Hausa',
                'he' => 'Hebrew',
                'hz' => 'Herero',
                'hi' => 'Hindi',
                'ho' => 'Hiri Motu',
                'hu' => 'Hungarian',
                'ig' => 'Igbo',
                'is' => 'Icelandic',
                'io' => 'Ido',
                'ii' => 'Sichuan Yi',
                'iu' => 'Inuktitut',
                'ie' => 'Interlingue',
                'ia' => 'Interlingua (International Auxiliary Language Association)',
                'id' => 'Indonesian',
                'ik' => 'Inupiaq',
                'it' => 'Italian',
                'jv' => 'Javanese',
                'ja' => 'Japanese',
                'kl' => 'Kalaallisut; Greenlandic',
                'kn' => 'Kannada',
                'ks' => 'Kashmiri',
                'kr' => 'Kanuri',
                'kk' => 'Kazakh',
                'km' => 'Central Khmer',
                'ki' => 'Kikuyu; Gikuyu',
                'rw' => 'Kinyarwanda',
                'ky' => 'Kirghiz; Kyrgyz',
                'kv' => 'Komi',
                'kg' => 'Kongo',
                'ko' => 'Korean',
                'kj' => 'Kuanyama; Kwanyama',
                'ku' => 'Kurdish',
                'lo' => 'Lao',
                'la' => 'Latin',
                'lv' => 'Latvian',
                'li' => 'Limburgan; Limburger; Limburgish',
                'ln' => 'Lingala',
                'lt' => 'Lithuanian',
                'lb' => 'Luxembourgish; Letzeburgesch',
                'lu' => 'Luba-Katanga',
                'lg' => 'Ganda',
                'mk' => 'Macedonian',
                'mh' => 'Marshallese',
                'ml' => 'Malayalam',
                'mi' => 'Maori',
                'mr' => 'Marathi',
                'ms' => 'Malay',
                'mg' => 'Malagasy',
                'mt' => 'Maltese',
                'mo' => 'Moldavian',
                'mn' => 'Mongolian',
                'na' => 'Nauru',
                'nv' => 'Navajo; Navaho',
                'nr' => 'Ndebele, South; South Ndebele',
                'nd' => 'Ndebele, North; North Ndebele',
                'ng' => 'Ndonga',
                'ne' => 'Nepali',
                'nn' => 'Norwegian Nynorsk; Nynorsk, Norwegian',
                'nb' => 'Bokmål, Norwegian, Norwegian Bokmål',
                'no' => 'Norwegian',
                'ny' => 'Chichewa; Chewa; Nyanja',
                'oc' => 'Occitan, Provençal',
                'oj' => 'Ojibwa',
                'or' => 'Oriya',
                'om' => 'Oromo',
                'os' => 'Ossetian; Ossetic',
                'pa' => 'Panjabi; Punjabi',
                'fa' => 'Persian',
                'pi' => 'Pali',
                'pl' => 'Polish',
                'pt' => 'Portuguese',
                'ps' => 'Pushto',
                'qu' => 'Quechua',
                'rm' => 'Romansh',
                'ro' => 'Romanian',
                'rn' => 'Rundi',
                'ru' => 'Russian',
                'sg' => 'Sango',
                'sa' => 'Sanskrit',
                'sr' => 'Serbian',
                'hr' => 'Croatian',
                'si' => 'Sinhala; Sinhalese',
                'sk' => 'Slovak',
                'sl' => 'Slovenian',
                'se' => 'Northern Sami',
                'sm' => 'Samoan',
                'sn' => 'Shona',
                'sd' => 'Sindhi',
                'so' => 'Somali',
                'st' => 'Sotho, Southern',
                'es' => 'Spanish; Castilian',
                'sc' => 'Sardinian',
                'ss' => 'Swati',
                'su' => 'Sundanese',
                'sw' => 'Swahili',
                'sv' => 'Swedish',
                'ty' => 'Tahitian',
                'ta' => 'Tamil',
                'tt' => 'Tatar',
                'te' => 'Telugu',
                'tg' => 'Tajik',
                'tl' => 'Tagalog',
                'th' => 'Thai',
                'bo' => 'Tibetan',
                'ti' => 'Tigrinya',
                'to' => 'Tonga (Tonga Islands)',
                'tn' => 'Tswana',
                'ts' => 'Tsonga',
                'tk' => 'Turkmen',
                'tr' => 'Turkish',
                'tw' => 'Twi',
                'ug' => 'Uighur; Uyghur',
                'uk' => 'Ukrainian',
                'ur' => 'Urdu',
                'uz' => 'Uzbek',
                've' => 'Venda',
                'vi' => 'Vietnamese',
                'vo' => 'Volapük',
                'cy' => 'Welsh',
                'wa' => 'Walloon',
                'wo' => 'Wolof',
                'xh' => 'Xhosa',
                'yi' => 'Yiddish',
                'yo' => 'Yoruba',
                'za' => 'Zhuang; Chuang',
                'zu' => 'Zulu',
            );


            return strtr( $code, $lang_codes );
        }

    }



} //class closure