<?php
/*
Copyright 2009-2017 John Blackbourn

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

*/

class QM_Collector_Theme extends QM_Collector {

	public $id = 'theme';
	protected $got_theme_compat = false;

	public function name() {
		return __( 'Theme', 'query-monitor' );
	}

	public function __construct() {
		parent::__construct();
		add_filter( 'body_class',       array( $this, 'filter_body_class' ), 999 );
		add_filter( 'template_include', array( $this, 'filter_template_include' ), 999 );
		add_filter( 'timber/output',    array( $this, 'filter_timber_output' ), 999, 3 );
		add_action( 'template_redirect', array( $this, 'action_template_redirect' ) );
	}

	public static function get_query_template_names() {
		return array(
			'embed'             => 'is_embed',
			'404'               => 'is_404',
			'search'            => 'is_search',
			'front_page'        => 'is_front_page',
			'home'              => 'is_home',
			'post_type_archive' => 'is_post_type_archive',
			'taxonomy'          => 'is_tax',
			'attachment'        => 'is_attachment',
			'single'            => 'is_single',
			'page'              => 'is_page',
			'singular'          => 'is_singular',
			'category'          => 'is_category',
			'tag'               => 'is_tag',
			'author'            => 'is_author',
			'date'              => 'is_date',
			'archive'           => 'is_archive',
			'index'             => '__return_true',
		);
	}

	// https://core.trac.wordpress.org/ticket/14310
	public function action_template_redirect() {

		foreach ( self::get_query_template_names() as $template => $conditional ) {

			// If a matching theme-compat file is found, further conditional checks won't occur in template-loader.php
			if ( $this->got_theme_compat ) {
				break;
			}

			$get_template = "get_{$template}_template";

			if ( function_exists( $conditional ) && function_exists( $get_template ) && call_user_func( $conditional ) ) {
				$filter = str_replace( '_', '', $template );
				add_filter( "{$filter}_template_hierarchy", array( $this, 'filter_template_hierarchy' ), 999 );
				call_user_func( $get_template );
				remove_filter( "{$filter}_template_hierarchy", array( $this, 'filter_template_hierarchy' ), 999 );
			}
		}

	}

	public function filter_template_hierarchy( array $templates ) {
		if ( ! isset( $this->data['template_hierarchy'] ) ) {
			$this->data['template_hierarchy'] = array();
		}

		foreach ( $templates as $template_name ) {
			if ( file_exists( ABSPATH . WPINC . '/theme-compat/' . $template_name ) ) {
				$this->got_theme_compat = true;
				break;
			}
		}

		$this->data['template_hierarchy'] = array_merge( $this->data['template_hierarchy'], $templates );

		return $templates;
	}

	public function filter_body_class( array $class ) {
		$this->data['body_class'] = $class;
		return $class;
	}

	public function filter_template_include( $template_path ) {
		$this->data['template_path'] = $template_path;
		return $template_path;
	}

	public function filter_timber_output( $output, $data = null, $file = null ) {
		if ( $file ) {
			$this->data['timber_files'][] = $file;
		}

		return $output;
	}

	public function process() {

		if ( ! empty( $this->data['template_path'] ) ) {

			$template_path        = QM_Util::standard_dir( $this->data['template_path'] );
			$stylesheet_directory = QM_Util::standard_dir( get_stylesheet_directory() );
			$template_directory   = QM_Util::standard_dir( get_template_directory() );
			$theme_directory      = QM_Util::standard_dir( get_theme_root() );

			$template_file       = str_replace( array( $stylesheet_directory, $template_directory, ABSPATH ), '', $template_path );
			$template_file       = ltrim( $template_file, '/' );
			$theme_template_file = str_replace( array( $theme_directory, ABSPATH ), '', $template_path );
			$theme_template_file = ltrim( $theme_template_file, '/' );

			$this->data['template_path']       = $template_path;
			$this->data['template_file']       = $template_file;
			$this->data['theme_template_file'] = $theme_template_file;

			foreach ( get_included_files() as $file ) {
				$filename = str_replace( array(
					$stylesheet_directory,
					$template_directory,
				), '', $file );
				if ( $filename !== $file ) {
					$slug          = trim( str_replace( '.php', '', $filename ), '/' );
					$display       = trim( $filename, '/' );
					$theme_display = trim( str_replace( $theme_directory, '', $file ), '/' );
					if ( did_action( "get_template_part_{$slug}" ) ) {
						$this->data['template_parts'][ $file ]       = $display;
						$this->data['theme_template_parts'][ $file ] = $theme_display;
					} else {
						$slug = trim( preg_replace( '|\-[^\-]+$|', '', $slug ), '/' );
						if ( did_action( "get_template_part_{$slug}" ) ) {
							$this->data['template_parts'][ $file ]       = $display;
							$this->data['theme_template_parts'][ $file ] = $theme_display;
						}
					}
				}
			}
		}

		$this->data['stylesheet']     = get_stylesheet();
		$this->data['template']       = get_template();
		$this->data['is_child_theme'] = ( $this->data['stylesheet'] !== $this->data['template'] );

		if ( isset( $this->data['body_class'] ) ) {
			asort( $this->data['body_class'] );
		}

	}

}

function register_qm_collector_theme( array $collectors, QueryMonitor $qm ) {
	$collectors['theme'] = new QM_Collector_Theme;
	return $collectors;
}

if ( ! is_admin() ) {
	add_filter( 'qm/collectors', 'register_qm_collector_theme', 10, 2 );
}
