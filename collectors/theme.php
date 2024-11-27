<?php declare(strict_types = 1);
/**
 * Template and theme collector.
 *
 * @package query-monitor
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @extends QM_DataCollector<QM_Data_Theme>
 */
class QM_Collector_Theme extends QM_DataCollector {

	/**
	 * @var string
	 */
	public $id = 'response';

	/**
	 * @var bool
	 */
	protected $got_theme_compat = false;

	/**
	 * @var array<int, mixed>
	 */
	protected $requested_template_parts = array();

	/**
	 * @var array<int, mixed>
	 */
	protected $requested_template_part_posts = array();

	/**
	 * @var array<int, mixed>
	 */
	protected $requested_template_part_files = array();

	/**
	 * @var array<int, mixed>
	 */
	protected $requested_template_part_nopes = array();

	/**
	 * @var ?WP_Block_Template
	 */
	protected $block_template = null;

	public function get_storage(): QM_Data {
		return new QM_Data_Theme();
	}

	/**
	 * @return void
	 */
	public function set_up() {
		parent::set_up();

		add_filter( 'body_class', array( $this, 'filter_body_class' ), 9999 );
		add_filter( 'timber/output', array( $this, 'filter_timber_output' ), 9999, 3 );
		add_action( 'template_redirect', array( $this, 'action_template_redirect' ) );
		add_action( 'get_template_part', array( $this, 'action_get_template_part' ), 10, 3 );
		add_action( 'get_header', array( $this, 'action_get_position' ) );
		add_action( 'get_sidebar', array( $this, 'action_get_position' ) );
		add_action( 'get_footer', array( $this, 'action_get_position' ) );
		add_action( 'render_block_core_template_part_post', array( $this, 'action_render_block_core_template_part_post' ), 10, 3 );
		add_action( 'render_block_core_template_part_file', array( $this, 'action_render_block_core_template_part_file' ), 10, 3 );
		add_action( 'render_block_core_template_part_none', array( $this, 'action_render_block_core_template_part_none' ), 10, 3 );
		add_action( 'gutenberg_render_block_core_template_part_post', array( $this, 'action_render_block_core_template_part_post' ), 10, 3 );
		add_action( 'gutenberg_render_block_core_template_part_file', array( $this, 'action_render_block_core_template_part_file' ), 10, 3 );
		add_action( 'gutenberg_render_block_core_template_part_none', array( $this, 'action_render_block_core_template_part_none' ), 10, 3 );
	}

	/**
	 * @return void
	 */
	public function tear_down() {
		remove_filter( 'body_class', array( $this, 'filter_body_class' ), 9999 );
		remove_filter( 'timber/output', array( $this, 'filter_timber_output' ), 9999 );
		remove_action( 'template_redirect', array( $this, 'action_template_redirect' ) );
		remove_action( 'get_template_part', array( $this, 'action_get_template_part' ), 10 );
		remove_action( 'get_header', array( $this, 'action_get_position' ) );
		remove_action( 'get_sidebar', array( $this, 'action_get_position' ) );
		remove_action( 'get_footer', array( $this, 'action_get_position' ) );
		remove_action( 'render_block_core_template_part_post', array( $this, 'action_render_block_core_template_part_post' ), 10 );
		remove_action( 'render_block_core_template_part_file', array( $this, 'action_render_block_core_template_part_file' ), 10 );
		remove_action( 'render_block_core_template_part_none', array( $this, 'action_render_block_core_template_part_none' ), 10 );
		remove_action( 'gutenberg_render_block_core_template_part_post', array( $this, 'action_render_block_core_template_part_post' ), 10 );
		remove_action( 'gutenberg_render_block_core_template_part_file', array( $this, 'action_render_block_core_template_part_file' ), 10 );
		remove_action( 'gutenberg_render_block_core_template_part_none', array( $this, 'action_render_block_core_template_part_none' ), 10 );

		parent::tear_down();
	}

	/**
	 * Fires before the header/sidebar/footer template file is loaded.
	 *
	 * @param string|null $name Name of the specific file to use. Null for the default.
	 * @return void
	 */
	public function action_get_position( $name ) {
		$filter = current_filter();
		$trace = new QM_Backtrace( array(
			'ignore_hook' => array(
				$filter => true,
			),
		) );

		$position = str_replace( 'get_', '', $filter );
		$templates = array();
		if ( '' !== (string) $name ) {
			$templates[] = "{$position}-{$name}.php";
		}

		$templates[] = "{$position}.php";

		$data = array(
			'slug' => $position,
			'name' => $name,
			'templates' => $templates,
			'caller' => $trace->get_caller(),
		);

		$this->requested_template_parts[] = $data;
	}

