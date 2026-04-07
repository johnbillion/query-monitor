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

	/**
	 * URL for the panel CSS file, to be loaded inside the shadow DOM.
	 */
	private string $panel_css_url = '';

	/**
	 * Whether we're in development mode (Vite dev server running).
	 *
	 * @var bool
	 */
	private static bool $is_dev = false;

	/**
	 * Cached manifest data.
	 *
	 * @var array<string, mixed>|null|false Null if not yet checked, false if not found, array if found.
	 */
	private static $manifest = null;

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

		// Show admin notice if assets are not built
		add_action( 'admin_notices', array( __CLASS__, 'admin_notice_missing_assets' ) );

		if ( ! self::request_supported() ) {
			return;
		}

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ), -9999 );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ), -9999 );
		add_action( 'login_enqueue_scripts', array( $this, 'enqueue_assets' ), -9999 );
		add_action( 'enqueue_embed_scripts', array( $this, 'enqueue_assets' ), -9999 );

		parent::init();
	}

	/**
	 * @return void
	 */
	public function enqueue_assets() {
		// Assets are now output directly in before_output() via QM_Assets::output().
		// This method is kept for back-compat in case plugins hook into the action
		// or depend on the `query-monitor` script or style.

		wp_register_script( 'query-monitor', false, [], QM_VERSION, false );
		wp_register_style( 'query-monitor', false, [], QM_VERSION );

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
			wp_print_inline_script_tag(
				<<<'JS'
				console.log( 'QM: collection and output was ceased' );

				const qmCeasedLink = document.querySelector( '#wp-admin-bar-query-monitor-placeholder a' );
				if ( qmCeasedLink ) {
					qmCeasedLink.innerText = 'Data collection ceased';
				}
				JS,
				array(
					'id' => 'query-monitor-ceased',
				)
			);
			return;
		}

		$switched_locale = self::switch_to_locale( get_user_locale() );

		$this->before_output();

		// Output server-side rendered panels outside the React container.
		// All outputters get a fallback container so that third-party plugins
		// which extend a client-side rendered base class (e.g. QM_Output_Html_Logger)
		// still have their PHP output available for the PhpPanelFallback component.
		echo '<div id="query-monitor-fallbacks">';
		foreach ( $this->outputters as $id => $output ) {
			$html = $output->get_output();

			if ( '' !== $html ) {
				printf(
					"\n" . '<!-- Begin %1$s output -->' . "\n",
					esc_html( $id )
				);

				printf(
					"\n" . '<div class="qm-panel-container" id="qm-%1$s-container">' . "\n",
					esc_html( $id )
				);
				echo $html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				echo "\n" . '</div>' . "\n";

				printf(
					"\n" . '<!-- End %s output -->' . "\n",
					esc_html( $id )
				);
			}
		}
		echo '</div>' . "\n";

		// Output the empty container for React to mount into (shadow DOM host)
		printf(
			'<div id="query-monitor-container" data-css-url="%s"></div>',
			esc_url( $this->panel_css_url )
		);

		$this->after_output();

		if ( $switched_locale ) {
			self::restore_previous_locale();
		}

	}

	/**
	 * @return void
	 */
	protected function before_output() {
		global $wp_version;

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

		// Back-compat: derive 'id' and 'panel' from legacy 'href' entries.
		self::apply_panel_menu_back_compat( $this->panel_menu );

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

			if ( ( ! empty( $collector->concerned_filters ) || ! empty( $collector->concerned_actions ) ) && isset( $this->panel_menu[ $collector->id() ] ) ) {
				$count = count( $collector->concerned_filters ) + count( $collector->concerned_actions );
				$this->panel_menu[ $collector->id() ]['children'][ $collector->id . '-concerned_hooks' ] = array(
					'id' => $collector->id . '-concerned_hooks',
					'panel' => $collector->id . '-concerned_hooks',
					'title' => __( 'Hooks in Use', 'query-monitor' ),
					'count' => $count,
				);
			}
		}

		$data['overview'] = array(
			'enabled' => true,
			'data' => QM_Collectors::get( 'overview' )->get_data(),
		);
		$data['timeline'] = array(
			'enabled' => true,
			'data' => null,
		);

		/** @var WP_Locale $wp_locale */
		global $wp_locale;

		/**
		 * Filter whether to show the QM extended query information prompt.
		 *
		 * By default QM shows a prompt to install the QM db.php drop-in,
		 * this filter allows a dev to choose not to show the prompt.
		 *
		 * @since 2.9.0
		 *
		 * @param bool $show_prompt Whether to show the prompt.
		 */
		$show_extended_query_prompt = apply_filters( 'qm/show_extended_query_prompt', true );

		// Determine the reason for the extended query prompt
		$extended_query_prompt_reason = null;
		if ( $show_extended_query_prompt && ! class_exists( 'QM_DB', false ) ) {
			if ( file_exists( WP_CONTENT_DIR . '/db.php' ) ) {
				$extended_query_prompt_reason = 'conflict';
			} elseif ( defined( 'QM_DB_SYMLINK' ) && ! QM_DB_SYMLINK ) {
				$extended_query_prompt_reason = 'disabled';
			} else {
				$extended_query_prompt_reason = 'failed';
			}
		}

		$color_scheme = get_user_option( 'admin_color' );

		if ( $color_scheme !== 'fresh' ) {
			$color_scheme = version_compare( $wp_version, '7.0.0', '>=' ) ? 'modern' : 'fresh';
		}

		$json = array(
			'menu' => $this->js_admin_bar_menu(),
			'settings'    => array(
				'verified' => self::user_verified(),
				'extended_query_prompt_reason' => $extended_query_prompt_reason,
				'color_scheme' => $color_scheme,
			),
			'panel_menu'  => $this->panel_menu,
			'data'        => $data,
			'frames'      => QM_Frame_Registry::get_frames(),
			'l10n' => [
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'admin_url' => admin_url(),
				'auth_nonce' => [
					'on' => wp_create_nonce( 'qm-auth-on' ),
					'off' => wp_create_nonce( 'qm-auth-off' ),
				],
				'file_path_map' => QM_Output_Html::get_file_path_map(),
				'file_link_format' => QM_Output_Html::get_file_link_format(),
				'abspath' => QM_Util::normalize_path( ABSPATH ),
				'contentpath' => QM_Util::normalize_path( dirname( WP_CONTENT_DIR ) . '/' ),
			],
			'number_format' => $wp_locale->number_format,
			'locale_data' => self::get_script_locale_data(),
		);

		$this->output_assets();

		wp_print_inline_script_tag(
			sprintf(
				'var QueryMonitorData = %s;',
				json_encode( $json, JSON_UNESCAPED_SLASHES )
			),
			array(
				'id' => 'query-monitor-inline-data',
			)
		);
	}

	/**
	 * Back-compat: derive 'id' and 'panel' from legacy 'href' entries.
	 *
	 * Third-party plugins may register panel menu items with only an 'href'
	 * property. This method derives the 'id' and 'panel' values from the
	 * array key and 'href' respectively.
	 *
	 * @param array<string, mixed[]> $panel_menu
	 * @return void
	 */
	protected static function apply_panel_menu_back_compat( array &$panel_menu ): void {
		foreach ( $panel_menu as $id => &$item ) {
			if ( empty( $item['id'] ) ) {
				$item['id'] = $id;
			}
			if ( empty( $item['panel'] ) && ! empty( $item['href'] ) ) {
				$item['panel'] = preg_replace( '/^#qm-/', '', $item['href'] );
			}
			if ( ! empty( $item['children'] ) ) {
				foreach ( $item['children'] as &$child ) {
					if ( empty( $child['panel'] ) && ! empty( $child['href'] ) ) {
						$child['panel'] = preg_replace( '/^#qm-/', '', $child['href'] );
					}
				}
				unset( $child );
			}
		}
		unset( $item );
	}

	/**
	 * @return void
	 */
	protected function after_output() {
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
			esc_url( QueryMonitor::init()->plugin_url( 'assets/build/toolbar.css' ) ),
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
				$callback = QM_Util::determine_callback( $frame );

				if ( ! isset( $callback->name ) ) {
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

				$name = str_replace( '()', '(' . implode( ', ', $args ) . ')', $callback->name );

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

	/**
	 * Get the manifest data, detecting dev vs production mode.
	 *
	 * @return array<string, mixed> The manifest data.
	 * @throws Exception If no manifest is found.
	 */
	private static function get_manifest(): array {
		if ( self::$manifest === null ) {
			$build_dir = dirname( __DIR__ ) . '/assets/build';

			// Check for dev server manifest first (only exists when Vite dev server is running)
			$dev_manifest = $build_dir . '/vite-dev-server.json';
			if ( is_file( $dev_manifest ) && is_readable( $dev_manifest ) ) {
				/** @var array{origin: string}|null $data */
				$data = wp_json_file_decode( $dev_manifest, array( 'associative' => true ) );
				if ( $data ) {
					self::$is_dev = true;
					self::$manifest = $data;
				}
			}

			// Fall back to production assets
			if ( self::$manifest === null ) {
				if ( is_file( $build_dir . '/query-monitor.js' ) ) {
					self::$manifest = array(
						'assets/toolbar.css' => array(
							'file' => 'toolbar.css',
						),
						'assets/query-monitor.css' => array(
							'file' => 'query-monitor.css',
						),
						'src/index.tsx' => array(
							'file' => 'query-monitor.js',
						),
					);
				}
			}

			// Mark as checked but not found
			if ( self::$manifest === null ) {
				self::$manifest = false;
			}
		}

		if ( self::$manifest === false ) {
			throw new Exception( 'Query Monitor assets not built. Run `npm run build` or `npm run watch`.' );
		}

		return self::$manifest;
	}

	/**
	 * Admin notice for missing assets.
	 */
	public static function admin_notice_missing_assets(): void {
		try {
			self::get_manifest();
		} catch ( Exception $e ) {
			printf(
				'<div class="notice notice-error"><p>%s</p></div>',
				esc_html( $e->getMessage() )
			);
		}
	}

	/**
	 * Output all assets (CSS, JS, and localized data).
	 */
	private function output_assets(): void {
		try {
			$manifest = self::get_manifest();
		} catch ( Exception $e ) {
			printf(
				'<!-- QM Assets Error: %s -->' . "\n",
				esc_html( $e->getMessage() )
			);
			return;
		}

		if ( self::$is_dev ) {
			/** @var array{origin: string} $manifest */
			$this->output_dev_assets( $manifest );
		} else {
			/** @var array<string, array{file: string}> $manifest */
			$this->output_production_assets( $manifest );
		}
	}

	/**
	 * Output assets for development mode (Vite dev server).
	 *
	 * @param array{origin: string} $data Dev server manifest data.
	 */
	private function output_dev_assets( array $data ): void {
		$origin = $data['origin'];

		// Toolbar CSS (loaded on the page, outside shadow DOM)
		printf(
			'<link rel="stylesheet" href="%s">' . "\n",
			esc_url( $origin . '/assets/toolbar.css' )
		);

		// Panel CSS URL (passed to JS via data attribute, loaded inside shadow DOM)
		$this->panel_css_url = $origin . '/assets/query-monitor.css';

		// Vite client for HMR
		wp_print_script_tag( [
			'type' => 'module',
			'src' => esc_url( $origin . '/@vite/client' ),
		] );

		// Main JS module
		wp_print_script_tag( [
			'type' => 'module',
			'src' => esc_url( $origin . '/src/index.tsx' ),
		] );
	}

	/**
	 * Loads the script locale data for the bundled JS translations.
	 *
	 * This replicates what wp_set_script_translations() does, but without
	 * requiring a registered script handle.
	 *
	 * @return array<string, mixed>|null The locale data for the text domain, or null.
	 */
	private static function get_script_locale_data(): ?array {
		$locale = determine_locale();

		if ( 'en_US' === $locale ) {
			return null;
		}

		$relative  = 'assets/build/query-monitor.js';
		$file_base = 'query-monitor-' . $locale;
		$md5_file  = $file_base . '-' . md5( $relative ) . '.json';

		$locations = array(
			dirname( __DIR__ ) . '/languages/' . $md5_file,
			WP_LANG_DIR . '/plugins/' . $md5_file,
		);

		foreach ( $locations as $file ) {
			$json = load_script_translations( $file, 'query-monitor', 'query-monitor' );

			if ( $json ) {
				/** @var array{locale_data?: array<string, mixed>} */
				$translations = json_decode( $json, true );
				$locale_data  = $translations['locale_data']['query-monitor']
							?? $translations['locale_data']['messages']
							?? null;

				if ( $locale_data ) {
					$locale_data['']['domain'] = 'query-monitor';
					return $locale_data;
				}
			}
		}

		return null;
	}

	/**
	 * Output assets for production mode (built files).
	 *
	 * @param array<string, array{file: string}> $data Production manifest data.
	 */
	private function output_production_assets( array $data ): void {
		$base_url = $this->qm->plugin_url( 'assets/build' );

		// Toolbar CSS (loaded on the page, outside shadow DOM)
		$toolbar_file = $data['assets/toolbar.css']['file'] ?? null;
		if ( $toolbar_file ) {
			$url = add_query_arg(
				'ver',
				QM_VERSION,
				$base_url . '/' . $toolbar_file,
			);
			printf(
				'<link rel="stylesheet" href="%s">' . "\n",
				esc_url( $url )
			);
		}

		// Panel CSS URL (passed to JS via data attribute, loaded inside shadow DOM)
		$css_file = $data['assets/query-monitor.css']['file'] ?? null;
		if ( $css_file ) {
			$this->panel_css_url = add_query_arg(
				'ver',
				QM_VERSION,
				$base_url . '/' . $css_file,
			);
		}

		// JS
		$js_file = $data['src/index.tsx']['file'] ?? null;
		if ( $js_file ) {
			$url = add_query_arg(
				'ver',
				QM_VERSION,
				$base_url . '/' . $js_file,
			);
			wp_print_script_tag( [
				'type' => 'module',
				'src' => esc_url( $url ),
			] );
		}
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
