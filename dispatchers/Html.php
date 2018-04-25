<?php
/**
 * General HTML request dispatcher.
 *
 * @package query-monitor
 */

class QM_Dispatcher_Html extends QM_Dispatcher {

	public $id = 'html';
	public $did_footer = false;

	protected $outputters     = array();
	protected $admin_bar_menu = array();
	protected $panel_menu     = array();

	public function __construct( QM_Plugin $qm ) {

		add_action( 'admin_bar_menu',             array( $this, 'action_admin_bar_menu' ), 999 );
		add_action( 'wp_ajax_qm_auth_on',         array( $this, 'ajax_on' ) );
		add_action( 'wp_ajax_qm_auth_off',        array( $this, 'ajax_off' ) );
		add_action( 'wp_ajax_nopriv_qm_auth_off', array( $this, 'ajax_off' ) );

		add_action( 'shutdown',                   array( $this, 'dispatch' ), 0 );

		add_action( 'wp_footer',                  array( $this, 'action_footer' ) );
		add_action( 'admin_footer',               array( $this, 'action_footer' ) );
		add_action( 'login_footer',               array( $this, 'action_footer' ) );
		add_action( 'embed_footer',               array( $this, 'action_footer' ) );
		add_action( 'gp_footer',                  array( $this, 'action_footer' ) );

		parent::__construct( $qm );

	}

	public function action_footer() {
		$this->did_footer = true;
	}

	/**
	 * Helper function. Should the authentication cookie be secure?
	 *
	 * @return bool Should the authentication cookie be secure?
	 */
	public static function secure_cookie() {
		return ( is_ssl() and ( 'https' === parse_url( home_url(), PHP_URL_SCHEME ) ) );
	}

	public function ajax_on() {

		if ( ! current_user_can( 'view_query_monitor' ) or ! check_ajax_referer( 'qm-auth-on', 'nonce', false ) ) {
			wp_send_json_error( __( 'Could not set authentication cookie.', 'query-monitor' ) );
		}

		$expiration = time() + ( 2 * DAY_IN_SECONDS );
		$secure     = self::secure_cookie();
		$cookie     = wp_generate_auth_cookie( get_current_user_id(), $expiration, 'logged_in' );

		setcookie( QM_COOKIE, $cookie, $expiration, COOKIEPATH, COOKIE_DOMAIN, $secure, false );

		$text = __( 'Authentication cookie set. You can now view Query Monitor output while logged out or while logged in as a different user.', 'query-monitor' );

		wp_send_json_success( $text );

	}

	public function ajax_off() {

		if ( ! self::user_verified() or ! check_ajax_referer( 'qm-auth-off', 'nonce', false ) ) {
			wp_send_json_error( __( 'Could not clear authentication cookie.', 'query-monitor' ) );
		}

		$expiration = time() - 31536000;

		setcookie( QM_COOKIE, ' ', $expiration, COOKIEPATH, COOKIE_DOMAIN );

		$text = __( 'Authentication cookie cleared.', 'query-monitor' );

		wp_send_json_success( $text );

	}

	public function action_admin_bar_menu( WP_Admin_Bar $wp_admin_bar ) {

		if ( ! $this->user_can_view() ) {
			return;
		}

		$title = __( 'Query Monitor', 'query-monitor' );

		$wp_admin_bar->add_menu( array(
			'id'    => 'query-monitor',
			'title' => esc_html( $title ),
			'href'  => '#qm-overview',
		) );

		$wp_admin_bar->add_menu( array(
			'parent' => 'query-monitor',
			'id'     => 'query-monitor-placeholder',
			'title'  => esc_html( $title ),
			'href'   => '#qm-overview',
		) );

	}

	public function init() {

		if ( ! $this->user_can_view() ) {
			return;
		}

		if ( ! defined( 'DONOTCACHEPAGE' ) ) {
			define( 'DONOTCACHEPAGE', 1 );
		}

		add_action( 'wp_enqueue_scripts',    array( $this, 'enqueue_assets' ), -999 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ), -999 );
		add_action( 'login_enqueue_scripts', array( $this, 'enqueue_assets' ), -999 );
		add_action( 'enqueue_embed_scripts', array( $this, 'enqueue_assets' ), -999 );
		add_action( 'send_headers',          'nocache_headers' );