	/**
	 * @return array<int, string>
	 */
	public function get_concerned_actions() {
		return array(
			'template_redirect',
		);
	}

	/**
	 * @return array<int, string>
	 */
	public function get_concerned_filters() {
		$filters = array(
			'stylesheet',
			'stylesheet_directory',
			'template',
			'template_directory',
			'template_include',
		);

		foreach ( self::get_query_filter_names() as $filter ) {
			$filters[] = $filter;
			$filters[] = "{$filter}_hierarchy";
		}

		return $filters;
	}

	/**
	 * @return array<int, string>
	 */
	public function get_concerned_options() {
		return array(
			'stylesheet',
			'template',
		);
	}

	/**
	 * @return array<int|string, string>
	 */
	public static function get_query_template_names() {
		$names = array();

		$names['embed'] = 'is_embed';
		$names['404'] = 'is_404';
		$names['search'] = 'is_search';
		$names['front_page'] = 'is_front_page';
		$names['home'] = 'is_home';
		$names['privacy_policy'] = 'is_privacy_policy';
		$names['post_type_archive'] = 'is_post_type_archive';
		$names['taxonomy'] = 'is_tax';
		$names['attachment'] = 'is_attachment';
		$names['single'] = 'is_single';
		$names['page'] = 'is_page';
		$names['singular'] = 'is_singular';
		$names['category'] = 'is_category';
		$names['tag'] = 'is_tag';
		$names['author'] = 'is_author';
		$names['date'] = 'is_date';
		$names['archive'] = 'is_archive';
		$names['index'] = '__return_true';

		return $names;
	}

	/**
	 * @return array<int|string, string>
	 */
	public static function get_query_filter_names() {
		$names = array();

		$names['embed'] = 'embed_template';
		$names['404'] = '404_template';
		$names['search'] = 'search_template';
		$names['front_page'] = 'frontpage_template';
		$names['home'] = 'home_template';
		$names['privacy_policy'] = 'privacypolicy_template';
		$names['taxonomy'] = 'taxonomy_template';
		$names['attachment'] = 'attachment_template';
		$names['single'] = 'single_template';
		$names['page'] = 'page_template';
		$names['singular'] = 'singular_template';
		$names['category'] = 'category_template';
		$names['tag'] = 'tag_template';
		$names['author'] = 'author_template';
		$names['date'] = 'date_template';
		$names['archive'] = 'archive_template';
		$names['index'] = 'index_template';

		return $names;
	}

	/**
	 * @return void
	 */
	public function action_template_redirect() {
		add_filter( 'template_include', array( $this, 'filter_template_include' ), PHP_INT_MAX );

		foreach ( self::get_query_template_names() as $template => $conditional ) {
			// If a matching theme-compat file is found, further conditional checks won't occur in template-loader.php
			if ( $this->got_theme_compat ) {
				break;
			}

			$get_template = "get_{$template}_template";

			if ( function_exists( $conditional ) && function_exists( $get_template ) && call_user_func( $conditional ) ) {
				$filter = str_replace( '_', '', "{$template}" );
				add_filter( "{$filter}_template_hierarchy", array( $this, 'filter_template_hierarchy' ), PHP_INT_MAX );
				add_filter( "{$filter}_template", array( $this, 'filter_template' ), PHP_INT_MAX, 3 );
				call_user_func( $get_template );
				remove_filter( "{$filter}_template_hierarchy", array( $this, 'filter_template_hierarchy' ), PHP_INT_MAX );
				remove_filter( "{$filter}_template", array( $this, 'filter_template' ), PHP_INT_MAX );
			}
		}
	}

	/**
	 * Fires before a template part is loaded.
	 *
	 * @param string             $slug      The slug name for the generic template.
	 * @param string             $name      The name of the specialized template or an empty
	 *                                      string if there is none.
	 * @param array<int, string> $templates Array of template files to search for, in order.
	 * @return void
	 */
	public function action_get_template_part( $slug, $name, $templates ) {
		$part = compact( 'slug', 'name', 'templates' );

		$trace = new QM_Backtrace( array(
			'ignore_hook' => array(
				current_filter() => true,
			),
		) );

		$part['caller'] = $trace->get_caller();

		$this->requested_template_parts[] = $part;
	}

	/**
	 * Fires when a post is loaded for a template part block.
	 *
	 * @param string  $template_part_id
	 * @param mixed[] $attributes
	 * @param WP_Post $post
	 * @return void
	 */
	public function action_render_block_core_template_part_post( $template_part_id, $attributes, WP_Post $post ) {
		$part = array(
			'id' => $template_part_id,
			'attributes' => $attributes,
			'post' => $post->ID,
		);
		$this->requested_template_part_posts[] = $part;
	}

