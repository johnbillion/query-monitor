<?php

if ( !defined( 'SAVEQUERIES' ) )
	define( 'SAVEQUERIES', true );
if ( !defined( 'QM_DB_EXPENSIVE' ) )
	define( 'QM_DB_EXPENSIVE', 0.05 );
if ( !defined( 'QM_DB_LIMIT' ) )
	define( 'QM_DB_LIMIT', 100 );

class QM_Component_DB_Queries extends QM_Component {

	public $id = 'db_queries';
	public $db_objects = array();

	function __construct() {
		parent::__construct();
		add_filter( 'query_monitor_menus', array( $this, 'admin_menu' ), 10 );
		add_filter( 'query_monitor_title', array( $this, 'admin_title' ), 20 );
		add_filter( 'query_monitor_class', array( $this, 'admin_class' ) );
	}

	function admin_title( array $title ) {
		if ( isset( $this->data['dbs'] ) ) {
			foreach ( $this->data['dbs'] as $db ) {
				$title[] = sprintf(
					_x( '%s<small>S</small>', 'database query time', 'query-monitor' ),
					number_format_i18n( $db->total_time, 4 )
				);
				$title[] = sprintf(
					_x( '%s<small>Q</small>', 'database query number', 'query-monitor' ),
					number_format_i18n( $db->total_qs )
				);
			}
		}
		return $title;
	}

	function admin_class( array $class ) {

		if ( $this->get_errors() )
			$class[] = 'qm-error';
		if ( $this->get_expensive() )
			$class[] = 'qm-expensive';
		return $class;

	}

	function admin_menu( array $menu ) {

		if ( $errors = $this->get_errors() ) {
			$menu[] = $this->menu( array(
				'id'    => 'query-monitor-errors',
				'href'  => '#qm-query-errors',
				'title' => sprintf( __( 'Database Errors (%s)', 'query-monitor' ), number_format_i18n( count( $errors ) ) )
			) );
		}
		if ( $expensive = $this->get_expensive() ) {
			$menu[] = $this->menu( array(
				'id'    => 'query-monitor-expensive',
				'href'  => '#qm-query-expensive',
				'title' => sprintf( __( 'Slow Queries (%s)', 'query-monitor' ), number_format_i18n( count( $expensive ) ) )
			) );
		}
		return $menu;

	}

	function get_errors() {
		if ( !empty( $this->data['errors'] ) )
			return $this->data['errors'];
		return false;
	}

	function get_expensive() {
		if ( !empty( $this->data['expensive'] ) )
			return $this->data['expensive'];
		return false;
	}

	function is_expensive( array $row ) {
		return $row['ltime'] > QM_DB_EXPENSIVE;
	}

	function process() {

		if ( !SAVEQUERIES )
			return;

		$this->data['query_num']  = 0;
		$this->data['total_time'] = 0;
		$this->data['errors']     = array();

		$this->db_objects = apply_filters( 'query_monitor_db_objects', array(
			'$wpdb' => $GLOBALS['wpdb']
		) );

		foreach ( $this->db_objects as $name => $db ) {
			if ( $this->is_db_object( $db ) )
				$this->process_db_object( $name, $db );
		}

	}

	function output( array $args, array $data ) {

		if ( empty( $data['dbs'] ) )
			return;

		if ( !empty( $data['errors'] ) ) {

			echo '<div class="qm qm-queries" id="qm-query-errors">';
			echo '<table cellspacing="0">';
			echo '<thead>';
			echo '<tr>';
			echo '<th colspan="4">' . __( 'Database Errors', 'query-monitor' ) . '</th>';
			echo '</tr>';
			echo '<tr>';
			echo '<th>' . __( 'Query', 'query-monitor' ) . '</th>';
			echo '<th>' . __( 'Caller', 'query-monitor' ) . '</th>';
			echo '<th>' . __( 'Component', 'query-monitor' ) . '</th>';
			echo '<th>' . __( 'Error', 'query-monitor' ) . '</th>';
			echo '</tr>';
			echo '</thead>';
			echo '<tbody>';

			foreach ( $data['errors'] as $row )
				$this->output_query_row( $row, array( 'sql', 'caller', 'component', 'result' ) );

			echo '</tbody>';
			echo '</table>';
			echo '</div>';

		}

		if ( !empty( $data['expensive'] ) ) {

			$dp = strlen( substr( strrchr( QM_DB_EXPENSIVE, '.' ), 1 ) );

			echo '<div class="qm qm-queries" id="qm-query-expensive">';
			echo '<table cellspacing="0">';
			echo '<thead>';
			echo '<tr>';
			echo '<th colspan="5" class="qm-expensive">' . sprintf( __( 'Slow Database Queries (above %ss)', 'query-monitor' ), number_format_i18n( QM_DB_EXPENSIVE, $dp ) ) . '</th>';
			echo '</tr>';
			echo '<tr>';
			echo '<th>' . __( 'Query', 'query-monitor' ) . '</th>';
			echo '<th>' . __( 'Caller', 'query-monitor' ) . '</th>';

			if ( isset( $data['expensive'][0]['component'] ) )
				echo '<th>' . __( 'Component', 'query-monitor' ) . '</th>';

			if ( isset( $data['expensive'][0]['result'] ) )
				echo '<th>' . __( 'Affected Rows', 'query-monitor' ) . '</th>';

			echo '<th>' . __( 'Time', 'query-monitor' ) . '</th>';
			echo '</tr>';
			echo '</thead>';
			echo '<tbody>';

			foreach ( $data['expensive'] as $row )
				$this->output_query_row( $row );

			echo '</tbody>';
			echo '</table>';
			echo '</div>';

		}

		foreach ( $data['dbs'] as $name => $db )
			$this->output_queries( $name, $db );

	}

