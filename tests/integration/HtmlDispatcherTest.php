<?php declare(strict_types = 1);

namespace QM\Tests;

class HtmlDispatcherTest extends Test {

	public function testIdDerivedFromArrayKeyWhenMissing(): void {
		$menu = array(
			'my-panel' => array(
				'title' => 'My Panel',
				'href'  => '#qm-my-panel',
			),
		);

		Supports\TestHtmlDispatcher::run_back_compat( $menu );

		self::assertSame( 'my-panel', $menu['my-panel']['id'] );
	}

	public function testIdNotOverwrittenWhenAlreadySet(): void {
		$menu = array(
			'my-panel' => array(
				'id'    => 'explicit-id',
				'title' => 'My Panel',
				'href'  => '#qm-my-panel',
			),
		);

		Supports\TestHtmlDispatcher::run_back_compat( $menu );

		self::assertSame( 'explicit-id', $menu['my-panel']['id'] );
	}

	public function testPanelDerivedFromHrefWithQmPrefix(): void {
		$menu = array(
			'my-panel' => array(
				'title' => 'My Panel',
				'href'  => '#qm-my-panel',
			),
		);

		Supports\TestHtmlDispatcher::run_back_compat( $menu );

		self::assertSame( 'my-panel', $menu['my-panel']['panel'] );
	}

	public function testPanelDerivedFromHrefWithoutQmPrefix(): void {
		$menu = array(
			'my-panel' => array(
				'title' => 'My Panel',
				'href'  => '#my-panel',
			),
		);

		Supports\TestHtmlDispatcher::run_back_compat( $menu );

		self::assertSame( '#my-panel', $menu['my-panel']['panel'] );
	}

	public function testPanelNotOverwrittenWhenAlreadySet(): void {
		$menu = array(
			'my-panel' => array(
				'title' => 'My Panel',
				'href'  => '#qm-my-panel',
				'panel' => 'explicit-panel',
			),
		);

		Supports\TestHtmlDispatcher::run_back_compat( $menu );

		self::assertSame( 'explicit-panel', $menu['my-panel']['panel'] );
	}

	public function testPanelNotSetWhenHrefMissing(): void {
		$menu = array(
			'my-panel' => array(
				'title' => 'My Panel',
			),
		);

		Supports\TestHtmlDispatcher::run_back_compat( $menu );

		self::assertArrayNotHasKey( 'panel', $menu['my-panel'] );
	}

	public function testChildPanelDerivedFromHref(): void {
		$menu = array(
			'my-panel' => array(
				'title'    => 'My Panel',
				'href'     => '#qm-my-panel',
				'children' => array(
					'my-child' => array(
						'title' => 'My Child',
						'href'  => '#qm-my-child',
					),
				),
			),
		);

		Supports\TestHtmlDispatcher::run_back_compat( $menu );

		self::assertSame( 'my-child', $menu['my-panel']['children']['my-child']['panel'] );
	}

	public function testChildPanelNotOverwrittenWhenAlreadySet(): void {
		$menu = array(
			'my-panel' => array(
				'title'    => 'My Panel',
				'href'     => '#qm-my-panel',
				'children' => array(
					'my-child' => array(
						'title' => 'My Child',
						'href'  => '#qm-my-child',
						'panel' => 'explicit-child-panel',
					),
				),
			),
		);

		Supports\TestHtmlDispatcher::run_back_compat( $menu );

		self::assertSame( 'explicit-child-panel', $menu['my-panel']['children']['my-child']['panel'] );
	}

	public function testChildPanelNotSetWhenHrefMissing(): void {
		$menu = array(
			'my-panel' => array(
				'title'    => 'My Panel',
				'href'     => '#qm-my-panel',
				'children' => array(
					'my-child' => array(
						'title' => 'My Child',
					),
				),
			),
		);

		Supports\TestHtmlDispatcher::run_back_compat( $menu );

		self::assertArrayNotHasKey( 'panel', $menu['my-panel']['children']['my-child'] );
	}

