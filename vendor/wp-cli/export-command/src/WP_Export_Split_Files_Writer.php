<?php

class WP_Export_Split_Files_Writer extends WP_Export_Base_Writer {
	private $max_file_size;
	private $destination_directory;
	private $filename_template;
	private $before_posts_xml;
	private $after_posts_xml;

	private $f;
	private $next_file_number    = 0;
	private $current_file_size   = 0;
	private $available_sections  = array(
		'header',
		'site_metadata',
		'authors',
		'categories',
		'tags',
		'nav_menu_terms',
		'custom_taxonomies_terms',
		'rss2_head_action',
	);
	private $subsequent_sections = array();

	public function __construct( $formatter, $writer_args = [] ) {
		parent::__construct( $formatter );

		if ( ! defined( 'MB_IN_BYTES' ) ) {
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedConstantFound -- WordPress native constants.
			define( 'MB_IN_BYTES', 1024 * 1024 );
		}

		//TODO: check if args are not missing
		if ( is_null( $writer_args['max_file_size'] ) ) {
			$this->max_file_size = 15 * MB_IN_BYTES;
		} elseif ( WP_CLI_EXPORT_COMMAND_NO_SPLIT === (string) $writer_args['max_file_size'] ) {
			$this->max_file_size = WP_CLI_EXPORT_COMMAND_NO_SPLIT;
		} else {
			$this->max_file_size = $writer_args['max_file_size'] * MB_IN_BYTES;
		}

		// Filter and handle condition where subsequent export files should not contain
		// all of the export sections
		if ( is_array( $writer_args['include_once'] ) && ! empty( $writer_args['include_once'] ) ) {
			$this->subsequent_sections = array_diff( $this->available_sections, $writer_args['include_once'] );
		}

		$this->destination_directory = $writer_args['destination_directory'];
		$this->filename_template     = $writer_args['filename_template'];
		$this->before_posts_xml      = $this->formatter->before_posts();
		$this->after_posts_xml       = $this->formatter->after_posts();
	}

	public function export() {
		$this->start_new_file();
		foreach ( $this->formatter->posts() as $post_xml ) {
			if ( WP_CLI_EXPORT_COMMAND_NO_SPLIT !== $this->max_file_size && $this->current_file_size + strlen( $post_xml ) > $this->max_file_size ) {
				$this->start_new_file();
			}
			$this->write( $post_xml );
		}
		$this->close_current_file();
	}

	protected function write( $xml ) {
		$res = fwrite( $this->f, $xml );
		if ( false === $res ) {
			throw new WP_Export_Exception( 'WP Export: error writing to export file.' );
		}
		$this->current_file_size += strlen( $xml );
	}

	private function start_new_file() {
		if ( $this->f ) {
			$this->close_current_file();
		}
		$file_path = $this->next_file_path();
		$this->f   = fopen( $file_path, 'w' );
		if ( ! $this->f ) {
			throw new WP_Export_Exception( "WP Export: error opening {$file_path} for writing." );
		}
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Possibly used by third party extension.
		do_action( 'wp_export_new_file', $file_path );
		$this->current_file_size = 0;

		$this->write( $this->before_posts_xml );
		if ( 1 === $this->next_file_number && ! empty( $this->subsequent_sections ) ) {
			$this->before_posts_xml = $this->formatter->before_posts( $this->subsequent_sections );
		}
	}

	private function close_current_file() {
		if ( ! $this->f ) {
			return;
		}
		$this->write( $this->after_posts_xml );
		fclose( $this->f );
	}

	private function next_file_name() {
		$next_file_name = sprintf( $this->filename_template, $this->next_file_number );
		$this->next_file_number++;
		return $next_file_name;
	}

	private function next_file_path() {
		return untrailingslashit( $this->destination_directory ) . DIRECTORY_SEPARATOR . $this->next_file_name();
	}

}