	function is_db_object( $var ) {
		$objs = array(
			'wpdb',
			'dbrc_wpdb'
		);
		foreach ( $objs as $obj ) {
			if ( $var instanceof $obj )
				return true;
		}
		return false;
	}

	function add_func_time( $func, $ltime, $type ) {

		if ( !isset( $this->data['times'][$func] ) ) {
			$this->data['times'][$func] = array(
				'func'  => $func,
				'calls' => 0,
				'ltime' => 0,
				'types' => array()
			);
		}

		$this->data['times'][$func]['calls']++;
		$this->data['times'][$func]['ltime'] += $ltime;

		if ( isset( $this->data['times'][$func]['types'][$type] ) )
			$this->data['times'][$func]['types'][$type]++;
		else
			$this->data['times'][$func]['types'][$type] = 1;

		# @TODO: this should be in a separate function:
		if ( isset( $this->data['types'][$type] ) )
			$this->data['types'][$type]++;
		else
			$this->data['types'][$type] = 1;

	}

	function add_component_time( $component, $ltime, $type ) {

		if ( !isset( $this->data['component_times'][$component] ) ) {
			$this->data['component_times'][$component] = array(
				'component' => $component,
				'calls'     => 0,
				'ltime'     => 0,
				'types'     => array()
			);
		}

		$this->data['component_times'][$component]['calls']++;
		$this->data['component_times'][$component]['ltime'] += $ltime;

		if ( isset( $this->data['component_times'][$component]['types'][$type] ) )
			$this->data['component_times'][$component]['types'][$type]++;
		else
			$this->data['component_times'][$component]['types'][$type] = 1;

	}

	function process_db_object( $id, wpdb $db ) {

		$rows       = array();
		$types      = array();
		$total_time = 0;
		$total_qs   = 0;

		foreach ( (array) $db->queries as $query ) {

			if ( false !== strpos( $query[2], 'wp_admin_bar' ) and !isset( $_REQUEST['qm_display_admin_bar'] ) )
				continue;

			$sql           = $query[0];
			$ltime         = $query[1];
			$funcs         = $query[2];
			$has_component = isset( $query[3] );
			$has_results   = isset( $query[4] );

			if ( $has_component )
				$stack = $query[3];
			else
				$stack = null;

			if ( $has_results )
				$result = $query[4];
			else
				$result = null;

			$total_time += $ltime;
			$total_qs++;

			if ( null !== $stack ) {

				foreach ( $stack as $f ) {

					$f = QM_Util::standard_dir( $f );
					if ( 'core' != ( $file_component = QM_Util::get_file_component( $f ) ) )
						break;

				}

				switch ( $file_component ) {
					case 'plugin':
					case 'muplugin':
						$plug = plugin_basename( $f );
						if ( strpos( $plug, '/' ) ) {
							$plug = explode( '/', $plug );
							$plug = reset( $plug );
						} else {
							$plug = basename( $plug );
						}
						$component = sprintf( __( 'Plugin: %s', 'query-monitor' ), $plug );
						break;
					case 'stylesheet':
						$component = __( 'Theme', 'query-monitor' );
						break;
					case 'template':
						$component = __( 'Parent Theme', 'query-monitor' );
						break;
					case 'other':
						$component = str_replace( QM_Util::standard_dir( ABSPATH ), '', $f );
						break;
					case 'core':
					default:
						$component = __( 'Core', 'query-monitor' );
						break;
				}

			} else {
				$component = null;
			}

			if ( preg_match( '|\.php$|', $funcs ) ) {
				$func = $funcs;
			} else {
				$func = array_reverse( explode( ', ', $funcs ) );
				$func = reset( $func );
			}

			$sql  = $this->format_sql( $sql );
			$type = preg_split( '/\b/', $sql );
			$type = strtoupper( $type[1] );

			$this->add_func_time( $func, $ltime, $type );

			if ( $has_component )
				$this->add_component_time( $component, $ltime, $type );

			if ( !isset( $types[$type]['total'] ) )
				$types[$type]['total'] = 1;
			else
				$types[$type]['total']++;

			if ( !isset( $types[$type]['funcs'][$func] ) )
				$types[$type]['funcs'][$func] = 1;
			else
				$types[$type]['funcs'][$func]++;

			$row = compact( 'func', 'funcs', 'sql', 'ltime', 'result', 'type', 'component' );

			if ( is_wp_error( $result ) )
				$this->data['errors'][] = $row;

			if ( $this->is_expensive( $row ) )
				$this->data['expensive'][] = $row;

			$rows[] = $row;

		}

		# @TODO standardise these var names:
		$this->data['query_num'] += $total_qs;
		$this->data['total_time'] += $total_time;

		# @TODO put errors in here too:
		# @TODO proper class instead of (object)
		$this->data['dbs'][$id] = (object) compact( 'rows', 'types', 'has_results', 'has_component', 'total_time', 'total_qs' );

	}

