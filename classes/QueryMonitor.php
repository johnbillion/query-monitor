<?php declare(strict_types = 1);
/**
 * The main Query Monitor plugin class.
 *
 * @package query-monitor
 */

class QueryMonitor extends QM_Plugin {

	/**
	 * @return void
	 */
	public function set_up() {

		# Actions
		add_action( 'plugins_loaded', array( $this, 'action_plugins_loaded' ) );
		add_action( 'init', array( $this, 'action_init' ) );
		add_action( 'members_register_caps', array( $this, 'action_register_members_caps' ) );
		add_action( 'members_register_cap_groups', array( $this, 'action_register_members_groups' ) );
		add_action( 'qm/cease', array( $this, 'action_cease' ) );

		# Filters
		add_filter( 'user_has_cap', array( $this, 'filter_user_has_cap' ), 10, 4 );
		add_filter( 'ure_built_in_wp_caps', array( $this, 'filter_ure_caps' ) );
		add_filter( 'ure_capabilities_groups_tree', array( $this, 'filter_ure_groups' ) );
		add_filter( 'network_admin_plugin_action_links_query-monitor/query-monitor.php', array( $this, 'filter_plugin_action_links' ) );
		add_filter( 'plugin_action_links_query-monitor/query-monitor.php', array( $this, 'filter_plugin_action_links' ) );
		add_filter( 'plugin_row_meta', array( $this, 'filter_plugin_row_meta' ), 10, 2 );

		# Load and register built-in collectors:
		$collectors = array();
		$files = glob( $this->plugin_path( 'collectors/*.php' ) );
		if ( $files ) {
			foreach ( $files as $file ) {
				$key = basename( $file, '.php' );
				$collectors[ $key ] = $file;
			}
		}

		/**
		 * Allow filtering of built-in collector files.
		 *
		 * @since 2.14.0
		 *
		 * @param array<string, string> $collectors Array of file paths to be loaded, keyed by the base
		 *                                          name of the file.
		 */
		foreach ( apply_filters( 'qm/built-in-collectors', $collectors ) as $file ) {
			include_once $file;
		}

	}

	/**
	 * @param array<string, string> $actions
	 * @return array<string, string>
	 */
	public function filter_plugin_action_links( array $actions ) {
		return array_merge( array(
			'settings' => '<a href="#qm-settings">' . esc_html__( 'Settings', 'query-monitor' ) . '</a>',
			'add-ons' => '<a href="https://github.com/johnbillion/query-monitor/wiki/Query-Monitor-Add-on-Plugins">' . esc_html__( 'Add-ons', 'query-monitor' ) . '</a>',
		), $actions );
	}

	/**
	 * Filters the array of row meta for each plugin in the Plugins list table.
	 *
	 * @param array<int, string> $plugin_meta An array of the plugin's metadata.
	 * @param string             $plugin_file Path to the plugin file relative to the plugins directory.
	 * @return array<int, string> Updated array of the plugin's metadata.
	 */
	public function filter_plugin_row_meta( array $plugin_meta, $plugin_file ) {
		if ( 'query-monitor/query-monitor.php' !== $plugin_file ) {
			return $plugin_meta;
		}

		$plugin_meta[] = sprintf(
			'<a href="%1$s"><span class="dashicons dashicons-star-filled" aria-hidden="true" style="font-size:14px;line-height:1.3"></span>%2$s</a>',
			'https://github.com/sponsors/johnbillion',
			esc_html_x( 'Sponsor', 'verb', 'query-monitor' )
		);

		return $plugin_meta;
	}

	/**
	 * Filter a user's capabilities so they can be altered at runtime.
	 *
	 * This is used to:
	 *  - Grant the 'view_query_monitor' capability to the user if they have the ability to manage options.
	 *
	 * This does not get called for Super Admins.
	 *
	 * @param array<string, bool> $user_caps     Array of key/value pairs where keys represent a capability name and boolean values
	 *                                           represent whether the user has that capability.
	 * @param array<int, string>  $required_caps Required primitive capabilities for the requested capability.
	 * @param mixed[]             $args {
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
	 * @param WP_User  $user          Concerned user object.
	 * @return array<string, bool> Concerned user's capabilities.
	 */
	public function filter_user_has_cap( array $user_caps, array $required_caps, array $args, WP_User $user ) {
		if ( 'view_query_monitor' !== $args[0] ) {
			return $user_caps;
		}

		if ( array_key_exists( 'view_query_monitor', $user_caps ) ) {
			return $user_caps;
		}

		if ( ! is_multisite() && user_can( $args[1], 'manage_options' ) ) {
			$user_caps['view_query_monitor'] = true;
		}

		return $user_caps;
	}

