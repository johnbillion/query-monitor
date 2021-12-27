<?php
/**
 * Plugin activation handler.
 *
 * @package query-monitor
 */

class QM_Activation extends QM_Plugin {

	/**
	 * @param string $file
	 */
	protected function __construct( $file ) {

		# PHP version handling
		if ( ! QM_PHP::version_met() ) {
			add_action( 'all_admin_notices', array( $this, 'php_notice' ) );
			return;
		}

		# Filters
		add_filter( 'pre_update_option_active_plugins', array( $this, 'filter_active_plugins' ) );
		add_filter( 'pre_update_site_option_active_sitewide_plugins', array( $this, 'filter_active_sitewide_plugins' ) );

		# Activation and deactivation
		register_activation_hook( $file, array( $this, 'activate' ) );
		register_deactivation_hook( $file, array( $this, 'deactivate' ) );

		# Parent setup:
		parent::__construct( $file );

	}

	/**
	 * @param bool $sitewide
	 * @return void
	 */
	public function activate( $sitewide = false ) {
		$db = WP_CONTENT_DIR . '/db.php';
		$create_symlink = defined( 'QM_DB_SYMLINK' ) ? QM_DB_SYMLINK : true;

		if ( $create_symlink && ! file_exists( $db ) && function_exists( 'symlink' ) ) {
			@symlink( $this->plugin_path( 'wp-content/db.php' ), $db ); // phpcs:ignore
		}

		if ( $sitewide ) {
			update_site_option( 'active_sitewide_plugins', get_site_option( 'active_sitewide_plugins' ) );
		} else {
			update_option( 'active_plugins', get_option( 'active_plugins' ) );
		}

	}

	/**
	 * @return void
	 */
	public function deactivate() {
		$admins = QM_Util::get_admins();

		// Remove legacy capability handling:
		if ( $admins ) {
			$admins->remove_cap( 'view_query_monitor' );
		}

		# Only delete db.php if it belongs to Query Monitor
		if ( file_exists( WP_CONTENT_DIR . '/db.php' ) && class_exists( 'QM_DB' ) ) {
			unlink( WP_CONTENT_DIR . '/db.php' ); // phpcs:ignore
		}

	}

	/**
	 * @param array<int, string> $plugins
	 * @return array<int, string>
	 */
	public function filter_active_plugins( $plugins ) {

		// this needs to run on the cli too

		if ( empty( $plugins ) ) {
			return $plugins;
		}

		$f = preg_quote( basename( $this->plugin_base() ), '/' );

		return array_merge(
			preg_grep( '/' . $f . '$/', $plugins ),
			preg_grep( '/' . $f . '$/', $plugins, PREG_GREP_INVERT )
		);

	}

	/**
	 * @param array<string, int> $plugins
	 * @return array<string, int>
	 */
	public function filter_active_sitewide_plugins( $plugins ) {

		if ( empty( $plugins ) ) {
			return $plugins;
		}

		$f = $this->plugin_base();

		if ( isset( $plugins[ $f ] ) ) {

			unset( $plugins[ $f ] );

			return array_merge( array(
				$f => time(),
			), $plugins );

		} else {
			return $plugins;
		}

	}

	/**
	 * @return void
	 */
	public function php_notice() {
		?>
		<div id="qm_php_notice" class="notice notice-error">
			<p>
				<?php
				echo esc_html( sprintf(
					/* Translators: 1: Minimum required PHP version, 2: Current PHP version. */
					__( 'The Query Monitor plugin requires PHP version %1$s or higher. This site is running version %2$s.', 'query-monitor' ),
					QM_PHP::$minimum_version,
					PHP_VERSION
				) );
				?>
			</p>
		</div>
		<?php
	}

	/**
	 * @param string $file
	 * @return self
	 */
	public static function init( $file = null ) {

		static $instance = null;

		if ( ! $instance ) {
			$instance = new QM_Activation( $file );
		}

		return $instance;

	}

}
