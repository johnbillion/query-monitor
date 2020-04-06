<?php
/**
 * Plugin activation handler.
 *
 * @package query-monitor
 */

class QM_Activation extends QM_Plugin {

	protected function __construct( $file ) {

		# PHP version handling
		if ( ! self::php_version_met() ) {
			add_action( 'all_admin_notices', array( $this, 'php_notice' ) );
			return;
		}

		# Filters
		add_filter( 'pre_update_option_active_plugins',               array( $this, 'filter_active_plugins' ) );
		add_filter( 'pre_update_site_option_active_sitewide_plugins', array( $this, 'filter_active_sitewide_plugins' ) );

		# Activation and deactivation
		register_activation_hook( $file, array( $this, 'activate' ) );
		register_deactivation_hook( $file, array( $this, 'deactivate' ) );

		# Parent setup:
		parent::__construct( $file );

	}

	public function activate( $sitewide = false ) {
		$db = WP_CONTENT_DIR . '/db.php';

		if ( ! file_exists( $db ) && function_exists( 'symlink' ) ) {
			@symlink( $this->plugin_path( 'wp-content/db.php' ), $db ); // @codingStandardsIgnoreLine
		}

		if ( $sitewide ) {
			update_site_option( 'active_sitewide_plugins', get_site_option( 'active_sitewide_plugins' ) );
		} else {
			update_option( 'active_plugins', get_option( 'active_plugins' ) );
		}

	}

	public function deactivate() {
		$admins = QM_Util::get_admins();

		// Remove legacy capability handling:
		if ( $admins ) {
			$admins->remove_cap( 'view_query_monitor' );
		}

		# Only delete db.php if it belongs to Query Monitor
		if ( file_exists( WP_CONTENT_DIR . '/db.php' ) && class_exists( 'QM_DB' ) ) {
			unlink( WP_CONTENT_DIR . '/db.php' ); // @codingStandardsIgnoreLine
		}

	}

	public function filter_active_plugins( $plugins ) {

		// this needs to run on the cli too

		if ( empty( $plugins ) ) {
			return $plugins;
		}

		$f = preg_quote( basename( $this->plugin_base() ) );

		return array_merge(
			preg_grep( '/' . $f . '$/', $plugins ),
			preg_grep( '/' . $f . '$/', $plugins, PREG_GREP_INVERT )
		);

	}

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

	public function php_notice() {
		?>
		<div id="qm_php_notice" class="error">
			<p>
				<span class="dashicons dashicons-warning" style="color:#dd3232" aria-hidden="true"></span>
				<?php
				echo esc_html( sprintf(
					/* Translators: 1: Minimum required PHP version, 2: Current PHP version. */
					__( 'The Query Monitor plugin requires PHP version %1$s or higher. This site is running version %2$s.', 'query-monitor' ),
					self::$minimum_php_version,
					PHP_VERSION
				) );
				?>
			</p>
		</div>
		<?php
	}

	public static function init( $file = null ) {

		static $instance = null;

		if ( ! $instance ) {
			$instance = new QM_Activation( $file );
		}

		return $instance;

	}

}