	/**
	 * @return void
	 */
	public function action_plugins_loaded() {
		// Hide QM itself from output by default:
		if ( ! defined( 'QM_HIDE_SELF' ) ) {
			define( 'QM_HIDE_SELF', true );
		}

		/**
		 * Filters the collectors that are being added.
		 *
		 * @since 2.11.2
		 *
		 * @param array<int, QM_Collector> $collectors Array of collector instances.
		 * @param QueryMonitor             $instance   QueryMonitor instance.
		 */
		foreach ( apply_filters( 'qm/collectors', array(), $this ) as $collector ) {
			QM_Collectors::add( $collector );
		}

		# Load dispatchers:
		foreach ( (array) glob( $this->plugin_path( 'dispatchers/*.php' ) ) as $file ) {
			include_once $file;
		}

		/**
		 * Filters the dispatchers that are being added.
		 *
		 * @since 2.11.2
		 *
		 * @param array<int, QM_Dispatcher> $dispatchers Array of dispatcher instances.
		 * @param QueryMonitor              $instance    QueryMonitor instance.
		 */
		foreach ( apply_filters( 'qm/dispatchers', array(), $this ) as $dispatcher ) {
			QM_Dispatchers::add( $dispatcher );
		}

	}

	/**
	 * @return void
	 */
	public function action_init() {
		load_plugin_textdomain( 'query-monitor', false, dirname( $this->plugin_base() ) . '/languages' );
	}

	/**
	 * @return void
	 */
	public static function symlink_warning() {
		$db = WP_CONTENT_DIR . '/db.php';
		trigger_error( sprintf(
			/* translators: %s: Symlink file location */
			esc_html__( 'The symlink at %s is no longer pointing to the correct location. Please remove the symlink, then deactivate and reactivate Query Monitor.', 'query-monitor' ),
			'<code>' . esc_html( $db ) . '</code>'
		), E_USER_WARNING );
	}

	/**
	 * Registers the Query Monitor user capability group for the Members plugin.
	 *
	 * @link https://wordpress.org/plugins/members/
	 *
	 * @return void
	 */
	public function action_register_members_groups() {
		members_register_cap_group( 'query_monitor', array(
			'label' => __( 'Query Monitor', 'query-monitor' ),
			'caps' => array(
				'view_query_monitor',
			),
			'icon' => 'dashicons-admin-tools',
			'priority' => 30,
		) );
	}

	/**
	 * Registers the View Query Monitor user capability for the Members plugin.
	 *
	 * @link https://wordpress.org/plugins/members/
	 *
	 * @return void
	 */
	public function action_register_members_caps() {
		members_register_cap( 'view_query_monitor', array(
			'label' => _x( 'View Query Monitor', 'Human readable label for the user capability required to view Query Monitor.', 'query-monitor' ),
			'group' => 'query_monitor',
		) );
	}

	/**
	 * Registers the Query Monitor user capability group for the User Role Editor plugin.
	 *
	 * @link https://wordpress.org/plugins/user-role-editor/
	 *
	 * @param array<string, array<string, mixed>> $groups Array of existing groups.
	 * @return array<string, array<string, mixed>> Updated array of groups.
	 */
	public function filter_ure_groups( array $groups ) {
		$groups['query_monitor'] = array(
			'caption' => esc_html__( 'Query Monitor', 'query-monitor' ),
			'parent' => 'custom',
			'level' => 2,
		);

		return $groups;
	}

	/**
	 * Registers the View Query Monitor user capability for the User Role Editor plugin.
	 *
	 * @link https://wordpress.org/plugins/user-role-editor/
	 *
	 * @param array<string, array<int, string>> $caps Array of existing capabilities.
	 * @return array<string, array<int, string>> Updated array of capabilities.
	 */
	public function filter_ure_caps( array $caps ) {
		$caps['view_query_monitor'] = array(
			'custom',
			'query_monitor',
		);

		return $caps;
	}

	/**
	 * @return void
	 */
	public function action_cease() {
		// iterate collectors, call tear_down
		// discard all collected data
		QM_Collectors::cease();

		// remove dispatchers or prevent them from doing anything
		QM_Dispatchers::cease();
	}

	/**
	 * @param string $file
	 * @return self
	 */
	public static function init( $file = null ) {

		static $instance = null;

		if ( ! $instance ) {
			$instance = new QueryMonitor( $file );
		}

		return $instance;

	}

}
