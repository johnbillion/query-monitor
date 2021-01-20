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
		$drop_in = WP_CONTENT_DIR . '/db.php';

		if ( file_exists( $drop_in ) ) {
			if ( false !== strpos( file_get_contents( $drop_in ), 'class QM_DB' ) ) {
				WP_CLI::success( "Query Monitor's wp-content/db.php is already in place" );
				exit( 0 );
			} else {
				WP_CLI::error( 'Unknown wp-content/db.php already is already in place' );
			}
		}

		if ( defined( 'QM_DB_SYMLINK' ) && ! QM_DB_SYMLINK ) {
			WP_CLI::warning( 'Creation of symlink prevented by QM_DB_SYMLINK constant.' );
			exit( 0 );
		}

		if ( ! function_exists( 'symlink' ) ) {
			WP_CLI::error( 'The symlink function is not available' );
		}

		if ( symlink( $this->plugin_path( 'wp-content/db.php' ), $drop_in ) ) {
			WP_CLI::success( 'wp-content/db.php symlink created' );
			exit( 0 );
		} else {
			WP_CLI::error( 'Failed to create wp-content/db.php symlink' );
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
