<?php declare(strict_types = 1);
/**
 * General HTML request dispatcher.
 *
 * @package query-monitor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class QM_Dispatcher_Html extends QM_Dispatcher {

	/**
	 * Outputter instances.
	 *
	 * @var array<string, QM_Output_Html> Array of outputters.
	 */
	protected $outputters = array();

	/**
	 * @var string
	 */
	public $id = 'html';

	/**
	 * @var bool
	 */
	public $did_footer = false;

	/**
	 * @var array<string, mixed[]>
	 */
	protected $admin_bar_menu = array();

	/**
	 * @var array<string, mixed[]>
	 */
	protected $panel_menu = array();

	public function __construct( QM_Plugin $qm ) {

		add_action( 'admin_bar_menu', array( $this, 'action_admin_bar_menu' ), 999 );
		add_action( 'wp_ajax_qm_auth_on', array( $this, 'ajax_on' ) );
		add_action( 'wp_ajax_qm_auth_off', array( $this, 'ajax_off' ) );
		add_action( 'wp_ajax_nopriv_qm_auth_off', array( $this, 'ajax_off' ) );

		// 9 is a magic number, it's the latest we can realistically use due to plugins
		// which call `fastcgi_finish_request()` in a `shutdown` callback hooked on the
		// default priority of 10, and QM needs to dispatch its output before those.
		add_action( 'shutdown', array( $this, 'dispatch' ), 9 );

		add_action( 'wp_footer', array( $this, 'action_footer' ) );
		add_action( 'admin_footer', array( $this, 'action_footer' ) );
		add_action( 'login_footer', array( $this, 'action_footer' ) );
		add_action( 'gp_footer', array( $this, 'action_footer' ) );

		parent::__construct( $qm );

	}

	/**
	 * @return void
	 */
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

	/**
	 * @return void
	 */
	public function ajax_on() {

		if ( ! current_user_can( 'view_query_monitor' ) || ! check_ajax_referer( 'qm-auth-on', 'nonce', false ) ) {
			wp_send_json_error();
		}

		$expiration = time() + ( 2 * DAY_IN_SECONDS );
		$secure = self::secure_cookie();
		$cookie = wp_generate_auth_cookie( get_current_user_id(), $expiration, 'logged_in' );
		$domain = COOKIE_DOMAIN ?: '';

		setcookie( QM_COOKIE, $cookie, $expiration, COOKIEPATH, $domain, $secure, false );

		wp_send_json_success();

	}

	/**
	 * @return void
	 */
	public function ajax_off() {

		if ( ! self::user_verified() || ! check_ajax_referer( 'qm-auth-off', 'nonce', false ) ) {
			wp_send_json_error();
		}

		$expiration = time() - 31536000;
		$domain = COOKIE_DOMAIN ?: '';

		setcookie( QM_COOKIE, ' ', $expiration, COOKIEPATH, $domain );

		wp_send_json_success();

	}

	/**
	 * @param WP_Admin_Bar $wp_admin_bar
	 * @return void
	 */
	public function action_admin_bar_menu( WP_Admin_Bar $wp_admin_bar ) {
		if ( ! self::user_can_view() ) {
			return;
		}

		$title = __( 'Query Monitor', 'query-monitor' );

		$wp_admin_bar->add_node( array(
			'id' => 'query-monitor',
			'title' => esc_html( $title ),
			'href' => '#qm-overview',
		) );

		$wp_admin_bar->add_node( array(
			'parent' => 'query-monitor',
			'id' => 'query-monitor-placeholder',
			'title' => esc_html( $title ),
			'href' => '#qm-overview',
		) );
	}

	/**
	 * @return void
	 */
	public function init() {

		if ( ! self::user_can_view() ) {
			return;
		}

		if ( ! self::request_supported() ) {
			return;
		}

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ), -9999 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ), -9999 );
		add_action( 'login_enqueue_scripts', array( $this, 'enqueue_assets' ), -9999 );
		add_action( 'enqueue_embed_scripts', array( $this, 'enqueue_assets' ), -9999 );

		add_action( 'gp_head', array( $this, 'manually_print_assets' ), 11 );

		parent::init();
	}

	/**
	 * @return void
	 */
	public function manually_print_assets() {
		wp_print_scripts( array(
			'query-monitor',
		) );
		wp_print_styles( array(
			'query-monitor',
		) );
	}

	/**
	 * @return void
	 */
	public function enqueue_assets() {
		/** @var WP_Locale $wp_locale */
		global $wp_locale;

		\QM\Vite\enqueue_asset(
			dirname( __DIR__ ) . '/build',
			'assets/query-monitor.css',
			array(
				'handle' => 'query-monitor-css',
			)
		);
		\QM\Vite\enqueue_asset(
			dirname( __DIR__ ) . '/build',
			'src/index.tsx',
			array(
				'handle' => 'query-monitor-js',
				'css-dependencies' => array( 'query-monitor-css' ),
			)
		);
		wp_localize_script(
			'query-monitor-js',
			'qm_number_format',
			$wp_locale->number_format
		);
		wp_localize_script(
			'query-monitor-js',
			'qm_l10n',
			array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'admin_url' => admin_url(),
				'auth_nonce' => array(
					'on' => wp_create_nonce( 'qm-auth-on' ),
					'off' => wp_create_nonce( 'qm-auth-off' ),
				),
			)
		);

		wp_set_script_translations( 'query-monitor-js', 'query-monitor' );

		/**
		 * Fires when assets for QM's HTML have been enqueued.
		 *
		 * @since 3.6.0
		 *
		 * @param \QM_Dispatcher_Html $dispatcher The HTML dispatcher.
		 */
		do_action( 'qm/output/enqueued-assets', $this );

	}

	/**
	 * @return void
	 */
	public function dispatch() {
		if ( ! $this->should_dispatch() ) {
			return;
		}

		if ( $this->ceased ) {
			$admin_bar_menu = array(
				'top' => array(
					'title' => 'Query Monitor',
				),
				'sub' => array(
					'ceased' => array(
						'title' => esc_html__( 'Data collection ceased', 'query-monitor' ),
						'id' => 'query-monitor-ceased',
						'href' => '#',
					),
				),
			);

			$json = array(
				'menu' => $admin_bar_menu,
			);

			echo '<!-- Begin Query Monitor output -->' . "\n\n";
			wp_print_inline_script_tag(
				sprintf(
					'var QueryMonitorData = %s;',
					wp_json_encode( $json )
				),
				array(
					'id' => 'query-monitor-inline-data',
				)
			);
			echo '<div id="query-monitor-ceased"></div>';
			echo '<!-- End Query Monitor output -->' . "\n\n";
			return;
		}

		$switched_locale = self::switch_to_locale( get_user_locale() );

		$this->before_output();

		// Output non-client-side rendered panels outside the React container
		echo '<div id="query-monitor-fallbacks">';
		foreach ( $this->outputters as $id => $output ) {
			if ( ! $output::$client_side_rendered ) {
				printf(
					"\n" . '<!-- Begin %1$s output -->' . "\n",
					esc_html( $id )
				);

				printf(
					"\n" . '<div class="qm-panel-container" id="qm-%1$s-container">' . "\n",
					esc_html( $id )
				);
				$output->output();
				echo "\n" . '</div>' . "\n";

				printf(
					"\n" . '<!-- End %s output -->' . "\n",
					esc_html( $id )
				);
			}
		}
		echo '</div>' . "\n";

		// Output the empty container for React to mount into
		echo '<div id="query-monitor-container"></div>';

		$this->after_output();

		if ( $switched_locale ) {
			self::restore_previous_locale();
		}

	}

	/**
	 * @return void
	 */
	protected function before_output() {
		foreach ( (array) glob( $this->qm->plugin_path( 'output/html/*.php' ) ) as $file ) {
			require_once $file;
		}

		/** @var array<string, QM_Output_Html> $outputters */
		$outputters = $this->get_outputters( 'html' );

		$this->outputters = $outputters;

		/**
		 * Filters the menu items shown in Query Monitor's admin toolbar menu.
		 *
		 * @since 3.0.0
		 *
		 * @param array<string, mixed[]> $menus Array of menus.
		 */
		$this->admin_bar_menu = apply_filters( 'qm/output/menus', array() );

		/**
		 * Filters the menu items shown in the panel navigation menu in Query Monitor's output.
		 *
		 * @since 3.0.0
		 *
		 * @param array<string, mixed[]> $admin_bar_menu Array of menus.
		 */
		$this->panel_menu = apply_filters( 'qm/output/panel_menus', $this->admin_bar_menu );

		$data = array();

		foreach ( $this->outputters as $output_id => $output ) {
			$collector = $output->get_collector();

			if ( $output::$client_side_rendered ) {
				$collector_data = $collector->get_data();

				// Add concerned actions and filters data if they exist
				if ( ! empty( $collector->concerned_filters ) ) {
					$collector_data->concerned_filters = $collector->concerned_filters;
				}

				if ( ! empty( $collector->concerned_actions ) ) {
					$collector_data->concerned_actions = $collector->concerned_actions;
				}

				$data[ $collector->id ] = array(
					'enabled' => $collector::enabled(),
					'data'    => $collector_data,
				);
			}

			if ( ( ! empty( $collector->concerned_filters ) || ! empty( $collector->concerned_actions ) ) && isset( $this->panel_menu[ $output_id ] ) ) {
				$count = count( $collector->concerned_filters ) + count( $collector->concerned_actions );
				$this->panel_menu[ $output_id ]['children'][ $output_id . '-concerned_hooks' ] = array(
					'id' => $collector->id() . '-concerned_hooks',
					'panel' => $collector->id() . '-concerned_hooks',
					'title' => sprintf(
						/* translators: %s: Number of hooks */
						__( 'Hooks in Use (%s)', 'query-monitor' ),
						number_format_i18n( $count )
					),
				);
			}
		}

		$data['overview'] = array(
			'enabled' => true,
			'data' => QM_Collectors::get( 'overview' )->get_data(),
		);

		$json = array(
			'menu' => $this->js_admin_bar_menu(),
			'settings'    => array(
				'verified' => self::user_verified(),
			),
			'panel_menu'  => $this->panel_menu,
			'data'        => $data,
		);

		echo '<!-- Begin Query Monitor output -->' . "\n\n";
		wp_print_inline_script_tag(
			sprintf(
				'var QueryMonitorData = %s;',
				wp_json_encode( $json )
			),
			array(
				'id' => 'query-monitor-inline-data',
			)
		);
	}

	/**
	 * @return void
	 */
	protected function after_output() {
		echo '<!-- End Query Monitor output -->' . "\n\n";
	}

	/**
	 * @param mixed $var
	 * @return int|Exception
	 */
	public static function size( $var ) {
		$start_memory = memory_get_usage();

		try {
			$var = unserialize( serialize( $var ) ); // phpcs:ignore
		} catch ( Exception $e ) {
			return $e;
		}

		return memory_get_usage() - $start_memory - ( PHP_INT_SIZE * 8 );
	}

	/**
	 * @return array<string, mixed>
	 */
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
		$title = apply_filters( 'qm/output/title', array() );

		$admin_bar_menu = array(
			'top' => array(
				'title'     => $title,
				'classname' => $class,
			),
			'sub' => array(),
		);

		foreach ( $this->admin_bar_menu as $menu ) {
			$admin_bar_menu['sub'][ $menu['id'] ] = $menu;
		}

		return $admin_bar_menu;

	}

	/**
	 * @return bool
	 */
	public static function request_supported() {
		// Don't dispatch if this is an async request:
		if ( QM_Util::is_async() ) {
			return false;
		}

		// Don't dispatch during a Customizer preview request:
		if ( function_exists( 'is_customize_preview' ) && is_customize_preview() ) {
			return false;
		}

		// Don't dispatch during an iframed request, eg the plugin info modal, an upgrader action, or the Customizer:
		if ( defined( 'IFRAME_REQUEST' ) && IFRAME_REQUEST ) {
			return false;
		}

		// Don't dispatch inside the Site Editor:
		if ( isset( $_SERVER['SCRIPT_NAME'] ) && '/wp-admin/site-editor.php' === $_SERVER['SCRIPT_NAME'] ) {
			return false;
		}

		// Don't dispatch on the interim login screen:
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! empty( $_GET['interim-login'] ) ) {
			return false;
		}

		return true;
	}

	/**
	 * @return bool
	 */
	public function is_active() {

		if ( ! self::user_can_view() ) {
			return false;
		}

		if ( ! $this->did_footer ) {
			return false;
		}

		if ( ! self::request_supported() ) {
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

		/** Back-compat filter. Please use `qm/dispatch/html` instead */
		if ( ! apply_filters( 'qm/process', true, is_admin_bar_showing() ) ) {
			return false;
		}

		return true;

	}

	/**
	 * @param string $error
	 * @param mixed[] $e
	 * @phpstan-param array{
	 *   message: string,
	 *   file: string,
	 *   line: int,
	 *   type?: int,
	 *   trace?: mixed|null,
	 * } $e
	 */
	public function output_fatal( $error, array $e ): void {
		// This hides the subsequent message from the fatal error handler in core. It cannot be
		// disabled by a plugin so we'll just hide its output.
		echo '<style type="text/css"> .wp-die-message { display: none; } </style>';

		printf(
			// phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedStylesheet
			'<link rel="stylesheet" href="%1$s?ver=%2$s" media="all" />',
			esc_url( QueryMonitor::init()->plugin_url( 'assets/query-monitor.css' ) ),
			esc_attr( QM_VERSION )
		);

		// This unused wrapper with an attribute serves to help the #qm-fatal div break out of an
		// attribute if a fatal has occurred within one.
		echo '<div data-qm="qm">';

		printf(
			'<div id="qm-fatal" data-qm-message="%1$s" data-qm-file="%2$s" data-qm-line="%3$d">',
			esc_attr( $e['message'] ),
			esc_attr( QM_Util::standard_dir( $e['file'], '' ) ),
			intval( $e['line'] )
		);

		echo '<div class="qm-fatal-wrap">';

		printf(
			'<p>%1$s<br>in <b>%2$s</b> on line <b>%3$d</b></p>',
			nl2br( esc_html( $e['message'] ), false ),
			esc_html( $e['file'] ),
			intval( $e['line'] )
		); // WPCS: XSS ok.

		if ( ! empty( $e['trace'] ) ) {
			echo '<p>Call stack:</p>';
			echo '<ol>';
			foreach ( $e['trace'] as $frame ) {
				$callback = QM_Util::populate_callback( $frame );

				if ( ! isset( $callback['name'] ) ) {
					continue;
				}

				$args = array_map( function( $value ) {
					$type = gettype( $value );

					switch ( $type ) {
						case 'object':
							return get_class( $value );
						case 'boolean':
							return $value ? 'true' : 'false';
						case 'integer':
						case 'double':
							return $value;
						case 'string':
							if ( strlen( $value ) > 50 ) {
								return "'" . substr( $value, 0, 20 ) . '...' . substr( $value, -20 ) . "'";
							}
							return "'" . $value . "'";
					}

					return $type;
				}, $frame['args'] ?? array() );

				$name = str_replace( '()', '(' . implode( ', ', $args ) . ')', $callback['name'] );

				printf(
					'<li>%s</li>',
					QM_Output_Html::output_filename( $name, $frame['file'], $frame['line'] )
				); // WPCS: XSS ok.
			}
			echo '</ol>';
		}

		echo '</div>';

		echo '<h2>Query Monitor</h2>';

		echo '</div>';
		echo '</div>';
	}

	/**
	 * Cease without deactivating the dispatcher.
	 *
	 * @return void
	 */
	public function cease() {
		$this->ceased = true;
	}
}

/**
 * @param array<string, QM_Dispatcher> $dispatchers
 * @param QM_Plugin $qm
 * @return array<string, QM_Dispatcher>
 */
function register_qm_dispatcher_html( array $dispatchers, QM_Plugin $qm ) {
	$dispatchers['html'] = new QM_Dispatcher_Html( $qm );
	return $dispatchers;
}

add_filter( 'qm/dispatchers', 'register_qm_dispatcher_html', 10, 2 );