		add_action( 'gp_head',                array( $this, 'manually_print_assets' ), 11 );

	}

	public function manually_print_assets() {
		wp_print_scripts( array(
			'query-monitor',
		) );
		wp_print_styles( array(
			'query-monitor',
		) );
	}

	public function enqueue_assets() {
		global $wp_locale, $wp_version;
		if ( function_exists( 'is_amp_endpoint' ) && is_amp_endpoint() ) {
			return;
		}

		$deps = array(
			'jquery',
		);

		if ( defined( 'QM_NO_JQUERY' ) && QM_NO_JQUERY ) {
			$deps = array();
		}

		wp_enqueue_style(
			'query-monitor',
			$this->qm->plugin_url( 'assets/query-monitor.css' ),
			array( 'dashicons' ),
			$this->qm->plugin_ver( 'assets/query-monitor.css' )
		);
		wp_enqueue_script(
			'query-monitor',
			$this->qm->plugin_url( 'assets/query-monitor.js' ),
			$deps,
			$this->qm->plugin_ver( 'assets/query-monitor.js' ),
			false
		);
		wp_localize_script(
			'query-monitor',
			'qm_number_format',
			$wp_locale->number_format
		);
		wp_localize_script(
			'query-monitor',
			'qm_l10n',
			array(
				'ajaxurl'    => admin_url( 'admin-ajax.php' ),
				'auth_nonce' => array(
					'on'  => wp_create_nonce( 'qm-auth-on' ),
					'off' => wp_create_nonce( 'qm-auth-off' ),
				),
			)
		);
	}

	public function dispatch() {

		if ( ! $this->should_dispatch() ) {
			return;
		}

		$this->before_output();

		/* @var QM_Output_Html[] */
		foreach ( $this->outputters as $id => $output ) {
			$timer = new QM_Timer;
			$timer->start();

			$output->output();

			$output->set_timer( $timer->stop() );
		}

		$this->after_output();

	}

	protected function before_output() {

		require_once $this->qm->plugin_path( 'output/Html.php' );

		foreach ( glob( $this->qm->plugin_path( 'output/html/*.php' ) ) as $file ) {
			require_once $file;
		}

		$this->outputters     = $this->get_outputters( 'html' );
		$this->admin_bar_menu = apply_filters( 'qm/output/menus', array() );
		$this->panel_menu     = apply_filters( 'qm/output/panel_menus', $this->admin_bar_menu );

		$class = array(
			'qm-no-js',
		);

		if ( did_action( 'wp_head' ) ) {
			$class[] = sprintf( 'qm-theme-%s', get_template() );
			$class[] = sprintf( 'qm-theme-%s', get_stylesheet() );
		}

		if ( ! is_admin_bar_showing() ) {
			$class[] = 'qm-peek';
		}

		$json = array(
			'menu'        => $this->js_admin_bar_menu(),
			'ajax_errors' => array(), # @TODO move this into the php_errors collector
		);

		echo '<script type="text/javascript">' . "\n\n";
		echo 'var qm = ' . json_encode( $json ) . ';' . "\n\n";
		echo '</script>' . "\n\n";

		echo '<div id="query-monitor" class="' . implode( ' ', array_map( 'esc_attr', $class ) ) . '">';
		echo '<div id="qm-title">';
		echo '<h1 class="qm-title-heading">' . esc_html__( 'Query Monitor', 'query-monitor' ) . '</h1>';
		echo '<div class="qm-title-heading">';
		echo '<select>';

		printf(
			'<option value="%1$s">%2$s</option>',
			'#qm-overview',
			esc_html__( 'Overview', 'query-monitor' )
		);

		foreach ( $this->panel_menu as $menu ) {
			printf(
				'<option value="%1$s">%2$s</option>',
				esc_attr( $menu['href'] ),
				esc_html( $menu['title'] )
			);
		}
		echo '</select>';

		echo '</div>';
		echo '<div class="qm-title-button"><button class="qm-button-container-settings"><span class="screen-reader-text">' . esc_html__( 'Settings', 'query-monitor' ) . '</span><span class="dashicons dashicons-admin-generic" aria-hidden="true"></span></button></div>';
		echo '<div class="qm-title-button"><button class="qm-button-container-pin"><span class="screen-reader-text">' . esc_html__( 'Pin Panel Open', 'query-monitor' ) . '</span><span class="dashicons dashicons-admin-post" aria-hidden="true"></span></button></div>';
		echo '<div class="qm-title-button"><button class="qm-button-container-close"><span class="screen-reader-text">' . esc_html__( 'Close Panel', 'query-monitor' ) . '</span><span class="dashicons dashicons-no-alt" aria-hidden="true"></span></button></div>';
		echo '</div>'; // #qm-title

		echo '<div id="qm-wrapper">';
		echo '<div id="qm-panel-menu">';
		echo '<ul>';

		printf(
			'<li><a href="%1$s">%2$s</a></li>',
			'#qm-overview',
			esc_html__( 'Overview', 'query-monitor' )
		);

		foreach ( $this->panel_menu as $menu ) {
			printf(
				'<li><a href="%1$s">%2$s</a></li>',
				esc_attr( $menu['href'] ),
				esc_html( $menu['title'] )
			);
		}

		echo '</ul>';
		echo '</div>'; // #qm-panel-menu

		echo '<div id="qm-panels">';

	}

	protected function after_output() {

		echo '<div class="qm qm-non-tabular" id="qm-settings">';

		echo '<div class="qm-boxed">';
		echo '<div class="qm-section">';
		echo '<h2>' . esc_html__( 'Authentication', 'query-monitor' ) . '</h2>';

		if ( ! self::user_verified() ) {

			echo '<p>' . esc_html__( 'You can set an authentication cookie which allows you to view Query Monitor output when you&rsquo;re not logged in.', 'query-monitor' ) . '</p>';
			echo '<p><a href="#" class="qm-auth" data-action="on">' . esc_html__( 'Set authentication cookie', 'query-monitor' ) . '</a></p>';

		} else {

			echo '<p>' . esc_html__( 'You currently have an authentication cookie which allows you to view Query Monitor output.', 'query-monitor' ) . '</p>';
			echo '<p><a href="#" class="qm-auth" data-action="off">' . esc_html__( 'Clear authentication cookie', 'query-monitor' ) . '</a></p>';

		}

		echo '</div>';

		$default_expensive = 0.05;
		$constants = array(
			'QM_DB_EXPENSIVE' => array(
				/* translators: %s: The default value for a PHP constant */
				'label'   => __( 'If an individual database query takes longer than this time to execute, it\'s considered "slow" and triggers a warning. Default value: %s.', 'query-monitor' ),
				'default' => 0.05,
			),
			'QM_DISABLED' => array(
				'label'   => __( 'Disable Query Monitor entirely.', 'query-monitor' ),
				'default' => false,
			),
			'QM_DISABLE_ERROR_HANDLER' => array(
				'label'   => __( 'Disable the handling of PHP errors.', 'query-monitor' ),
				'default' => false,
			),
			'QM_ENABLE_CAPS_PANEL' => array(
				'label'   => __( 'Enable the Capability Checks panel.', 'query-monitor' ),
				'default' => false,
			),
			'QM_HIDE_CORE_ACTIONS' => array(
				'label'   => __( 'Hide WordPress core on the Hooks & Actions panel.', 'query-monitor' ),
				'default' => false,
			),
			'QM_HIDE_SELF' => array(
				'label'   => __( 'Hide Query Monitor itself on various panels (currently Hooks & Actions and Capability Checks).', 'query-monitor' ),
				'default' => false,
			),
			'QM_NO_JQUERY' => array(
				'label'   => __( 'Don\'t specify jQuery as a dependency of Query Monitor. If jQuery isn\'t enqueued then Query Monitor will still operate, but with some reduced functionality.', 'query-monitor' ),
				'default' => false,
			),
			'QM_SHOW_ALL_HOOKS' => array(
				'label'   => __( 'In the Hooks & Actions panel, show every hook that has an action or filter attached (instead of every action hook that fired during the request).', 'query-monitor' ),
				'default' => false,
			),
		);

		echo '<div class="qm-section">';
		echo '<h2>' . esc_html__( 'Configuration', 'query-monitor' ) . '</h2>';
		echo '<p>';
		printf(
			/* translators: %s: Name of the config file */
			esc_html__( 'The following PHP constants can be defined in your %s file in order to control the behaviour of Query Monitor.', 'query-monitor' ),
			'<code>wp-config.php</code>'
		);
		echo '</p>';

		foreach ( $constants as $name => $constant ) {
			echo '<h3><code>' . esc_html( $name ) . '</code></h3>';
			echo '<p>';
			printf(
				esc_html( $constant['label'] ),
				'<code>' . esc_html( $constant['default'] ) . '</code>'
			);

			if ( defined( $name ) ) {
				$current_value = constant( $name );
				if ( is_bool( $current_value ) ) {
					$current_value = QM_Collector::format_bool_constant( $name );
				}
			}

			if ( defined( $name ) && ( constant( $name ) !== $constant['default'] ) ) {
				echo '<br><span class="qm-warn">';
				printf(
					/* translators: %s: Current value for a PHP constant */
					esc_html__( 'Current value: %s', 'query-monitor' ),
					'<code>' . esc_html( $current_value ) . '</code>'
				);
				echo '</span>';
			}
			echo '</p>';
		}

		echo '</div>';

		echo '</div>';

		echo '</div>'; // #qm-settings
		echo '</div>'; // #qm-panels
		echo '</div>'; // #qm-wrapper
		echo '</div>'; // #qm

		echo '<script type="text/javascript">' . "\n\n";
		?>
		if ( ( 'undefined' === typeof QM_i18n ) || ( 'undefined' === typeof jQuery ) || ! jQuery ) {
			/* Fallback for worst case scenario */
			document.getElementById( 'query-monitor' ).className += ' qm-broken';
			console.error( document.getElementById( 'qm-broken' ).textContent );
			var menu_item = document.getElementById( 'wp-admin-bar-query-monitor' );
			if ( menu_item ) {
				menu_item.addEventListener( 'click', function() {
					document.getElementById( 'query-monitor' ).className += ' qm-show';
				} );
			}
		} else if ( ! document.getElementById( 'wpadminbar' ) ) {
			document.getElementById( 'query-monitor' ).className += ' qm-peek';
		}
		<?php
		echo '</script>' . "\n\n";
		echo '<!-- End of Query Monitor output -->' . "\n\n";

	}

	protected static function size( $var ) {
		$start_memory = memory_get_usage();

		try {
			$var = unserialize( serialize( $var ) ); // @codingStandardsIgnoreLine
		} catch ( Exception $e ) {
			return $e;
		}

		return memory_get_usage() - $start_memory - ( PHP_INT_SIZE * 8 );
	}

	public function js_admin_bar_menu() {

		$class = implode( ' ', apply_filters( 'qm/output/menu_class', array() ) );

		if ( false === strpos( $class, 'qm-' ) ) {
			$class .= ' qm-all-clear';
		}

		$title = implode( '&nbsp;&nbsp;&nbsp;', apply_filters( 'qm/output/title', array() ) );

		if ( empty( $title ) ) {
			$title = esc_html__( 'Query Monitor', 'query-monitor' );
		}

		$admin_bar_menu = array(
			'top' => array(
				'title'     => sprintf( '<span class="ab-icon">QM</span><span class="ab-label">%s</span>', $title ),
				'classname' => $class,
			),
			'sub' => array(),
		);

		foreach ( $this->admin_bar_menu as $menu ) {
			$admin_bar_menu['sub'][ $menu['id'] ] = $menu;
		}

		return $admin_bar_menu;

	}

	public function is_active() {

		if ( ! $this->user_can_view() ) {
			return false;
		}

		if ( ! $this->did_footer ) {
			return false;
		}

		// If this is an async request and not a customizer preview:
		if ( QM_Util::is_async() && ( ! function_exists( 'is_customize_preview' ) || ! is_customize_preview() ) ) {
			return false;
		}

		# Don't process if the minimum required actions haven't fired:
		if ( is_admin() ) {
			if ( ! did_action( 'admin_init' ) ) {
				return false;
			}
		} else {
			if ( ! ( did_action( 'wp' ) || did_action( 'login_init' ) || did_action( 'gp_head' ) ) ) {
				return false;
			}
		}

		# Back-compat filter. Please use `qm/dispatch/html` instead
		if ( ! apply_filters( 'qm/process', true, is_admin_bar_showing() ) ) {
			return false;
		}

		return true;

	}

}

function register_qm_dispatcher_html( array $dispatchers, QM_Plugin $qm ) {
	$dispatchers['html'] = new QM_Dispatcher_Html( $qm );
	return $dispatchers;
}

add_filter( 'qm/dispatchers', 'register_qm_dispatcher_html', 10, 2 );
