<?php
/**
 * General HTML request dispatcher.
 *
 * @package query-monitor
 */

class QM_Dispatcher_Html extends QM_Dispatcher {

	/**
	 * Outputter instances.
	 *
	 * @var QM_Output_Html[] Array of outputters.
	 */
	protected $outputters = array();

	public $id         = 'html';
	public $did_footer = false;

	protected $admin_bar_menu = array();
	protected $panel_menu     = array();

	public function __construct( QM_Plugin $qm ) {

		add_action( 'admin_bar_menu',             array( $this, 'action_admin_bar_menu' ), 999 );
		add_action( 'wp_ajax_qm_auth_on',         array( $this, 'ajax_on' ) );
		add_action( 'wp_ajax_qm_auth_off',        array( $this, 'ajax_off' ) );
		add_action( 'wp_ajax_qm_editor_set',      array( $this, 'ajax_editor_set' ) );
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

		if ( ! self::user_can_view() ) {
			return;
		}

		$title = __( 'Query Monitor', 'query-monitor' );

		$wp_admin_bar->add_node( array(
			'id'    => 'query-monitor',
			'title' => esc_html( $title ),
			'href'  => '#qm-overview',
		) );

		$wp_admin_bar->add_node( array(
			'parent' => 'query-monitor',
			'id'     => 'query-monitor-placeholder',
			'title'  => esc_html( $title ),
			'href'   => '#qm-overview',
		) );

	}

	public function init() {

		if ( ! self::user_can_view() ) {
			return;
		}

		if ( ! file_exists( $this->qm->plugin_path( 'assets/query-monitor.css' ) ) ) {
			add_action( 'admin_notices', array( $this, 'build_warning' ) );
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

	public function build_warning() {
		printf(
			'<div id="qm-built-nope" class="notice notice-error"><p>%s</p></div>',
			sprintf(
				/* translators: 1: CLI command to run, 2: plugin directory name */
				esc_html__( 'Asset files for Query Monitor need to be built. Run %1$s from the %2$s directory.', 'query-monitor' ),
				'<code>npm i && npm run build</code>',
				sprintf(
					'<code>%s</code>',
					esc_html( QM_Util::standard_dir( untrailingslashit( $this->qm->plugin_path() ), '' ) )
				)
			)
		);
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
		} elseif ( defined( 'QM_DARK_MODE' ) && QM_DARK_MODE ) {
			$css .= '-dark';
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
					'on'         => wp_create_nonce( 'qm-auth-on' ),
					'off'        => wp_create_nonce( 'qm-auth-off' ),
				),
				'fatal_error' => __( 'PHP Fatal Error', 'query-monitor' ),
			)
		);

		$asset_file = require_once $this->qm->plugin_path( 'build/main.asset.php' );

		wp_enqueue_script(
			'query-monitor-ui',
			$this->qm->plugin_url( 'build/main.js' ),
			array_merge(
				array(
					'wp-element',
					'wp-i18n',
				),
				$asset_file['dependencies']
			),
			$asset_file['version'],
			true
		);

		wp_set_script_translations( 'query-monitor-ui', 'query-monitor' );

		/**
		 * Fires when assets for QM's HTML have been enqueued.
		 *
		 * @since 3.6.0
		 *
		 * @param \QM_Dispatcher_Html $this The HTML dispatcher.
		 */
		do_action( 'qm/output/enqueued-assets', $this );

	}

	public function dispatch() {

		if ( ! $this->should_dispatch() ) {
			return;
		}

		$switched_locale = function_exists( 'switch_to_locale' ) && switch_to_locale( get_user_locale() );

		$this->before_output();

		foreach ( $this->outputters as $id => $output ) {
			printf(
				"\n" . '<!-- Begin %1$s output -->' . "\n",
				esc_html( $id )
			);

			if ( $output::$client_side_rendered ) {
				echo "\t" . '<!-- Client-side rendered -->';
			} else {
				printf(
					"\n" . '<div class="qm-panel-container" id="qm-%1$s-container">' . "\n",
					esc_html( $id )
				);
				$output->output();
				echo "\n" . '</div>' . "\n";
			}

			printf(
				"\n" . '<!-- End %s output -->' . "\n",
				esc_html( $id )
			);
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
		 * @param array $admin_bar_menu Array of menus.
		 */
		$this->panel_menu = apply_filters( 'qm/output/panel_menus', $this->admin_bar_menu );

		$json_data = array();

		foreach ( $this->outputters as $output_id => $output ) {
			$collector = $output->get_collector();

			if ( $output::$client_side_rendered ) {
				$json_data[ $collector->id ] = array(
					'enabled' => $collector::enabled(),
					'data'    => $collector->get_data(),
				);
			}

			if ( ( ! empty( $collector->concerned_filters ) || ! empty( $collector->concerned_actions ) ) && isset( $this->panel_menu[ 'qm-' . $output_id ] ) ) {
				$this->panel_menu[ 'qm-' . $output_id ]['children'][ 'qm-' . $output_id . '-concerned_hooks' ] = array(
					'href'  => esc_attr( '#' . $collector->id() . '-concerned_hooks' ),
					'title' => __( 'Hooks in Use', 'query-monitor' ),
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
			'settings'    => array(
				'verified' => self::user_verified(),
			),
		);

		echo '<!-- Begin Query Monitor output -->' . "\n\n";
		echo '<script type="text/javascript">' . "\n\n";
		echo 'var qm = ' . json_encode( $json ) . ';' . "\n\n";
		echo 'var qm_data = ' . json_encode( $json_data, JSON_UNESCAPED_SLASHES ) . ';' . "\n\n";
		echo 'var qm_menu = ' . json_encode( $this->panel_menu, JSON_UNESCAPED_SLASHES ) . ';' . "\n\n";
		echo '</script>' . "\n\n";

		echo '<div id="query-monitor-main" class="' . implode( ' ', array_map( 'esc_attr', $class ) ) . '" dir="ltr">';
	}

	protected function after_output() {
		/**
		 * Fires after settings but before the panel closing tag.
		 *
		 * @since  3.1.0
		 *
		 * @param QM_Dispatcher_Html $this             The HTML dispatcher instance.
		 * @param QM_Output_Html[]   $this->outputters Array of outputters.
		 */
		do_action( 'qm/output/after', $this, $this->outputters );

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
				'title'     => sprintf(
					'<span class="ab-icon">QM</span><span class="ab-label">%s</span>',
					$title
				),
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

		if ( ! self::user_can_view() ) {
			return false;
		}

		if ( ! $this->did_footer ) {
			return false;
		}

		// Don't dispatch if this is an async request and not a customizer preview:
		if ( QM_Util::is_async() && ( ! function_exists( 'is_customize_preview' ) || ! is_customize_preview() ) ) {
			return false;
		}

		// Don't dispatch if the minimum required actions haven't fired:
		if ( is_admin() ) {
			if ( ! did_action( 'admin_init' ) ) {
				return false;
			}
		} else {
			if ( ! ( did_action( 'wp' ) || did_action( 'login_init' ) || did_action( 'gp_head' ) ) ) {
				return false;
			}
		}

		// Don't dispatch during an iframed request, eg the plugin info modal or an upgrader action:
		if ( defined( 'IFRAME_REQUEST' ) && IFRAME_REQUEST ) {
			return false;
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
