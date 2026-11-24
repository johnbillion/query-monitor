<?php declare(strict_types = 1);

namespace QM\Tests;

class ActivationTest extends Test {
	private string $dropin;

	private string $plugin;

	/**
	 * The existing drop-in is shared with the acceptance tests so it gets restored afterwards.
	 */
	private ?string $original_link = null;

	private ?string $original_contents = null;

	#[\Override]
	public function set_up(): void {
		parent::set_up();

		$this->dropin = WP_CONTENT_DIR . '/db.php';
		$this->plugin = plugin_basename( dirname( __DIR__, 2 ) . '/query-monitor.php' );

		if ( is_link( $this->dropin ) ) {
			$this->original_link = (string) readlink( $this->dropin );
		} elseif ( file_exists( $this->dropin ) ) {
			$this->original_contents = (string) file_get_contents( $this->dropin );
		}

		$this->remove_dropin();
	}

	#[\Override]
	public function tear_down(): void {
		$this->remove_dropin();

		if ( null !== $this->original_link ) {
			symlink( $this->original_link, $this->dropin );
		} elseif ( null !== $this->original_contents ) {
			file_put_contents( $this->dropin, $this->original_contents );
		}

		parent::tear_down();
	}

	public function testDropinIsDeletedOnDeactivation(): void {
		symlink( dirname( __DIR__, 2 ) . '/wp-content/db.php', $this->dropin );

		if ( is_multisite() ) {
			update_site_option( 'active_sitewide_plugins', array(
				$this->plugin => time(),
			) );
		} else {
			update_option( 'active_plugins', array( $this->plugin ) );
		}

		deactivate_plugins( $this->plugin, false, is_multisite() );

		self::assertFalse( is_link( $this->dropin ) );
		self::assertFileDoesNotExist( $this->dropin );
	}

	public function testDropinThatDoesNotBelongToQueryMonitorIsNotDeleted(): void {
		$contents = '<?php // Another drop-in';
		file_put_contents( $this->dropin, $contents );

		self::assertNull( activate_plugin( $this->plugin, '', is_multisite() ) );
		self::assertFalse( is_link( $this->dropin ) );

		deactivate_plugins( $this->plugin, false, is_multisite() );

		self::assertFileExists( $this->dropin );
		self::assertSame( $contents, file_get_contents( $this->dropin ) );
	}

	private function remove_dropin(): void {
		if ( is_link( $this->dropin ) || file_exists( $this->dropin ) ) {
			unlink( $this->dropin );
		}
	}
}
