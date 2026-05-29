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

}