	function format_sql( $sql ) {

		$sql = str_replace( array( "\r\n", "\r", "\n", "\t" ), ' ', $sql );
		$sql = esc_html( trim( $sql ) );

		foreach( array(
			'AND', 'DELETE', 'ELSE', 'END', 'FROM', 'GROUP', 'HAVING', 'INNER', 'INSERT', 'LIMIT',
			'ON', 'OR', 'ORDER', 'SELECT', 'SET', 'THEN', 'UPDATE', 'VALUES', 'WHEN', 'WHERE'
		) as $cmd )
			$sql = trim( str_replace( " $cmd ", "<br/>$cmd ", $sql ) );

		return $sql;

	}

	function output_queries( $name, stdClass $db ) {

		# @TODO move more of this into process()

		$rows          = $db->rows;
		$has_results   = $db->has_results;
		$has_component = $db->has_component;
		$total_time    = $db->total_time;
		$total_qs      = $db->total_qs;
		$max_exceeded  = $total_qs > QM_DB_LIMIT;

		$id = sanitize_title( $name );
		$span = 3;

		if ( $has_results )
			$span++;
		if ( $has_component )
			$span++;

		echo '<div class="qm qm-queries" id="qm-queries-' . $id . '">';
		echo '<table cellspacing="0">';
		echo '<thead>';
		echo '<tr>';
		echo '<th colspan="' . $span . '" class="qm-ltr">' . $name . '</th>';
		echo '</tr>';

		if ( $max_exceeded and !isset( $_REQUEST['qm_display_all'] ) ) {
			echo '<tr>';
			echo '<td colspan="' . $span . '" class="qm-expensive">' . sprintf( __( '%1$s %2$s queries were performed on this page load. Only the first %3$d are shown. Total times shown are for all queries.', 'query-monitor' ),
				number_format_i18n( $total_qs ),
				$name,
				number_format_i18n( QM_DB_LIMIT )
			) . '</td>';
			echo '</tr>';
		}

		echo '<tr>';
		echo '<th>' . __( 'Query', 'query-monitor' ) . $this->build_filter( 'type', array_keys( $db->types ) ) . '</th>';
		echo '<th>' . __( 'Caller', 'query-monitor' ) . $this->build_filter( 'caller', array_keys( $this->data['times'] ) ) . '</th>';

		if ( $has_component )
			echo '<th>' . __( 'Component', 'query-monitor' ) . $this->build_filter( 'component', array_keys( $this->data['component_times'] ) ) . '</th>';

		if ( $has_results )
			echo '<th>' . __( 'Affected Rows', 'query-monitor' ) . '</th>';

		echo '<th>' . __( 'Time', 'query-monitor' ) . '</th>';
		echo '</tr>';
		echo '</thead>';
		echo '<tbody>';

		if ( isset( $_REQUEST['qm_sort'] ) and ( 'time' == $_REQUEST['qm_sort'] ) )
			usort( $rows, array( 'QM_Util', 'sort' ) );

		if ( !empty( $rows ) ) {

			foreach ( $rows as $i => $row ) {

				if ( ( $i === QM_DB_LIMIT ) and !isset( $_REQUEST['qm_display_all'] ) )
					break;

				$this->output_query_row( $row );

			}

			$total_stime = number_format_i18n( $total_time, 4 );
			$total_ltime = number_format_i18n( $total_time, 10 );

			echo '<tr>';
			echo '<td valign="top" colspan="' . ( $span - 1 ) . '">' . sprintf( _n( '%s query', '%s queries', $total_qs, 'query-monitor' ), number_format_i18n( $total_qs ) ) . '</td>';
			echo "<td valign='top' title='{$total_ltime}'>{$total_stime}</td>";
			echo '</tr>';

			echo '<tr class="qm-queries-shown qm-hide">';
			echo '<td valign="top" colspan="' . ( $span - 1 ) . '">' . sprintf( __( 'Queries in filter: %s', 'query-monitor' ), '<span class="qm-queries-number">' . number_format_i18n( $total_qs ) . '</span>' ) . '</td>';
			echo "<td valign='top' class='qm-queries-time'>{$total_stime}</td>";
			echo '</tr>';

		} else {

			echo '<td colspan="' . $span . '" style="text-align:center !important"><em>' . __( 'none', 'query-monitor' ) . '</em></td>';

		}

		echo '</tbody>';
		echo '</table>';
		echo '</div>';

	}

