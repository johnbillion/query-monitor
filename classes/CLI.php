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
		$db_file = $this->plugin_path( 'wp-content/db.php' );
		$target       = self::get_relative_path( $drop_in, $db_file );
		chdir( WP_CONTENT_DIR );
		// @codingStandardsIgnoreStart
		if ( symlink( $target, 'db.php' ) ) {
			// @codingStandardsIgnoreEnd
			WP_CLI::success( 'Enabled QM Extended query information by creating wp-content/db.php symlink.' );
		} else {
			WP_CLI::error( 'Failed create wp-content/db.php symlink and enable QM Extended query information.' );
		}
	}

	/**
	 * Get the relative path between two files
	 *
	 * @see http://stackoverflow.com/questions/2637945/getting-relative-path-from-absolute-path-in-php
	 */
	private static function get_relative_path( $from, $to ) {
		// some compatibility fixes for Windows paths
		$from = is_dir( $from ) ? rtrim( $from, '\/' ) . '/' : $from;
		$to   = is_dir( $to ) ? rtrim( $to, '\/' ) . '/' : $to;
		$from = str_replace( '\\', '/', $from );
		$to   = str_replace( '\\', '/', $to );

		$from     = explode( '/', $from );
		$to       = explode( '/', $to );
		$rel_path = $to;

		foreach ( $from as $depth => $dir ) {
			// find first non-matching dir
			if ( $dir === $to[ $depth ] ) {
				// ignore this directory
				array_shift( $rel_path );
			} else {
				// get number of remaining dirs to $from
				$remaining = count( $from ) - $depth;
				if ( $remaining > 1 ) {
					// add traversals up to first matching dir
					$pad_length = ( count( $rel_path ) + $remaining - 1 ) * -1;
					$rel_path   = array_pad( $rel_path, $pad_length, '..' );
					break;
				} else {
					$rel_path[0] = './' . $rel_path[0];
				}
			}
		}
		return implode( '/', $rel_path );
	}

	public static function init( $file = null ) {

		static $instance = null;

		if ( ! $instance ) {
			$instance = new QM_CLI( $file );
		}

		return $instance;

	}

}