	/**
	 * Fires when a file is loaded for a template part block.
	 *
	 * @param string  $template_part_id
	 * @param mixed[] $attributes
	 * @param string  $template_part_file_path
	 * @return void
	 */
	public function action_render_block_core_template_part_file( $template_part_id, $attributes, $template_part_file_path ) {
		$part = array(
			'id' => $template_part_id,
			'attributes' => $attributes,
			'path' => $template_part_file_path,
		);
		$this->requested_template_part_files[] = $part;
	}

	/**
	 * Fires when neither a post nor file is found for a template part block.
	 *
	 * @param string  $template_part_id
	 * @param mixed[] $attributes
	 * @param string  $template_part_file_path
	 * @return void
	 */
	public function action_render_block_core_template_part_none( $template_part_id, $attributes, $template_part_file_path ) {
		$part = array(
			'id' => $template_part_id,
			'attributes' => $attributes,
			'path' => $template_part_file_path,
		);
		$this->requested_template_part_nopes[] = $part;
	}

	/**
	 * @param array<int, string> $templates
	 * @return array<int, string>
	 */
	public function filter_template_hierarchy( array $templates ) {
		if ( ! isset( $this->data->template_hierarchy ) ) {
			$this->data->template_hierarchy = array();
		}

		foreach ( $templates as $template_name ) {
			if ( file_exists( ABSPATH . WPINC . '/theme-compat/' . $template_name ) ) {
				$this->got_theme_compat = true;
				break;
			}
		}

		if ( wp_is_block_theme() ) {
			$block_theme_folders = get_block_theme_folders();
			foreach ( $templates as $template ) {
				if ( str_ends_with( $template, '.php' ) ) {
					// Standard PHP template, inject the HTML version:
					$this->data->template_hierarchy[] = $block_theme_folders['wp_template'] . '/' . str_replace( '.php', '.html', $template );
					$this->data->template_hierarchy[] = $template;
				} else {
					// Block theme custom template (eg. from `customTemplates` in theme.json), doesn't have a suffix:
					$this->data->template_hierarchy[] = $block_theme_folders['wp_template'] . '/' . $template . '.html';
				}
			}
		} else {
			$this->data->template_hierarchy = array_merge( $this->data->template_hierarchy, $templates );
		}

		return $templates;
	}

	/**
	 * @param string             $template  Path to the template. See locate_template().
	 * @param string             $type      Sanitized filename without extension.
	 * @param array<int, string> $templates A list of template candidates, in descending order of priority.
	 * @return string Full path to template file.
	 */
	public function filter_template( $template, $type, $templates ) {
		if ( $this->data->block_template instanceof \WP_Block_Template ) {
			return $template;
		}

		$block_template = self::wp_resolve_block_template( $type, $templates, $template );

		if ( $block_template ) {
			$this->data->block_template = $block_template;
		}

		return $template;
	}

	/**
	 * @param array<int, string> $class
	 * @return array<int, string>
	 */
	public function filter_body_class( array $class ) {
		$this->data->body_class = $class;
		return $class;
	}

	/**
	 * @param string $template_path
	 * @return string
	 */
	public function filter_template_include( $template_path ) {
		$this->data->template_path = $template_path;
		return $template_path;
	}

	/**
	 * @param mixed[] $output
	 * @param mixed $data
	 * @param string $file
	 * @return mixed[]
	 */
	public function filter_timber_output( $output, $data = null, $file = null ) {
		if ( $file ) {
			$this->data->timber_files[] = $file;
		}

		return $output;
	}

