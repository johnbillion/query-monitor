<?php

class QM_Component_Environment extends QM_Component {

	var $id = 'environment';
	var $php_vars = array(
		'max_execution_time',
		'memory_limit',
		'upload_max_filesize',
		'post_max_size',
		'display_errors',
		'log_errors',
	#	'error_log',
	);

	function __construct() {

		global $wpdb;

		parent::__construct();

		add_filter( 'query_monitor_menus', array( $this, 'admin_menu' ), 110 );

		# If QueryMonitorDB is in place then we'll use the values which were
		# caught early before any plugins had a chance to alter them

		foreach ( $this->php_vars as $setting ) {
			if ( isset( $wpdb->qm_php_vars[$setting] ) )
				$val = $wpdb->qm_php_vars[$setting];
			else
				$val = ini_get( $setting );
			$this->data['php']['variables'][$setting]['before'] = $val;
		}

		if ( isset( $wpdb->qm_php_vars['error_reporting'] ) )
			$val = $wpdb->qm_php_vars['error_reporting'];
		else
			$val = implode( '<br/>', $this->get_error_reporting() );
		$this->data['php']['variables']['error_reporting']['before'] = $val;

	}

	function get_error_reporting() {

		$error_reporting = error_reporting();
		$levels = array();

		$constants = array(
			'E_ERROR',
			'E_WARNING',
			'E_PARSE',
			'E_NOTICE',
			'E_USER_ERROR',
			'E_USER_WARNING',
			'E_USER_NOTICE',
			'E_STRICT',
			'E_RECOVERABLE_ERROR',
			'E_DEPRECATED',
			'E_USER_DEPRECATED',
			'E_ALL'
		);

		foreach ( $constants as $level ) {
			if ( defined( $level ) ) {
				$c = constant( $level );
				if ( $error_reporting & $c ) 
					$levels[$c] = $level;
			}
		}

		return $levels;

	}

	function admin_menu( array $menu ) {

		$menu[] = $this->menu( array(
			'title' => __( 'Environment', 'query-monitor' )
		) );
		return $menu;

	}

