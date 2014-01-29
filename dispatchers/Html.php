<?php
/*

Copyright 2014 John Blackbourn

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

*/

class QM_Dispatcher_Html extends QM_Dispatcher {

	public $id = 'html';

	public function __construct( QM_Plugin $qm ) {

		add_action( 'admin_bar_menu', array( $this, 'action_admin_bar_menu' ), 999 );

		parent::__construct( $qm );

	}

	public function action_admin_bar_menu( WP_Admin_Bar $wp_admin_bar ) {

		if ( ! $this->qm->user_can_view() ) {
			return;
		}

		$class = implode( ' ', array( 'hide-if-js', QM_Util::wpv() ) );
		$title = __( 'Query Monitor', 'query-monitor' );

		$wp_admin_bar->add_menu( array(
			'id'    => 'query-monitor',
			'title' => $title,
			'href'  => '#qm-overview',
			'meta'  => array(
				'classname' => $class
			)
		) );

		$wp_admin_bar->add_menu( array(
			'parent' => 'query-monitor',
			'id'     => 'query-monitor-placeholder',
			'title'  => $title,
			'href'   => '#qm-overview'
		) );

	}

	public function init() {

		if ( ! $this->qm->user_can_view() ) {
			return;
		}

		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', 1 );
		}

		add_action( 'wp_enqueue_scripts',    array( $this, 'enqueue_assets' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'login_enqueue_scripts', array( $this, 'enqueue_assets' ) );

	}

	public function enqueue_assets() {

		global $wp_locale;

		wp_enqueue_style(
			'query-monitor',
			$this->qm->plugin_url( 'assets/query-monitor.css' ),
			null,
			$this->qm->plugin_ver( 'assets/query-monitor.css' )
		);
		wp_enqueue_script(
			'query-monitor',
			$this->qm->plugin_url( 'assets/query-monitor.js' ),
			array( 'jquery' ),
			$this->qm->plugin_ver( 'assets/query-monitor.js' ),
			true
		);
		wp_localize_script(
			'query-monitor',
			'qm_locale',
			(array) $wp_locale
		);
		wp_localize_script(
			'query-monitor',
			'qm_l10n',
			array(
				'ajax_error' => __( 'PHP Error in AJAX Response', 'query-monitor' ),
			)
		);

	}

	public function before_output() {

		require_once $this->qm->plugin_path( 'output/Html.php' );

		foreach ( glob( $this->qm->plugin_path( 'output/html/*.php' ) ) as $output ) {
			include $output;
		}

		if ( !is_admin_bar_showing() )
			$class = 'qm-show';
		else
			$class = '';

		echo '<div id="qm" class="' . $class . '">';
		echo '<div id="qm-wrapper">';
		echo '<p>' . __( 'Query Monitor', 'query-monitor' ) . '</p>';

	}

	public function after_output() {

		echo '</div>';
		echo '</div>';

		$json = array(
			'menu'        => $this->js_admin_bar_menu(),
			'ajax_errors' => array() # @TODO move this into the php_errors collector
		);

		echo '<script type="text/javascript">' . "\n\n";
		echo 'var qm = ' . json_encode( $json ) . ';' . "\n\n";
		echo '</script>' . "\n\n";

	}

	public function get_outputter( QM_Collector $collector ) {
		return new QM_Output_Html( $collector );
	}

	public function js_admin_bar_menu() {

		$class = implode( ' ', apply_filters( 'query_monitor_class', array( QM_Util::wpv() ) ) );
		$title = implode( ' &nbsp; ', apply_filters( 'query_monitor_title', array() ) );

		if ( empty( $title ) )
			$title = __( 'Query Monitor', 'query-monitor' );

		$admin_bar_menu = array(
			'top' => array(
				'title'     => sprintf( '<span class="ab-icon">QM</span><span class="ab-label">%s</span>', $title ),
				'classname' => $class
			),
			'sub' => array()
		);

		foreach ( apply_filters( 'query_monitor_menus', array() ) as $menu )
			$admin_bar_menu['sub'][] = $menu;

		return $admin_bar_menu;

	}

	public function active() {

		if ( ! $this->qm->user_can_view() ) {
			return false;
		}

		if ( ! ( did_action( 'wp_footer' ) or did_action( 'admin_footer' ) or did_action( 'login_footer' ) ) ) {
			return false;
		}

		if ( QM_Util::is_async() ) {
			return false;
		}

		return true;

	}

}

function register_qm_dispatcher_html( array $dispatchers, QM_Plugin $qm ) {
	$dispatchers['html'] = new QM_Dispatcher_Html( $qm );
	return $dispatchers;
}

add_filter( 'query_monitor_dispatchers', 'register_qm_dispatcher_html', 10, 2 );
