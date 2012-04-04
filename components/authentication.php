<?php

class QM_Authentication extends QM {

	var $id = 'authentication';

	function __construct() {
		parent::__construct();
		add_filter( 'plugins_loaded', array( $this, 'setup' ) );
	}

	function setup() {

		if ( !defined( 'QUERY_MONITOR_COOKIE' ) )
			define( 'QUERY_MONITOR_COOKIE', 'query_monitor_' . COOKIEHASH );

	}

	function show_query_monitor() {
		if ( isset( $_COOKIE[QUERY_MONITOR_COOKIE] ) )
			return $this->verify_nonce( $_COOKIE[QUERY_MONITOR_COOKIE], 'view_query_monitor' );
		return false;
	}

	function output( $args, $data ) {

		# @TODO non-js fallback

		echo '<div class="qm" id="' . $args['id'] . '">';
		echo '<table cellspacing="0">';
		echo '<thead>';
		echo '<tr>';
		echo '<th>' . __( 'Authentication', 'query-monitor' ) . '</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';

		$name   = QUERY_MONITOR_COOKIE;
		$domain = COOKIE_DOMAIN;
		$path   = COOKIEPATH;
		$value  = $this->create_nonce( 'view_query_monitor' );

		if ( !isset( $_COOKIE[$name] ) ) {

			$text = esc_js( __( 'Authentication cookie set. You can now view Query Monitor output while logged out or while logged in as a different user.', 'query-monitor' ) );
			$link = "document.cookie='{$name}={$value}; domain={$domain}; path={$path}'; alert('{$text}'); return false;";

			echo '<tr>';
			echo '<td>' . __( 'You can set an authentication cookie which allows you to view Query Monitor output when you&rsquo;re not logged in.', 'query-monitor' ) . '</td>';
			echo '</tr>';
			echo '<tr>';
			echo '<td><a href="#" onclick="' . $link . '">' . __( 'Set authentication cookie', 'query-monitor' ) . '</a></td>';
			echo '</tr>';

		} else {

			$text = esc_js( __( 'Authentication cookie cleared.', 'query-monitor' ) );
			$link = "document.cookie='{$name}=; expires=' + new Date(0).toUTCString() + '; domain={$domain}; path={$path}'; alert('{$text}'); return false;";

			echo '<tr>';
			echo '<td>' . __( 'You currently have an authentication cookie which allows you to view Query Monitor output.', 'query-monitor' ) . '</td>';
			echo '</tr>';
			echo '<tr>';
			echo '<td><a href="#" onclick="' . $link . '">' . __( 'Clear authentication cookie', 'query-monitor' ) . '</a></td>';
			echo '</tr>';

		}

		echo '</tbody>';
		echo '</table>';
		echo '</div>';

	}

	function create_nonce( $action ) {
		# This is just WordPress' nonce implementation minus the user ID
		# check so a nonce can be set in a cookie and used cross-user
		$i = wp_nonce_tick();
		return substr( wp_hash( $i . $action, 'nonce' ), -12, 10 );
	}

	function verify_nonce( $nonce, $action ) {

		$i = wp_nonce_tick();

		if ( substr( wp_hash( $i . $action, 'nonce' ), -12, 10 ) == $nonce )
			return true;
		if ( substr( wp_hash( ( $i - 1 ) . $action, 'nonce' ), -12, 10 ) == $nonce )
			return true;

		return false;

	}

}

function register_qm_authentication( $qm ) {
	$qm['authentication'] = new QM_Authentication;
	return $qm;
}

add_filter( 'query_monitor_components', 'register_qm_authentication', 130 );

?>