	function build_filter( $name, array $values ) {

		usort( $values, 'strcasecmp' );

		$out = '<select id="qm-filter-' . esc_attr( $name ) . '" class="qm-filter" data-filter="' . esc_attr( $name ) . '">';
		$out .= '<option value="">' . __( 'All', 'query-monitor' ) . '</option>'; # @TODO _x()?

		foreach ( $values as $value )
			$out .= '<option value="' . esc_attr( $value ) . '">' . esc_html( $value ) . '</option>';

		$out .= '</select>';

		return $out;

	}

	function output_query_row( array $row, array $cols = array() ) {

		$cols = (array) $cols;

		if ( empty( $cols ) )
			$cols = array( 'sql', 'caller', 'component', 'result', 'time' );

		$cols = array_flip( $cols );

		if ( null === $row['component'] )
			unset( $cols['component'] );
		if ( null === $row['result'] )
			unset( $cols['result'] );

		$row_attr = array();
		$stime = number_format_i18n( $row['ltime'], 4 );
		$ltime = number_format_i18n( $row['ltime'], 10 );
		$td = $this->is_expensive( $row ) ? ' qm-expensive' : '';

		if ( 'SELECT' != $row['type'] )
			$row['sql'] = "<span class='qm-nonselectsql'>{$row['sql']}</span>";

		if ( is_wp_error( $row['result'] ) ) {
			$r = $row['result']->get_error_message( 'qmdb' );
			$result = "<td valign='top' class='qm-row-result qm-row-error'>{$r}</td>\n";
			$row_attr['class'] = 'qm-warn';
		} else {
			$result = "<td valign='top' class='qm-row-result'>{$row['result']}</td>\n";
		}

		if ( isset( $cols['sql'] ) )
			$row_attr['data-qm-type'] = $row['type'];
		if ( isset( $cols['component'] ) )
			$row_attr['data-qm-component'] = $row['component'];
		if ( isset( $cols['caller'] ) )
			$row_attr['data-qm-caller'] = $row['func'];
		if ( isset( $cols['time'] ) )
			$row_attr['data-qm-time'] = $row['ltime'];

		$funcs = esc_attr( $row['funcs'] );
		$attr = '';

		foreach ( $row_attr as $a => $v )
			$attr .= ' ' . $a . '="' . esc_attr( $v ) . '"';

		echo "<tr{$attr}>";

		if ( isset( $cols['sql'] ) )
			echo "<td valign='top' class='qm-row-sql qm-ltr qm-sql'>{$row['sql']}</td>";

		if ( isset( $cols['caller'] ) )
			echo "<td valign='top' class='qm-row-caller qm-ltr' title='{$funcs}'>{$row['func']}</td>";

		if ( isset( $cols['component'] ) )
			echo "<td valign='top' class='qm-row-component'>{$row['component']}</td>\n";

		if ( isset( $cols['result'] ) )
			echo $result;

		if ( isset( $cols['time'] ) )
			echo "<td valign='top' title='{$ltime}' class='qm-row-time{$td}'>{$stime}</td>\n";

		echo "</tr>";

	}

}

function register_qm_db_queries( array $qm ) {
	$qm['db_queries'] = new QM_Component_DB_Queries;
	return $qm;
}

add_filter( 'query_monitor_components', 'register_qm_db_queries', 20 );

?>
