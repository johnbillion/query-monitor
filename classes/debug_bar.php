<?php
/**
 * Mock 'Debug Bar' plugin class.
 *
 * @package query-monitor
 */

class Debug_Bar {
	public $panels = array();

	public function __construct() {
		add_action( 'wp_head', array( $this, 'ensure_ajaxurl' ), 1 );

		$this->enqueue();
		$this->init_panels();
	}

	public function enqueue() {
		wp_register_style( 'debug-bar', false, array(
			'query-monitor',
		) );
		wp_register_script( 'debug-bar', false, array(
			'query-monitor',
		) );

		do_action( 'debug_bar_enqueue_scripts' );
	}

	public function init_panels() {
		require_once 'debug_bar_panel.php';

		$this->panels = apply_filters( 'debug_bar_panels', array() );
	}

	public function ensure_ajaxurl() {
		?>
		<script type="text/javascript">
		var ajaxurl = '<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>';
		</script>
		<?php
	}

	public function Debug_Bar() {
		Debug_Bar::__construct();
	}

}
