<?php

class QM_Environment extends QM {

	var $id = 'environment';

	function __construct() {
		parent::__construct();
		add_filter( 'query_monitor_menus', array( $this, 'admin_menu' ), 80 );
	}

	function admin_menu( $menu ) {

		$menu[] = $this->menu( array(
			'title' => __( 'Environment', 'query-monitor' )
		) );
		return $menu;

	}

	function process() {

		global $wp_version, $blog_id;

		$vars = array(
			'key_buffer_size'    => true,  # Key cache size limit
			'max_allowed_packet' => false, # Individual query size limit
			'max_connections'    => false, # Max number of client connections
		#	'profiling'          => 'OFF', # Query profiling on or off
			'query_cache_limit'  => true,  # Individual query cache size limit
			'query_cache_size'   => true,  # Total cache size limit
			'query_cache_type'   => 'ON'   # Query cache on or off
		);

		if ( $dbq = $this->get_component( 'db_queries' ) ) {

			foreach ( $dbq->db_objects as $id => $db ) {

				if ( !$dbq->is_db_object( $db ) )
					continue;

				$variables = $db->get_results( "
					SHOW VARIABLES
					WHERE Variable_name IN ( '" . implode( "', '", array_keys( $vars ) ) . "' )
				" );

				$this->data['db'][$id] = array(
					'version'   => mysql_get_server_info( $db->dbh ),
					'user'      => $db->dbuser,
					'host'      => $db->dbhost,
					'name'      => $db->dbname,
					'vars'      => $vars,
					'variables' => $variables
				);

			}

		}

		if ( function_exists( 'posix_getpwuid' ) ) {

			$u = posix_getpwuid( posix_getuid() );
			$g = posix_getgrgid( $u['gid'] );
			$php_u = esc_html( $u['name'] . ':' . $g['name'] );

		} else if ( function_exists( 'exec' ) ) {

			$php_u = esc_html( exec( 'whoami' ) );

		} else {

			$php_u = '<em>' . __( 'Unknown', 'query-monitor' ) . '</em>';

		}

		$this->data['php'] = array(
			'version' => phpversion(),
			'user'    => $php_u
		);

		$wp_debug = ( WP_DEBUG ) ? 'ON' : 'OFF';

		$this->data['wp'] = array(
			'version'  => $wp_version,
			'wp_debug' => $wp_debug,
			'blog_id'  => $blog_id
		);

	}

	function output( $args, $data ) {

		echo '<div class="qm" id="' . $args['id'] . '">';
		echo '<table cellspacing="0">';
		echo '<thead>';
		echo '<tr>';
		echo '<th colspan="3">' . __( 'Environment', 'query-monitor' ) . '</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';

		echo '<tr>';
		echo '<td rowspan="2">PHP</td>';
		echo '<td>version</td>';
		echo "<td>{$data['php']['version']}</td>";
		echo '</tr>';
		echo '<tr>';
		echo '<td>user</td>';
		echo "<td>{$data['php']['user']}</td>";
		echo '</tr>';

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
				$warn   = __( "This value is not optimal. Check the recommended setting for '%s'.", 'query-monitor' );
				$search = __( 'http://www.google.com/search?q=mysql+performance+%s', 'query-monitor' );

				foreach ( $db['variables'] as $setting ) {

					$key = $setting->Variable_name;
					$val = $setting->Value;
					$prepend = '';
					$warning = '&nbsp;(<a class="qm-warn" href="' . esc_url( sprintf( $search, $key ) ) . '" target="_blank" title="' . esc_attr( sprintf( $warn, $key ) ) . '">!</a>)';

					if ( ( true === $db['vars'][$key] ) and empty( $val ) )
						$prepend .= $warning;
					else if ( is_string( $db['vars'][$key] ) and ( $val !== $db['vars'][$key] ) )
						$prepend .= $warning;

					if ( is_numeric( $val ) and ( $val >= ( 1024*1024 ) ) )
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

			}

		}

		$wp_span = 2;

		if ( $this->is_multisite )
			$wp_span++;

		echo '<tr>';
		echo '<td rowspan="' . $wp_span . '">WP</td>';
		echo '<td>version</td>';
		echo "<td>{$data['wp']['version']}</td>";
		echo '</tr>';

		if ( $this->is_multisite ) {
			echo '<tr>';
			echo '<td>blog_id</td>';
			echo "<td>{$data['wp']['blog_id']}</td>";
			echo '</tr>';
		}

		echo '<tr>';
		echo '<td>WP_DEBUG</td>';
		echo "<td>{$data['wp']['wp_debug']}</td>";
		echo '</tr>';

		echo '</tbody>';
		echo '</table>';
		echo '</div>';

	}

}

function register_qm_environment( $qm ) {
	$qm['environment'] = new QM_Environment;
	return $qm;
}

add_filter( 'query_monitor_components', 'register_qm_environment', 90 );

?>
