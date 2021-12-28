<?php
/**
 * User capability check collector.
 *
 * @package query-monitor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class QM_Collector_Caps extends QM_Collector {

	public $id = 'caps';

	/**
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		if ( ! self::enabled() ) {
			return;
		}

		add_filter( 'user_has_cap', array( $this, 'filter_user_has_cap' ), 9999, 3 );
		add_filter( 'map_meta_cap', array( $this, 'filter_map_meta_cap' ), 9999, 4 );
	}

	/**
	 * @return void
	 */
	public function tear_down() {
		remove_filter( 'user_has_cap', array( $this, 'filter_user_has_cap' ), 9999 );
		remove_filter( 'map_meta_cap', array( $this, 'filter_map_meta_cap' ), 9999 );

		parent::tear_down();
	}

	/**
	 * @return bool
	 */
	public static function enabled() {
		return ( defined( 'QM_ENABLE_CAPS_PANEL' ) && QM_ENABLE_CAPS_PANEL );
	}

	/**
	 * @return array<int, string>
	 */
	public function get_concerned_actions() {
		return array(
			'wp_roles_init',
		);
	}

	/**
	 * @return array<int, string>
	 */
	public function get_concerned_filters() {
		return array(
			'map_meta_cap',
			'role_has_cap',
			'user_has_cap',
		);
	}

	/**
	 * @return array<int, string>
	 */
	public function get_concerned_options() {
		$blog_prefix = $GLOBALS['wpdb']->get_blog_prefix();

		return array(
			"{$blog_prefix}user_roles",
		);
	}

	/**
	 * @return array<int, string>
	 */
	public function get_concerned_constants() {
		return array(
			'ALLOW_UNFILTERED_UPLOADS',
			'DISALLOW_FILE_EDIT',
			'DISALLOW_UNFILTERED_HTML',
		);
	}

	/**
	 * Logs user capability checks.
	 *
	 * This does not get called for Super Admins. See filter_map_meta_cap() below.
	 *
	 * @param bool[]   $user_caps Concerned user's capabilities.
	 * @param string[] $caps      Required primitive capabilities for the requested capability.
	 * @param mixed[]  $args {
	 *     Arguments that accompany the requested capability check.
	 *
	 *     @type string    $0 Requested capability.
	 *     @type int       $1 Concerned user ID.
	 *     @type mixed  ...$2 Optional second and further parameters.
	 * }
	 * @return bool[] Concerned user's capabilities.
	 */
	public function filter_user_has_cap( array $user_caps, array $caps, array $args ) {
		$trace = new QM_Backtrace( array(
			'ignore_hook' => array(
				current_filter() => true,
			),
			'ignore_func' => array(
				'current_user_can' => true,
				'map_meta_cap' => true,
				'user_can' => true,
			),
			'ignore_method' => array(
				'WP_User' => array(
					'has_cap' => true,
				),
			),
		) );
		$result = true;

		foreach ( $caps as $cap ) {
			if ( empty( $user_caps[ $cap ] ) ) {
				$result = false;
				break;
			}
		}

		$this->data['caps'][] = array(
			'args' => $args,
			'filtered_trace' => $trace->get_filtered_trace(),
			'component' => $trace->get_component(),
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
	 * @param mixed[]  $args {
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

		$trace = new QM_Backtrace( array(
			'ignore_hook' => array(
				current_filter() => true,
			),
			'ignore_func' => array(
				'current_user_can' => true,
				'map_meta_cap' => true,
				'user_can' => true,
			),
			'ignore_method' => array(
				'WP_User' => array(
					'has_cap' => true,
				),
			),
		) );
		$result = ( ! in_array( 'do_not_allow', $required_caps, true ) );

		array_unshift( $args, $user_id );
		array_unshift( $args, $cap );

		$this->data['caps'][] = array(
			'args' => $args,
			'filtered_trace' => $trace->get_filtered_trace(),
			'component' => $trace->get_component(),
			'result' => $result,
		);

		return $required_caps;
	}

	/**
	 * @return void
	 */
	public function process() {
		if ( empty( $this->data['caps'] ) ) {
			return;
		}

		$all_parts = array();
		$all_users = array();
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

			$component = $cap['component'];

			$parts = array_values( array_filter( preg_split( '#[_/-]#', $name ) ) );
			$this->data['caps'][ $i ]['parts'] = $parts;
			$this->data['caps'][ $i ]['name'] = $name;
			$this->data['caps'][ $i ]['user'] = $cap['args'][1];
			$this->data['caps'][ $i ]['args'] = array_slice( $cap['args'], 2 );
			$all_parts = array_merge( $all_parts, $parts );
			$all_users[] = $cap['args'][1];
			$components[ $component->name ] = $component->name;
		}

		$this->data['parts'] = array_values( array_unique( array_filter( $all_parts ) ) );
		$this->data['users'] = array_values( array_unique( array_filter( $all_users ) ) );
		$this->data['components'] = $components;
	}

	/**
	 * @param array<string, mixed> $cap
	 * @return bool
	 */
	public function filter_remove_noise( array $cap ) {
		$trace = $cap['filtered_trace'];

		$exclude_files = array(
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

/**
 * @param array<string, QM_Collector> $collectors
 * @param QueryMonitor $qm
 * @return array<string, QM_Collector>
 */
function register_qm_collector_caps( array $collectors, QueryMonitor $qm ) {
	$collectors['caps'] = new QM_Collector_Caps();
	return $collectors;
}

add_filter( 'qm/collectors', 'register_qm_collector_caps', 20, 2 );
