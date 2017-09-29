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

class QM_Activation extends QM_Plugin {

	protected function __construct( $file ) {

		# Filters
		add_filter( 'pre_update_option_active_plugins',               array( $this, 'filter_active_plugins' ) );
		add_filter( 'pre_update_site_option_active_sitewide_plugins', array( $this, 'filter_active_sitewide_plugins' ) );

		# Activation and deactivation
		register_activation_hook( $file, array( $this, 'activate' ) );
		register_deactivation_hook( $file, array( $this, 'deactivate' ) );

		# Parent setup:
		parent::__construct( $file );

	}

	public function activate( $sitewide = false ) {

		if ( $admins = QM_Util::get_admins() ) {
			$admins->add_cap( 'view_query_monitor' );
		}

		if ( ! file_exists( $db = WP_CONTENT_DIR . '/db.php' ) && function_exists( 'symlink' ) ) {
			@symlink( plugin_dir_path( $this->file ) . 'wp-content/db.php', $db ); // @codingStandardsIgnoreLine
		}

		if ( $sitewide ) {
			update_site_option( 'active_sitewide_plugins', get_site_option( 'active_sitewide_plugins' ) );
		} else {
			update_option( 'active_plugins', get_option( 'active_plugins' ) );
		}

	}

	public function deactivate() {

		if ( $admins = QM_Util::get_admins() ) {
			$admins->remove_cap( 'view_query_monitor' );
		}

		# Only delete db.php if it belongs to Query Monitor
		if ( file_exists( WP_CONTENT_DIR . '/db.php' ) && class_exists( 'QM_DB' ) ) {
			unlink( WP_CONTENT_DIR . '/db.php' ); // @codingStandardsIgnoreLine
		}

	}

	public function filter_active_plugins( $plugins ) {

		// this needs to run on the cli too

		if ( empty( $plugins ) ) {
			return $plugins;
		}

		$f = preg_quote( basename( $this->plugin_base() ) );

		return array_merge(
			preg_grep( '/' . $f . '$/', $plugins ),
			preg_grep( '/' . $f . '$/', $plugins, PREG_GREP_INVERT )
		);

	}

	public function filter_active_sitewide_plugins( $plugins ) {

		if ( empty( $plugins ) ) {
			return $plugins;
		}

		$f = $this->plugin_base();

		if ( isset( $plugins[$f] ) ) {

			unset( $plugins[$f] );

			return array_merge( array(
				$f => time(),
			), $plugins );

		} else {
			return $plugins;
		}

	}

	public static function init( $file = null ) {

		static $instance = null;

		if ( ! $instance ) {
			$instance = new QM_Activation( $file );
		}

		return $instance;

	}

}
