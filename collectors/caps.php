<?php
/**
 * User capability check collector.
 *
 * @package query-monitor
 */

class QM_Collector_Caps extends QM_Collector {

	public $id = 'caps';

	public function __construct() {
		parent::__construct();

		if ( ! self::enabled() ) {
			return;
		}

		add_filter( 'user_has_cap', array( $this, 'filter_user_has_cap' ), 9999, 3 );
		add_filter( 'map_meta_cap', array( $this, 'filter_map_meta_cap' ), 9999, 4 );
	}

	public static function enabled() {
		return ( defined( 'QM_ENABLE_CAPS_PANEL' ) && QM_ENABLE_CAPS_PANEL );
	}

	public function get_concerned_actions() {
		return array(
			'wp_roles_init',
		);
	}

	public function get_concerned_filters() {
		return array(
			'map_meta_cap',
			'role_has_cap',
			'user_has_cap',
		);
	}

	public function get_concerned_options() {
		$blog_prefix = $GLOBALS['wpdb']->get_blog_prefix();

		return array(
			"{$blog_prefix}user_roles",
		);
	}

	public function get_concerned_constants() {
		return array(
			'ALLOW_UNFILTERED_UPLOADS',
			'DISALLOW_FILE_EDIT',
			'DISALLOW_UNFILTERED_HTML',
		);
	}

	public function tear_down() {
		remove_filter( 'user_has_cap', array( $this, 'filter_user_has_cap' ), 9999 );
		remove_filter( 'map_meta_cap', array( $this, 'filter_map_meta_cap' ), 9999 );
	}

	/**
	 * Logs user capability checks.
	 *
	 * This does not get called for Super Admins. See filter_map_meta_cap() below.
	 *
	 * @param bool[]   $user_caps     Concerned user's capabilities.
	 * @param string[] $required_caps Required primitive capabilities for the requested capability.
	 * @param array    $args {
	 *     Arguments that accompany the requested capability check.
	 *
	 *     @type string    $0 Requested capability.
	 *     @type int       $1 Concerned user ID.
	 *     @type mixed  ...$2 Optional second and further parameters.
	 * }
	 * @return bool[] Concerned user's capabilities.
	 */
	public function filter_user_has_cap( array $user_caps, array $caps, array $args ) {
		$trace  = new QM_Backtrace();
		$result = true;

		foreach ( $caps as $cap ) {
			if ( empty( $user_caps[ $cap ] ) ) {
				$result = false;
				break;
			}
		}

		$this->data['caps'][] = array(
			'args'   => $args,
			'trace'  => $trace,
			'result' => $result,
		);

		return $user_caps;
	}

	/**
	 * Logs user capability checks for Super Admins on Multisite.
	 *
	 * This is needed because the `user_has_cap` filter doesn't fire for Super Admins.
	 *
	 * @param string[] $required_caps Required primitive capabilities for the requested capability.
	 * @param string   $cap           Capability or meta capability being checked.
	 * @param int      $user_id       Concerned user ID.
	 * @param array    $args {
	 *     Arguments that accompany the requested capability check.
	 *
	 *     @type mixed ...$0 Optional second and further parameters.
	 * }
	 * @return string[] Required capabilities for the requested action.
	 */
	public function filter_map_meta_cap( array $required_caps, $cap, $user_id, array $args ) {
		if ( ! is_multisite() ) {
			return $required_caps;
		}

		if ( ! is_super_admin( $user_id ) ) {
			return $required_caps;
		}

		$trace  = new QM_Backtrace();
		$result = ( ! in_array( 'do_not_allow', $required_caps, true ) );

		array_unshift( $args, $user_id );
		array_unshift( $args, $cap );

		$this->data['caps'][] = array(
			'args'   => $args,
			'trace'  => $trace,
			'result' => $result,
		);

		return $required_caps;
	}

	public function process() {
		if ( empty( $this->data['caps'] ) ) {
			return;
		}

		$all_parts  = array();
		$all_users  = array();
		$components = array();

		$this->data['caps'] = array_values( array_filter( $this->data['caps'], array( $this, 'filter_remove_noise' ) ) );

		if ( self::hide_qm() ) {
			$this->data['caps'] = array_values( array_filter( $this->data['caps'], array( $this, 'filter_remove_qm' ) ) );
		}

		foreach ( $this->data['caps'] as $i => $cap ) {
			$name = $cap['args'][0];

			if ( ! is_string( $name ) ) {
				$name = '';
			}

			$trace          = $cap['trace']->get_trace();
			$filtered_trace = $cap['trace']->get_display_trace();

			$last = end( $filtered_trace );
			if ( isset( $last['function'] ) && 'map_meta_cap' === $last['function'] ) {
				array_shift( $filtered_trace ); // remove the map_meta_cap() call
			}

			array_shift( $filtered_trace ); // remove the WP_User->has_cap() call
			array_shift( $filtered_trace ); // remove the *_user_can() call

			if ( ! count( $filtered_trace ) ) {
				$responsible_name = QM_Util::standard_dir( $trace[1]['file'], '' ) . ':' . $trace[1]['line'];

				$responsible_item                 = $trace[1];
				$responsible_item['display']      = $responsible_name;
				$responsible_item['calling_file'] = $trace[1]['file'];
				$responsible_item['calling_line'] = $trace[1]['line'];
				array_unshift( $filtered_trace, $responsible_item );
			}

			$component = $cap['trace']->get_component();

			$this->data['caps'][ $i ]['filtered_trace'] = $filtered_trace;
			$this->data['caps'][ $i ]['component']      = $component;

			$parts                             = array_values( array_filter( preg_split( '#[_/-]#', $name ) ) );
			$this->data['caps'][ $i ]['parts'] = $parts;
			$this->data['caps'][ $i ]['name']  = $name;
			$this->data['caps'][ $i ]['user']  = $cap['args'][1];
			$this->data['caps'][ $i ]['args']  = array_slice( $cap['args'], 2 );
			$all_parts                         = array_merge( $all_parts, $parts );
			$all_users[]                       = $cap['args'][1];
			$components[ $component->name ]    = $component->name;

			unset( $this->data['caps'][ $i ]['trace'] );
		}

		$this->data['parts']      = array_values( array_unique( array_filter( $all_parts ) ) );
		$this->data['users']      = array_values( array_unique( array_filter( $all_users ) ) );
		$this->data['components'] = $components;
	}

	public function filter_remove_noise( array $cap ) {
		$trace = $cap['trace']->get_trace();

		$exclude_files     = array(
			ABSPATH . 'wp-admin/menu.php',
			ABSPATH . 'wp-admin/includes/menu.php',
		);
		$exclude_functions = array(
			'_wp_menu_output',
			'wp_admin_bar_render',
		);

		foreach ( $trace as $item ) {
			if ( isset( $item['file'] ) && in_array( $item['file'], $exclude_files, true ) ) {
				return false;
			}
			if ( isset( $item['function'] ) && in_array( $item['function'], $exclude_functions, true ) ) {
				return false;
			}
		}

		return true;
	}

}

function register_qm_collector_caps( array $collectors, QueryMonitor $qm ) {
	$collectors['caps'] = new QM_Collector_Caps();
	return $collectors;
}

add_filter( 'qm/collectors', 'register_qm_collector_caps', 20, 2 );
