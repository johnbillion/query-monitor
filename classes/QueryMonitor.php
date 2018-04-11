<?php
/**
 * The main Query Monitor plugin class.
 *
 * @package query-monitor
 */

class QueryMonitor extends QM_Plugin {

	protected function __construct( $file ) {

		# Actions
		add_action( 'plugins_loaded', array( $this, 'action_plugins_loaded' ) );
		add_action( 'init',           array( $this, 'action_init' ) );

		# Filters
		add_filter( 'user_has_cap',   array( $this, 'filter_user_has_cap' ), 10, 3 );

		# Parent setup:
		parent::__construct( $file );

		# Load and register built-in collectors:
		$collectors = array();
		foreach ( glob( $this->plugin_path( 'collectors/*.php' ) ) as $file ) {
			$key = basename( $file, '.php' );
			$collectors[ $key ] = $file;
		}

		foreach ( apply_filters( 'qm/built-in-collectors', $collectors ) as $file ) {
			include $file;
		}

	}

	/**
	 * Filter a user's capabilities so they can be altered at runtime.
	 *
	 * This is used to:
	 *  - Grant the 'view_query_monitor' capability to the user if they have the ability to manage options.
	 *
	 * This does not get called for Super Admins.
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
	public function filter_user_has_cap( array $user_caps, array $required_caps, array $args ) {
		if ( 'view_query_monitor' !== $args[0] ) {
			return $user_caps;
		}

		if ( ! is_multisite() && user_can( $args[1], 'manage_options' ) ) {
			$user_caps['view_query_monitor'] = true;
		}

		return $user_caps;
	}

	public function action_plugins_loaded() {

		# Register additional collectors:
		foreach ( apply_filters( 'qm/collectors', array(), $this ) as $collector ) {
			QM_Collectors::add( $collector );
		}

		# Load dispatchers:
		foreach ( glob( $this->plugin_path( 'dispatchers/*.php' ) ) as $file ) {
			include $file;
		}

		# Register built-in and additional dispatchers:
		foreach ( apply_filters( 'qm/dispatchers', array(), $this ) as $dispatcher ) {
			QM_Dispatchers::add( $dispatcher );
		}

	}

	public function action_init() {
		load_plugin_textdomain( 'query-monitor', false, dirname( $this->plugin_base() ) . '/languages' );
	}

	public static function symlink_warning() {
		$db = WP_CONTENT_DIR . '/db.php';
		trigger_error( sprintf(
			/* translators: %s: Symlink file location */
			esc_html__( 'The symlink at %s is no longer pointing to the correct location. Please remove the symlink, then deactivate and reactivate Query Monitor.', 'query-monitor' ),
			'<code>' . esc_html( $db ) . '</code>'
		), E_USER_WARNING );
	}

	public static function init( $file = null ) {

		static $instance = null;

		if ( ! $instance ) {
			$instance = new QueryMonitor( $file );
		}

		return $instance;

	}

}