	/**
	 * @return void
	 */
	public function process() {

		$stylesheet_directory = QM_Util::standard_dir( get_stylesheet_directory() );
		$template_directory = QM_Util::standard_dir( get_template_directory() );
		$theme_directory = QM_Util::standard_dir( get_theme_root() );

		if ( isset( $this->data->template_hierarchy ) ) {
			$this->data->template_hierarchy = array_unique( $this->data->template_hierarchy );
		}

		if ( ! empty( $this->requested_template_parts ) ) {
			$this->data->template_parts = array();
			$this->data->theme_template_parts = array();
			$this->data->count_template_parts = array();

			foreach ( $this->requested_template_parts as $part ) {
				$file = locate_template( $part['templates'] );

				if ( ! $file ) {
					$this->data->unsuccessful_template_parts[] = $part;
					continue;
				}

				$file = QM_Util::standard_dir( $file );

				if ( isset( $this->data->count_template_parts[ $file ] ) ) {
					$this->data->count_template_parts[ $file ]++;
					continue;
				}

				$this->data->count_template_parts[ $file ] = 1;

				$filename = str_replace( array(
					$stylesheet_directory,
					$template_directory,
				), '', $file );

				$display = trim( $filename, '/' );
				$theme_display = trim( str_replace( $theme_directory, '', $file ), '/' );

				$this->data->template_parts[ $file ] = $display;
				$this->data->theme_template_parts[ $file ] = $theme_display;
			}
		}

		if (
			! empty( $this->requested_template_part_posts ) ||
			! empty( $this->requested_template_part_files ) ||
			! empty( $this->requested_template_part_nopes )
		) {
			$this->data->template_parts = array();
			$this->data->theme_template_parts = array();
			$this->data->count_template_parts = array();

			$posts = ! empty( $this->requested_template_part_posts ) ? $this->requested_template_part_posts : array();
			$files = ! empty( $this->requested_template_part_files ) ? $this->requested_template_part_files : array();
			$nopes = ! empty( $this->requested_template_part_nopes ) ? $this->requested_template_part_nopes : array();

			$all = array_merge( $posts, $files, $nopes );

			foreach ( $all as $part ) {
				$file = isset( $part['path'] ) ? QM_Util::standard_dir( $part['path'] ) : $part['post'];

				if ( isset( $this->data->count_template_parts[ $file ] ) ) {
					$this->data->count_template_parts[ $file ]++;
					continue;
				}

				$this->data->count_template_parts[ $file ] = 1;

				if ( isset( $part['post'] ) ) {
					$display = $part['id'];
					$theme_display = $display;
				} else {
					$filename = str_replace( array(
						$stylesheet_directory,
						$template_directory,
					), '', $file );

					$display = trim( $filename, '/' );
					$theme_display = trim( str_replace( $theme_directory, '', $file ), '/' );
				}

				$this->data->template_parts[ $file ] = $display;
				$this->data->theme_template_parts[ $file ] = $theme_display;
			}
		}

		if ( ! empty( $this->data->template_path ) ) {
			$template_path = QM_Util::standard_dir( $this->data->template_path );
			$template_file = str_replace( array( $stylesheet_directory, $template_directory, ABSPATH ), '', $template_path );
			$template_file = ltrim( $template_file, '/' );
			$theme_template_file = str_replace( array( $theme_directory, ABSPATH ), '', $template_path );
			$theme_template_file = ltrim( $theme_template_file, '/' );

			$this->data->template_path = $template_path;
			$this->data->template_file = $template_file;
			$this->data->theme_template_file = $theme_template_file;
		}

		$this->data->stylesheet = get_stylesheet();
		$this->data->template = get_template();
		$this->data->is_child_theme = ( $this->data->stylesheet !== $this->data->template );
		$this->data->theme_dirs = array(
			$this->data->stylesheet => $stylesheet_directory,
			$this->data->template => $template_directory,
		);

		$this->data->theme_folders = get_block_theme_folders();

		$stylesheet_theme_json = $stylesheet_directory . '/theme.json';
		$template_theme_json = $template_directory . '/theme.json';

		if ( is_readable( $stylesheet_theme_json ) ) {
			$this->data->stylesheet_theme_json = $stylesheet_theme_json;
		}

		if ( is_readable( $template_theme_json ) ) {
			$this->data->template_theme_json = $template_theme_json;
		}

		if ( isset( $this->data->body_class ) ) {
			asort( $this->data->body_class );
		}

	}

	/**
	 * @param string             $template_type      The current template type.
	 * @param array<int, string> $template_hierarchy The current template hierarchy, ordered by priority.
	 * @param string             $fallback_template  A PHP fallback template to use if no matching block template is found.
	 * @return WP_Block_Template|null template A template object, or null if none could be found.
	 */
	protected static function wp_resolve_block_template( $template_type, $template_hierarchy, $fallback_template ) {
		if ( ! current_theme_supports( 'block-templates' ) ) {
			return null;
		}

		return resolve_block_template( $template_type, $template_hierarchy, $fallback_template );
	}
}

/**
 * @param array<string, QM_Collector> $collectors
 * @param QueryMonitor $qm
 * @return array<string, QM_Collector>
 */
function register_qm_collector_theme( array $collectors, QueryMonitor $qm ) {
	$collectors['response'] = new QM_Collector_Theme();
	return $collectors;
}

if ( ! is_admin() ) {
	add_filter( 'qm/collectors', 'register_qm_collector_theme', 10, 2 );
}