	public function testChildWithNumericKeyAndExistingId(): void {
		// Mirrors the pattern used by third-party plugins such as humanmade/aws-xray,
		// which append children via [] (numeric key) with an 'id' already set but no 'panel'.
		$menu = array(
			'aws-xray' => array(
				'title'    => 'XRay',
				'href'     => '#qm-aws-xray',
				'children' => array(
					array(
						'title' => 'Flamegraph',
						'id'    => 'query-monitor-aws-xray-flamegraph',
						'href'  => '#qm-aws-xray-flamegraph',
					),
				),
			),
		);

		Supports\TestHtmlDispatcher::run_back_compat( $menu );

		$child = $menu['aws-xray']['children'][0];

		// 'id' must not be overwritten.
		self::assertSame( 'query-monitor-aws-xray-flamegraph', $child['id'] );
		// 'panel' must be derived from 'href' with '#qm-' stripped.
		self::assertSame( 'aws-xray-flamegraph', $child['panel'] );
	}

	public function testMultipleItemsProcessedCorrectly(): void {
		$menu = array(
			'panel-one' => array(
				'title' => 'Panel One',
				'href'  => '#qm-panel-one',
			),
			'panel-two' => array(
				'id'    => 'panel-two',
				'title' => 'Panel Two',
				'panel' => 'panel-two',
			),
		);

		Supports\TestHtmlDispatcher::run_back_compat( $menu );

		self::assertSame( 'panel-one', $menu['panel-one']['id'] );
		self::assertSame( 'panel-one', $menu['panel-one']['panel'] );
		self::assertSame( 'panel-two', $menu['panel-two']['id'] );
		self::assertSame( 'panel-two', $menu['panel-two']['panel'] );
	}

	public function testScriptTagInBlockContentDoesNotBreakInlineData(): void {
		global $_wp_current_template_content;

		$inner_html = '<script src="https://player.vimeo.com/api/player.js"></script>';

		// Without unfiltered_html the script tag is stripped from the post content.
		kses_remove_filters();

		$post_id = self::factory()->post->create( array(
			'post_content' => "<!-- wp:html -->\n{$inner_html}\n<!-- /wp:html -->",
		) );

		$_wp_current_template_content = null;
		$url = get_permalink( $post_id );
		self::assertIsString( $url );
		$this->go_to( $url );

		$dispatcher = \QM_Dispatchers::get( 'html' );
		self::assertInstanceOf( \QM_Dispatcher_Html::class, $dispatcher );

		// The collectors are ceased during bootstrap, which marks them as processed.
		$collectors = \QM_Collectors::init();
		$processed = new \ReflectionProperty( $collectors, 'processed' );

		if ( \PHP_VERSION_ID < 80100 ) {
			$processed->setAccessible( true );
		}

		$processed->setValue( $collectors, false );

		try {
			$output = Supports\TestHtmlDispatcher::run_before_output( $dispatcher );
		} finally {
			$processed->setValue( $collectors, true );
		}

		$processor = new \WP_HTML_Tag_Processor( $output );
		$script = null;

		while ( $processor->next_tag( array( 'tag_name' => 'script' ) ) ) {
			if ( 'query-monitor-inline-data' === $processor->get_attribute( 'id' ) ) {
				// The integration tests run on the latest WordPress version.
				// @phpstan-ignore WPCompat.methodNotAvailable
				$script = $processor->get_modifiable_text();
				break;
			}
		}

		self::assertIsString( $script );

		self::assertStringNotContainsStringIgnoringCase( '</script', $script );
		// WordPress 7.1+ escapes `</script` itself, so assert that QM's own JSON encoding contains no tags.
		self::assertStringNotContainsString( '<', $script );

		self::assertSame( 1, preg_match( '/^\s*var QueryMonitorData = (.+);\s*$/s', $script, $matches ) );

		/** @var array{data: array{block_editor: array{data: array{post_blocks: list<array{innerHTML: string}>}}}} $data */
		$data = json_decode( $matches[1], true, 512, JSON_THROW_ON_ERROR );

		self::assertSame( $inner_html, $data['data']['block_editor']['data']['post_blocks'][0]['innerHTML'] );
	}

}