	function process() {

		global $wp_version, $blog_id;

		$mysql_vars = array(
			'key_buffer_size'    => true,  # Key cache size limit
			'max_allowed_packet' => false, # Individual query size limit
			'max_connections'    => false, # Max number of client connections
			'query_cache_limit'  => true,  # Individual query cache size limit
			'query_cache_size'   => true,  # Total cache size limit
			'query_cache_type'   => 'ON'   # Query cache on or off
		);
		$php_u = '';

		if ( $dbq = $this->get_component( 'db_queries' ) ) {

			foreach ( $dbq->db_objects as $id => $db ) {

				if ( !is_a( $db, 'wpdb' ) )
					continue;

				$variables = $db->get_results( "
					SHOW VARIABLES
					WHERE Variable_name IN ( '" . implode( "', '", array_keys( $mysql_vars ) ) . "' )
				" );

				$this->data['db'][$id] = array(
					'version'   => mysql_get_server_info( $db->dbh ),
					'user'      => $db->dbuser,
					'host'      => $db->dbhost,
					'name'      => $db->dbname,
					'vars'      => $mysql_vars,
					'variables' => $variables
				);

			}

		}

		if ( function_exists( 'posix_getpwuid' ) ) {

			$u = posix_getpwuid( posix_getuid() );
			$g = posix_getgrgid( $u['gid'] );
			$php_u = esc_html( $u['name'] . ':' . $g['name'] );

		} else if ( isset( $_SERVER['USER'] ) ) {

			$php_u = esc_html( $_SERVER['USER'] );

		} else if ( function_exists( 'exec' ) ) {

			$php_u = esc_html( exec( 'whoami' ) );

		}

		if ( empty( $php_u ) )
			$php_u = '<em>' . __( 'Unknown', 'query-monitor' ) . '</em>';

		$this->data['php']['version'] = phpversion();
		$this->data['php']['user']    = $php_u;

		foreach ( $this->php_vars as $setting )
			$this->data['php']['variables'][$setting]['after'] = ini_get( $setting );

		$this->data['php']['variables']['error_reporting']['after'] = implode( '<br/>', $this->get_error_reporting() );

		$wp_debug = ( WP_DEBUG ) ? 'true' : 'false';

		$this->data['wp'] = array(
			'version'  => $wp_version,
			'wp_debug' => $wp_debug,
			'blog_id'  => $blog_id
		);

		$server = explode( ' ', $_SERVER['SERVER_SOFTWARE'] );
		$server = explode( '/', reset( $server ) );

		if ( isset( $server[1] ) )
			$server_version = $server[1];
		else
			$server_version = '<em>' . __( 'Unknown', 'query-monitor' ) . '</em>';

		$this->data['server'] = array(
			'name'    => $server[0],
			'version' => $server_version,
			'address' => $_SERVER['SERVER_ADDR'],
			'host'    => php_uname( 'n' )
		);

	}

	function output_html( array $args, array $data ) {

		echo '<div class="qm" id="' . $args['id'] . '">';
		echo '<table cellspacing="0">';
		echo '<thead>';
		echo '<tr>';
		echo '<th colspan="3">' . __( 'Environment', 'query-monitor' ) . '</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';

		echo '<tr>';
		echo '<td rowspan="' . ( 2 + count( $data['php']['variables'] ) ) . '">PHP</td>';
		echo '<td>version</td>';
		echo "<td>{$data['php']['version']}</td>";
		echo '</tr>';
		echo '<tr>';
		echo '<td>user</td>';
		echo "<td>{$data['php']['user']}</td>";
		echo '</tr>';

		foreach ( $data['php']['variables'] as $key => $val ) {

			$append = '';

			if ( $val['after'] != $val['before'] )
				$append .= '<br /><span class="qm-info">' . sprintf( __( 'Overridden at runtime from %s', 'query-monitor' ), $val['before'] ) . '</span>';

			echo '<tr>';
			echo "<td>{$key}</td>";
			echo "<td>{$val['after']}{$append}</td>";
			echo '</tr>';
		}

		if ( isset( $data['db'] ) ) {

			foreach ( $data['db'] as $id => $db ) {

				if ( 1 == count( $data['db'] ) )
					$name = 'MySQL';
				else
					$name = $id . '<br />MySQL';

				echo '<tr>';
				echo '<td rowspan="' . ( 4 + count( $db['variables'] ) ) . '">' . $name . '</td>';
				echo '<td>version</td>';
				echo '<td>' . $db['version'] . '</td>';
				echo '</tr>';

				echo '<tr>';
				echo '<td>user</td>';
				echo '<td>' . $db['user'] . '</td>';
				echo '</tr>';

				echo '<tr>';
				echo '<td>host</td>';
				echo '<td>' . $db['host'] . '</td>';
				echo '</tr>';

				echo '<tr>';
				echo '<td>database</td>';
				echo '<td>' . $db['name'] . '</td>';
				echo '</tr>';

				echo '<tr>';

				$first  = true;
				$warn   = __( "This value may not be optimal. Check the recommended configuration for '%s'.", 'query-monitor' );
				$search = __( 'http://www.google.com/search?q=mysql+performance+%s', 'query-monitor' );

				foreach ( $db['variables'] as $setting ) {

					$key = $setting->Variable_name;
					$val = $setting->Value;
					$prepend = '';
					$warning = '&nbsp;<span class="qm-info">(<a href="' . esc_url( sprintf( $search, $key ) ) . '" target="_blank" title="' . esc_attr( sprintf( $warn, $key ) ) . '">' . __( 'Help', 'query-monitor' ) . '</a>)</span>';
					$show_warning = false;

					if ( ( true === $db['vars'][$key] ) and empty( $val ) )
						$show_warning = true;
					else if ( is_string( $db['vars'][$key] ) and ( $val !== $db['vars'][$key] ) )
						$show_warning = true;

					if ( $show_warning )
						$prepend .= $warning;

					if ( is_numeric( $val ) and ( $val >= ( 1024*1024 ) ) )
						$prepend .= '<br /><span class="qm-info">~' . size_format( $val ) . '</span>';

					$class = ( $show_warning ) ? 'qm-warn' : '';

					if ( !$first )
						echo "<tr class='{$class}'>";

					$key = esc_html( $key );
					$val = esc_html( $val );

					echo "<td>{$key}</td>";
					echo "<td>{$val}{$prepend}</td>";

					echo '</tr>';

					$first = false;

				}

			}

		}

		$wp_span = 2;

		if ( is_multisite() )
			$wp_span++;

		echo '<tr>';
		echo '<td rowspan="' . $wp_span . '">WordPress</td>';
		echo '<td>version</td>';
		echo "<td>{$data['wp']['version']}</td>";
		echo '</tr>';

		if ( is_multisite() ) {
			echo '<tr>';
			echo '<td>blog_id</td>';
			echo "<td>{$data['wp']['blog_id']}</td>";
			echo '</tr>';
		}

		echo '<tr>';
		echo '<td>WP_DEBUG</td>';
		echo "<td>{$data['wp']['wp_debug']}</td>";
		echo '</tr>';

		echo '<tr>';
		echo '<td rowspan="4">' . __( 'Server', 'query-monitor' ) . '</td>';
		echo '<td>software</td>';
		echo "<td>{$data['server']['name']}</td>";
		echo '</tr>';

		echo '<tr>';
		echo '<td>version</td>';
		echo "<td>{$data['server']['version']}</td>";
		echo '</tr>';

		echo '<tr>';
		echo '<td>address</td>';
		echo "<td>{$data['server']['address']}</td>";
		echo '</tr>';

		echo '<tr>';
		echo '<td>host</td>';
		echo "<td>{$data['server']['host']}</td>";
		echo '</tr>';

		echo '</tbody>';
		echo '</table>';
		echo '</div>';

	}

}

function register_qm_environment( array $qm ) {
	$qm['environment'] = new QM_Component_Environment;
	return $qm;
}

add_filter( 'query_monitor_components', 'register_qm_environment', 90 );
