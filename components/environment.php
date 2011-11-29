<?php

class QM_Environment extends QM {

	var $id = 'environment';

	function __construct() {

		parent::__construct();

	}

	function admin_menu() {

		return $this->menu( array(
			'title' => __( 'Environment', 'query_monitor' )
		) );

	}

	function output() {

		global $wpdb;

		# We could attempt to calculate optimal values here but there are just too many factors to consider.

		$vars = array(
			'key_buffer_size'    => true,  # Key cache limit
			'max_allowed_packet' => false, # Max individual query size
			'max_connections'    => false, # Max client connections
			'query_cache_limit'  => true,  # Individual query cache limit
			'query_cache_size'   => true,  # Query cache limit
			'query_cache_type'   => 'ON'   # Query cache on or off
		);

		$version = $wpdb->get_row( "
			SHOW VARIABLES
			WHERE Variable_name = 'version'
		" );
		$variables = $wpdb->get_results( "
			SHOW VARIABLES
			WHERE Variable_name IN ( '" . implode( "', '", array_keys( $vars ) ) . "' )
		" );

		echo '<table class="qm" cellspacing="0" id="' . $this->id() . '">';
		echo '<thead>';
		echo '<tr>';
		echo '<th colspan="3">' . __( 'Environment', 'query_monitor' ) . '</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';

		if ( function_exists( 'posix_getpwuid' ) ) {

			$u = posix_getpwuid( posix_getuid() );
			$g = posix_getgrgid( $u['gid'] );
			$php_u = esc_html( $u['name'] . ':' . $g['name'] );

		} else if ( function_exists( 'exec' ) ) {

			$php_u = esc_html( exec( 'whoami' ) );

		} else {

			$php_u = '<em>' . __( 'Unknown', 'query_monitor' ) . '</em>';

		}

		echo '<tr>';
		echo '<td rowspan="2">PHP</td>';
		echo '<td>version</td>';
		echo '<td>' . phpversion() . '</td>';
		echo '</tr>';
		echo '<tr>';
		echo '<td>user</td>';
		echo "<td>{$php_u}</td>";
		echo '</tr>';

		echo '<tr>';
		echo '<td rowspan="' . ( 2 + count( $variables ) ) . '">MySQL</td>';
		echo '<td>version</td>';
		echo '<td>' . $version->Value . '</td>';
		echo '</tr>';

		echo '<tr>';
		echo '<td>user</td>';
		echo '<td>' . DB_USER . '</td>';
		echo '</tr>';

		echo '<tr>';

		$first  = true;
		$warn   = __( "This value is not optimal. Check the recommended setting for '%s'.", 'query_monitor' );
		$search = __( 'http://www.google.com/search?q=mysql+performance+%s', 'query_monitor' );

		foreach ( $variables as $setting ) {

			$key = $setting->Variable_name;
			$val = $setting->Value;
			$prepend = '';
			$warning = '&nbsp;(<a class="qm-warn" href="' . esc_url( sprintf( $search, $key ) ) . '" target="_blank" title="' . esc_attr( sprintf( $warn, $key ) ) . '">!</a>)';

			if ( ( true === $vars[$key] ) and empty( $val ) )
				$prepend .= $warning;
			else if ( is_string( $vars[$key] ) and ( $val !== $vars[$key] ) )
				$prepend .= $warning;

			if ( is_numeric( $val ) and ( $val >= 1024 ) )
				$prepend .= '<br /><span class="qm-info">~' . size_format( $val ) . '</span>';

			if ( !$first )
				echo '<tr>';

			$key = esc_html( $key );
			$val = esc_html( $val );

			echo "<td>{$key}</td>";
			echo "<td>{$val}{$prepend}</td>";

			echo '</tr>';

			$first = false;

		}

		$wp_debug = ( WP_DEBUG ) ? 'ON' : 'OFF';
		$wp_span = 2;

		if ( is_multisite() )
			$wp_span++;

		echo '<tr>';
		echo '<td rowspan="' . $wp_span . '">WP</td>';
		echo '<td>version</td>';
		echo "<td>{$GLOBALS['wp_version']}</td>";
		echo '</tr>';

		if ( is_multisite() ) {
			echo '<tr>';
			echo '<td>blog_id</td>';
			echo "<td>{$GLOBALS['blog_id']}</td>";
			echo '</tr>';
		}

		echo '<tr>';
		echo '<td>WP_DEBUG</td>';
		echo "<td>{$wp_debug}</td>";
		echo '</tr>';

		echo '</tbody>';
		echo '</table>';

	}

}

function register_qm_environment( $qm ) {
	$qm['environment'] = new QM_Environment;
	return $qm;
}

add_filter( 'qm', 'register_qm_environment' );

?>