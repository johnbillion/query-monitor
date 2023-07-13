<?php declare(strict_types = 1);
/**
 * User capability check collector.
 *
 * @package query-monitor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @extends QM_DataCollector<QM_Data_Caps>
 * @phpstan-type CapCheck array{
 *   args: list<mixed>,
 *   filtered_trace: list<array<string, mixed>>,
 *   component: QM_Component,
 *   result: bool,
 * }
 */
class QM_Collector_Caps extends QM_DataCollector {

	public $id = 'caps';

	/**
	 * @var array<int, array<string, mixed>>
	 * @phpstan-var list<CapCheck>
	 */
	private $cap_checks = array();

	public function get_storage(): QM_Data {
		return new QM_Data_Caps();
	}

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
	 * @param array<string, bool> $user_caps Concerned user's capabilities.
	 * @param array<int, string>  $caps      Required primitive capabilities for the requested capability.
	 * @param array<int, mixed>   $args {
	 *     Arguments that accompany the requested capability check.
	 *
	 *     @type string    $0 Requested capability.
	 *     @type int       $1 Concerned user ID.
	 *     @type mixed  ...$2 Optional second and further parameters.
	 * }
	 * @phpstan-param array{
	 *   0: string,
	 *   1: int,
	 * } $args
	 * @return array<string, bool> Concerned user's capabilities.
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

		$this->cap_checks[] = array(
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
	 * @param array<int, string> $required_caps Required primitive capabilities for the requested capability.
	 * @param string             $cap           Capability or meta capability being checked.
	 * @param int                $user_id       Concerned user ID.
	 * @param mixed[]            $args {
	 *     Arguments that accompany the requested capability check.
	 *
	 *     @type mixed ...$0 Optional second and further parameters.
	 * }
	 * @return array<int, string> Required capabilities for the requested action.
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

		$this->cap_checks[] = array(
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
		if ( empty( $this->cap_checks ) ) {
			return;
		}

		$all_parts = array();
		$all_users = array();
		$components = array();
		$this->data->caps = array();

		$this->cap_checks = array_values( array_filter( $this->cap_checks, array( $this, 'filter_remove_noise' ) ) );

		if ( self::hide_qm() ) {
			$this->cap_checks = array_values( array_filter( $this->cap_checks, array( $this, 'filter_remove_qm' ) ) );
		}

		foreach ( $this->cap_checks as $cap ) {
			$name = $cap['args'][0];

			if ( ! is_string( $name ) ) {
				$name = '';
			}

			$component = $cap['component'];
			$parts = array();
			$pieces = preg_split( '#[_/-]#', $name );

			if ( is_array( $pieces ) ) {
				$parts = array_values( array_filter( $pieces ) );
			}

			$capability = array_shift( $cap['args'] );
			$user_id = array_shift( $cap['args'] );

			$cap['parts'] = $parts;
			$cap['name'] = $name;
			$cap['user'] = $user_id;

			$this->data->caps[] = $cap;

			$all_parts = array_merge( $all_parts, $parts );
			$all_users[] = (int) $user_id;
			$components[ $component->name ] = $component->name;
		}

		$this->data->parts = array_values( array_unique( array_filter( $all_parts ) ) );
		$this->data->users = array_values( array_unique( array_filter( $all_users ) ) );
		$this->data->components = $components;
	}

	/**
	 * @param array<string, mixed> $cap
	 * @phpstan-param CapCheck $cap
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
