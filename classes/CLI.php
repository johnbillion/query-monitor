<?php
/**
 * Plugin CLI command.
 *
 * @package query-monitor
 */

class QM_CLI extends QM_Plugin {

	protected function __construct( $file ) {

		# Register command
		WP_CLI::add_command( 'qm enable', array( $this, 'enable' ) );

		# Parent setup:
		parent::__construct( $file );

	}

	/**
	 * Enable QM by creating the symlink for db.php
	 */
	public function enable() {
		global $wpdb;
		if ( is_a( $wpdb, 'QM_DB' ) ) {
			WP_CLI::success( 'QM Extended query information is already enabled.' );
			return;
		}
		$drop_in = WP_CONTENT_DIR . '/db.php';
		if ( file_exists( $drop_in ) ) {
			WP_CLI::error( 'Unknown wp-content/db.php already exists.' );
		}

		if ( ! function_exists( 'symlink' ) ) {
			WP_CLI::error( 'The symlink function is not available.' );
		}

		if ( symlink( $this->plugin_path( 'wp-content/db.php' ), $drop_in ) ) {
			WP_CLI::success( 'Enabled QM Extended query information by creating wp-content/db.php symlink.' );
		} else {
			WP_CLI::error( 'Failed create wp-content/db.php symlink and enable QM Extended query information.' );
		}
	}

	public static function init( $file = null ) {

		static $instance = null;

		if ( ! $instance ) {
			$instance = new QM_CLI( $file );
		}

		return $instance;

	}

}
