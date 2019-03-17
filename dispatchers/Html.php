<?php
/**
 * General HTML request dispatcher.
 *
 * @package query-monitor
 */

class QM_Dispatcher_Html extends QM_Dispatcher {

	public $id         = 'html';
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
		return ( is_ssl() && ( 'https' === parse_url( home_url(), PHP_URL_SCHEME ) ) );
	}

	public function ajax_on() {

		if ( ! current_user_can( 'view_query_monitor' ) || ! check_ajax_referer( 'qm-auth-on', 'nonce', false ) ) {
			wp_send_json_error();
		}

		$expiration = time() + ( 2 * DAY_IN_SECONDS );
		$secure     = self::secure_cookie();
		$cookie     = wp_generate_auth_cookie( get_current_user_id(), $expiration, 'logged_in' );

		setcookie( QM_COOKIE, $cookie, $expiration, COOKIEPATH, COOKIE_DOMAIN, $secure, false );

		wp_send_json_success();

	}

	public function ajax_off() {

		if ( ! self::user_verified() || ! check_ajax_referer( 'qm-auth-off', 'nonce', false ) ) {
			wp_send_json_error();
		}

		$expiration = time() - 31536000;

		setcookie( QM_COOKIE, ' ', $expiration, COOKIEPATH, COOKIE_DOMAIN );

		wp_send_json_success();

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

		add_action( 'wp_enqueue_scripts',    array( $this, 'enqueue_assets' ), -9999 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ), -9999 );
		add_action( 'login_enqueue_scripts', array( $this, 'enqueue_assets' ), -9999 );
		add_action( 'enqueue_embed_scripts', array( $this, 'enqueue_assets' ), -9999 );

		add_action( 'gp_head',                array( $this, 'manually_print_assets' ), 11 );

		parent::init();
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

		$deps = array(
			'jquery',
		);

		if ( defined( 'QM_NO_JQUERY' ) && QM_NO_JQUERY ) {
			$deps = array();
		}

		$css = 'query-monitor';

		if ( method_exists( 'Dark_Mode', 'is_using_dark_mode' ) && is_user_logged_in() ) {
			if ( Dark_Mode::is_using_dark_mode() ) {
				$css .= '-dark';
			}
		}

		wp_enqueue_style(
			'query-monitor',
			$this->qm->plugin_url( "assets/{$css}.css" ),
			array( 'dashicons' ),
			$this->qm->plugin_ver( "assets/{$css}.css" )
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
				'ajax_error' => __( 'PHP Errors in Ajax Response', 'query-monitor' ),
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

		$switched_locale = function_exists( 'switch_to_locale' ) && switch_to_locale( get_user_locale() );

		$this->before_output();

		/* @var QM_Output_Html[] */
		foreach ( $this->outputters as $id => $output ) {
			$timer = new QM_Timer();
			$timer->start();

			printf(
				"\n" . '<!-- Begin %s output -->' . "\n",
				esc_html( $id )
			);
			$output->output();
			printf(
				"\n" . '<!-- End %s output -->' . "\n",
				esc_html( $id )
			);

			$output->set_timer( $timer->stop() );
		}

		$this->after_output();

		if ( $switched_locale ) {
			restore_previous_locale();
		}

	}

	protected function before_output() {

		require_once $this->qm->plugin_path( 'output/Html.php' );

		foreach ( glob( $this->qm->plugin_path( 'output/html/*.php' ) ) as $file ) {
			require_once $file;
		}

		$this->outputters = $this->get_outputters( 'html' );

		/**
		 * Filters the menu items shown in Query Monitor's admin toolbar menu.
		 *
		 * @since 3.0.0
		 *
		 * @param array $menus Array of menus.
		 */
		$this->admin_bar_menu = apply_filters( 'qm/output/menus', array() );

		/**
		 * Filters the menu items shown in the panel navigation menu in Query Monitor's output.
		 *
		 * @since 3.0.0
		 *
		 * @param array $admin_bar_men Array of menus.
		 */
		$this->panel_menu = apply_filters( 'qm/output/panel_menus', $this->admin_bar_menu );

		foreach ( $this->outputters as $output_id => $output ) {
			$collector = $output->get_collector();

			if ( ( ! empty( $collector->concerned_filters ) || ! empty( $collector->concerned_actions ) ) && isset( $this->panel_menu[ 'qm-' . $output_id ] ) ) {
				$this->panel_menu[ 'qm-' . $output_id ]['children'][ 'qm-' . $output_id . '-concerned_hooks' ] = array(
					'href'  => esc_attr( '#' . $collector->id() . '-concerned_hooks' ),
					'title' => '└ ' . __( 'Hooks in Use', 'query-monitor' ),
				);
			}
		}

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

		echo '<!-- Begin Query Monitor output -->' . "\n\n";
		echo '<script type="text/javascript">' . "\n\n";
		echo 'var qm = ' . json_encode( $json ) . ';' . "\n\n";
		echo '</script>' . "\n\n";

		echo '<div id="query-monitor-main" class="' . implode( ' ', array_map( 'esc_attr', $class ) ) . '" dir="ltr">';
		echo '<div id="qm-side-resizer" class="qm-resizer"></div>';
		echo '<div id="qm-title" class="qm-resizer">';
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
			if ( ! empty( $menu['children'] ) ) {
				foreach ( $menu['children'] as $child ) {
					printf(
						'<option value="%1$s">%2$s</option>',
						esc_attr( $child['href'] ),
						esc_html( $child['title'] )
					);
				}
			}
		}

		printf(
			'<option value="%1$s">%2$s</option>',
			'#qm-settings',
			esc_html__( 'Settings', 'query-monitor' )
		);

		echo '</select>';

		echo '</div>';
		echo '<div class="qm-title-button"><button class="qm-button-container-settings" aria-label="' . esc_attr__( 'Settings', 'query-monitor' ) . '"><span class="dashicons dashicons-admin-generic" aria-hidden="true"></span></button></div>';
		echo '<div class="qm-title-button"><button class="qm-button-container-position" aria-label="' . esc_html__( 'Toggle panel position', 'query-monitor' ) . '"><span class="dashicons dashicons-image-rotate-left" aria-hidden="true"></span></button></div>';
		echo '<div class="qm-title-button"><button class="qm-button-container-close" aria-label="' . esc_attr__( 'Close Panel', 'query-monitor' ) . '"><span class="dashicons dashicons-no-alt" aria-hidden="true"></span></button></div>';
		echo '</div>'; // #qm-title

		echo '<div id="qm-wrapper">';
		echo '<nav id="qm-panel-menu" aria-labelledby="qm-panel-menu-caption">';
		echo '<h2 class="qm-screen-reader-text" id="qm-panel-menu-caption">' . esc_html__( 'Query Monitor Menu', 'query-monitor' ) . '</h2>';
		echo '<ul>';

		printf(
			'<li><button data-qm-href="%1$s">%2$s</button></li>',
			'#qm-overview',
			esc_html__( 'Overview', 'query-monitor' )
		);

		foreach ( $this->panel_menu as $id => $menu ) {
			$this->do_panel_menu_item( $id, $menu );
		}

		echo '</ul>';
		echo '</nav>'; // #qm-panel-menu

		echo '<div id="qm-panels">';

	}

	protected function do_panel_menu_item( $id, array $menu ) {
		printf(
			'<li><button data-qm-href="%1$s">%2$s</button>',
			esc_attr( $menu['href'] ),
			esc_html( $menu['title'] )
		);

		if ( ! empty( $menu['children'] ) ) {
			echo '<ul>';
			foreach ( $menu['children'] as $child_id => $child ) {
				$this->do_panel_menu_item( $child_id, $child );
			}
			echo '</ul>';
		}

		echo '</li>';
	}

	protected function after_output() {

		$state = self::user_verified() ? 'on' : 'off';
		$text  = array(
			'on'  => __( 'Clear authentication cookie', 'query-monitor' ),
			'off' => __( 'Set authentication cookie', 'query-monitor' ),
		);

		echo '<div class="qm qm-non-tabular" id="qm-settings" data-qm-state="' . esc_attr( $state ) . '">';
		echo '<h2 class="qm-screen-reader-text">' . esc_html__( 'Settings', 'query-monitor' ) . '</h2>';

		echo '<div class="qm-boxed">';
		echo '<section>';
		echo '<h3>' . esc_html__( 'Authentication', 'query-monitor' ) . '</h3>';

		echo '<p>' . esc_html__( 'You can set an authentication cookie which allows you to view Query Monitor output when you&rsquo;re not logged in, or when you&rsquo;re logged in as a different user.', 'query-monitor' ) . '</p>';

		echo '<p data-qm-state-visibility="on"><span class="dashicons dashicons-yes qm-dashicons-yes"></span> ' . esc_html__( 'Authentication cookie is set', 'query-monitor' ) . '</p>';

		echo '<p><button class="qm-auth qm-button" data-qm-text-on="' . esc_attr( $text['on'] ) . '" data-qm-text-off="' . esc_attr( $text['off'] ) . '">' . esc_html( $text[ $state ] ) . '</button></p>';
		echo '</section>';
		echo '</div>';

		echo '<div class="qm-boxed">';
		$constants = array(
			'QM_DB_EXPENSIVE'          => array(
				/* translators: %s: The default value for a PHP constant */
				'label'   => __( 'If an individual database query takes longer than this time to execute, it\'s considered "slow" and triggers a warning. Default value: %s.', 'query-monitor' ),
				'default' => 0.05,
			),
			'QM_DISABLED'              => array(
				'label'   => __( 'Disable Query Monitor entirely.', 'query-monitor' ),
				'default' => false,
			),
			'QM_DISABLE_ERROR_HANDLER' => array(
				'label'   => __( 'Disable the handling of PHP errors.', 'query-monitor' ),
				'default' => false,
			),
			'QM_ENABLE_CAPS_PANEL'     => array(
				'label'   => __( 'Enable the Capability Checks panel.', 'query-monitor' ),
				'default' => false,
			),
			'QM_HIDE_CORE_ACTIONS'     => array(
				'label'   => __( 'Hide WordPress core on the Hooks & Actions panel.', 'query-monitor' ),
				'default' => false,
			),
			'QM_HIDE_SELF'             => array(
				'label'   => __( 'Hide Query Monitor itself from various panels.', 'query-monitor' ),
				'default' => false,
			),
			'QM_NO_JQUERY'             => array(
				'label'   => __( 'Don\'t specify jQuery as a dependency of Query Monitor. If jQuery isn\'t enqueued then Query Monitor will still operate, but with some reduced functionality.', 'query-monitor' ),
				'default' => false,
			),
			'QM_SHOW_ALL_HOOKS'        => array(
				'label'   => __( 'In the Hooks & Actions panel, show every hook that has an action or filter attached (instead of every action hook that fired during the request).', 'query-monitor' ),
				'default' => false,
			),
		);

		echo '<section>';
		echo '<h3>' . esc_html__( 'Configuration', 'query-monitor' ) . '</h3>';
		echo '<p>';
		printf(
			/* translators: %s: Name of the config file */
			esc_html__( 'The following PHP constants can be defined in your %s file in order to control the behavior of Query Monitor:', 'query-monitor' ),
			'<code>wp-config.php</code>'
		);
		echo '</p>';

		echo '<dl>';

		foreach ( $constants as $name => $constant ) {
			echo '<dt><code>' . esc_html( $name ) . '</code></dt>';
			echo '<dd>';
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
				echo '<br><span class="qm-info">';
				printf(
					/* translators: %s: Current value for a PHP constant */
					esc_html__( 'Current value: %s', 'query-monitor' ),
					'<code>' . esc_html( $current_value ) . '</code>'
				);
				echo '</span>';
			}
			echo '</dd>';
		}

		echo '</dl>';
		echo '</section>';

		echo '</div>';

		echo '</div>'; // #qm-settings

		/**
		 * Fires after settings but before the panel closing tag.
		 *
		 * @since  3.1.0
		 *
		 * @param QM_Dispatcher_Html $this             The HTML dispatcher instance.
		 * @param QM_Output[]        $this->outputters Array of outputters.
		 */
		do_action( 'qm/output/after', $this, $this->outputters );

		echo '</div>'; // #qm-panels
		echo '</div>'; // #qm-wrapper
		echo '</div>'; // #query-monitor-main

		echo '<script type="text/javascript">' . "\n\n";
		?>
		window.addEventListener('load', function() {
			if ( ( 'undefined' === typeof QM_i18n ) || ( 'undefined' === typeof jQuery ) || ! window.jQuery ) {
				/* Fallback for worst case scenario */
				document.getElementById( 'query-monitor-main' ).className += ' qm-broken';
				console.error( document.getElementById( 'qm-broken' ).textContent );

				if ( 'undefined' === typeof QM_i18n ) {
					console.error( 'QM error from page: undefined QM_i18n' );
				}

				if ( 'undefined' === typeof jQuery ) {
					console.error( 'QM error from page: undefined jQuery' );
				}

				if ( ! window.jQuery ) {
					console.error( 'QM error from page: no jQuery' );
				}

				var menu_item = document.getElementById( 'wp-admin-bar-query-monitor' );
				if ( menu_item ) {
					menu_item.addEventListener( 'click', function() {
						document.getElementById( 'query-monitor-main' ).className += ' qm-show';
					} );
				}
			} else if ( ! document.getElementById( 'wpadminbar' ) ) {
				document.getElementById( 'query-monitor-main' ).className += ' qm-peek';
			}
		} );
		<?php
		echo '</script>' . "\n\n";
		echo '<!-- End Query Monitor output -->' . "\n\n";

	}

	public static function size( $var ) {
		$start_memory = memory_get_usage();

		try {
			$var = unserialize( serialize( $var ) ); // @codingStandardsIgnoreLine
		} catch ( Exception $e ) {
			return $e;
		}

		return memory_get_usage() - $start_memory - ( PHP_INT_SIZE * 8 );
	}

	public function js_admin_bar_menu() {

		/**
		 * Filters the CSS class names used on Query Monitor's admin toolbar menu.
		 *
		 * @since 2.7.0
		 *
		 * @param array $menu_classes Array of menu classes.
		 */
		$class = implode( ' ', apply_filters( 'qm/output/menu_class', array() ) );

		if ( false === strpos( $class, 'qm-' ) ) {
			$class .= ' qm-all-clear';
		}

		/**
		 * Filters the title used in Query Monitor's admin toolbar menu.
		 *
		 * @since 2.7.0
		 *
		 * @param array $output_title List of titles.
		 */
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

		/** Back-compat filter. Please use `qm/dispatch/html` instead */
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
