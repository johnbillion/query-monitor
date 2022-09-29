<?php declare(strict_types = 1);
/**
 * Mock 'Debug Bar' plugin class.
 *
 * @package query-monitor
 */

class Debug_Bar {
	/**
	 * @var Debug_Bar_Panel[]
	 */
	public $panels = array();

	public function __construct() {
		add_action( 'wp_head', array( $this, 'ensure_ajaxurl' ), 1 );

		$this->enqueue();
		$this->init_panels();
	}

	/**
	 * @return void
	 */
	public function enqueue() {
		// phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
		wp_register_style( 'debug-bar', false, array(
			'query-monitor',
		) );
		// phpcs:ignore WordPress.WP.EnqueuedResourceParameters.MissingVersion
		wp_register_script( 'debug-bar', false, array(
			'query-monitor',
		) );

		/**
		 * Fires after scripts have been enqueued. This mimics the same action fired in the Debug Bar plugin.
		 *
		 * @since 2.7.0
		 */
		do_action( 'debug_bar_enqueue_scripts' );
	}

	/**
	 * @return void
	 */
	public function init_panels() {
		/**
		 * Filters the debug bar panel list. This mimics the same filter called in the Debug Bar plugin.
		 *
		 * @since 2.7.0
		 *
		 * @param Debug_Bar_Panel[] $panels Array of Debug Bar panel instances.
		 */
		$this->panels = apply_filters( 'debug_bar_panels', array() );
	}

	/**
	 * @return void
	 */
	public function ensure_ajaxurl() {
		$dispatcher = QM_Dispatchers::get( 'html' );

		if ( $this->panels && $dispatcher && $dispatcher::user_can_view() ) {
			?>
			<script type="text/javascript">
			var ajaxurl = '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>';
			</script>
			<?php
		}
	}

	/**
	 * @return void
	 */
	public function Debug_Bar() {
		self::__construct();
	}

}
