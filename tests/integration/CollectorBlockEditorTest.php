<?php declare(strict_types = 1);

namespace QM\Tests;

class CollectorBlockEditorTest extends Test {

	/**
	 * @var \QM_Collector_Block_Editor
	 */
	public $collector;

	#[\Override]
	public function set_up(): void {
		parent::set_up();

		$this->collector = new \QM_Collector_Block_Editor();
	}

	#[\Override]
	public function tear_down(): void {
		$this->collector->tear_down();

		parent::tear_down();
	}

	/**
	 * Simulates the WordPress filter call sequence for rendering a single
	 * leaf block (no inner blocks).
	 *
	 * @param mixed[] $block
	 * @param mixed[] $context
	 */
	private function simulateRenderBlock( array $block, array $context = array() ): void {
		$this->collector->filter_render_block_data( $block );
		$this->collector->filter_render_block_context( $context, $block );
		$this->collector->filter_render_block( '', $block );
	}

	/**
	 * Simulates the WordPress filter call sequence for rendering a block
	 * that has inner blocks. The caller provides a callback which should
	 * render the inner blocks by calling simulateRenderBlock or
	 * simulateRenderParentBlock.
	 *
	 * @param mixed[] $block
	 * @param mixed[] $context
	 * @param callable $renderInnerBlocks
	 */
	private function simulateRenderParentBlock( array $block, array $context, callable $renderInnerBlocks ): void {
		$this->collector->filter_render_block_data( $block );
		$this->collector->filter_render_block_context( $context, $block );

		$renderInnerBlocks();

		$this->collector->filter_render_block( '', $block );
	}

	/**
	 * Helper to create a minimal parsed block array.
	 *
	 * @param string $name
	 * @param mixed[] $attrs
	 * @param mixed[] $innerBlocks
	 * @return mixed[]
	 */
	private function makeBlock( string $name, array $attrs = array(), array $innerBlocks = array() ): array {
		return array(
			'blockName' => $name,
			'attrs' => $attrs,
			'innerBlocks' => $innerBlocks,
			'innerHTML' => '<p>test</p>',
			'innerContent' => array( '<p>test</p>' ),
		);
	}

	/**
	 * Returns the block_timing array from the collector via reflection.
	 *
	 * @return array<int, \QM_Timer|false>
	 */
	private function getBlockTimingArray(): array {
		$ref = new \ReflectionProperty( $this->collector, 'block_timing' );
		( \PHP_VERSION_ID < 80100 ) && $ref->setAccessible( true );
		return $ref->getValue( $this->collector );
	}

	function testLeafBlocksGetDistinctTimerInstances(): void {
		$block_a = $this->makeBlock( 'core/paragraph' );
		$block_b = $this->makeBlock( 'core/heading' );

		$this->simulateRenderBlock( $block_a );
		$this->simulateRenderBlock( $block_b );

		$timers = $this->getBlockTimingArray();

		self::assertCount( 2, $timers );
		self::assertInstanceOf( \QM_Timer::class, $timers[0] );
		self::assertInstanceOf( \QM_Timer::class, $timers[1] );
		self::assertNotSame( $timers[0], $timers[1] );
	}

	function testNestedBlocksGetDistinctTimerInstances(): void {
		$child_a = $this->makeBlock( 'core/paragraph' );
		$child_b = $this->makeBlock( 'core/heading' );
		$parent = $this->makeBlock( 'core/group', array(), array( $child_a, $child_b ) );

		// Simulate the rendering order WordPress uses:
		// parent start -> child_a start -> child_a end -> child_b start -> child_b end -> parent end
		$this->simulateRenderParentBlock( $parent, array(), function () use ( $child_a, $child_b ) {
			$this->simulateRenderBlock( $child_a );
			$this->simulateRenderBlock( $child_b );
		} );

		$timers = $this->getBlockTimingArray();

		// Timers are pushed in pre-order: parent, child_a, child_b.
		self::assertCount( 3, $timers );
		self::assertInstanceOf( \QM_Timer::class, $timers[0] );
		self::assertInstanceOf( \QM_Timer::class, $timers[1] );
		self::assertInstanceOf( \QM_Timer::class, $timers[2] );

		// Each block must have its own distinct timer instance.
		self::assertNotSame( $timers[0], $timers[1] );
		self::assertNotSame( $timers[0], $timers[2] );
		self::assertNotSame( $timers[1], $timers[2] );
	}

	function testNestedBlocksProcessCorrectly(): void {
		$child_a = $this->makeBlock( 'core/paragraph' );
		$child_b = $this->makeBlock( 'core/heading' );
		$parent = $this->makeBlock( 'core/group', array(), array( $child_a, $child_b ) );

		$this->simulateRenderParentBlock( $parent, array(), function () use ( $child_a, $child_b ) {
			$this->simulateRenderBlock( $child_a );
			$this->simulateRenderBlock( $child_b );
		} );

		$post_id = self::factory()->post->create( array(
			'post_content' => '<!-- wp:group --><div class="wp-block-group"><!-- wp:paragraph --><p>test</p><!-- /wp:paragraph --><!-- wp:heading --><p>test</p><!-- /wp:heading --></div><!-- /wp:group -->',
		) );

		$this->go_to( (string) get_permalink( $post_id ) );

		$this->collector->process();
		$data = $this->collector->get_data();

		self::assertCount( 1, $data->post_blocks );

		$group = $data->post_blocks[0];

		self::assertSame( 'core/group', $group->blockName );
		self::assertIsFloat( $group->timing );
		self::assertCount( 2, $group->innerBlocks );

		self::assertSame( 'core/paragraph', $group->innerBlocks[0]->blockName );
		self::assertSame( 'core/heading', $group->innerBlocks[1]->blockName );
		self::assertIsFloat( $group->innerBlocks[0]->timing );
		self::assertIsFloat( $group->innerBlocks[1]->timing );

		// The parent timing must be greater than or equal to each child
		// because it encompasses the rendering of its children.
		self::assertGreaterThanOrEqual( $group->innerBlocks[0]->timing, $group->timing );
		self::assertGreaterThanOrEqual( $group->innerBlocks[1]->timing, $group->timing );
	}

	function testDeeplyNestedBlocksGetDistinctTimerInstances(): void {
		$leaf = $this->makeBlock( 'core/paragraph' );
		$mid = $this->makeBlock( 'core/group', array(), array( $leaf ) );
		$root = $this->makeBlock( 'core/group', array( 'tagName' => 'main' ), array( $mid ) );

		// Simulate: root start -> mid start -> leaf start -> leaf end -> mid end -> root end
		$this->simulateRenderParentBlock( $root, array(), function () use ( $mid, $leaf ) {
			$this->simulateRenderParentBlock( $mid, array(), function () use ( $leaf ) {
				$this->simulateRenderBlock( $leaf );
			} );
		} );

		$timers = $this->getBlockTimingArray();

		// Timers are pushed in pre-order: root, mid, leaf.
		self::assertCount( 3, $timers );
		self::assertInstanceOf( \QM_Timer::class, $timers[0] );
		self::assertInstanceOf( \QM_Timer::class, $timers[1] );
		self::assertInstanceOf( \QM_Timer::class, $timers[2] );

		// Each level must have its own distinct timer instance.
		self::assertNotSame( $timers[0], $timers[1] );
		self::assertNotSame( $timers[0], $timers[2] );
		self::assertNotSame( $timers[1], $timers[2] );
	}

	function testTimingContextAlignmentWithSiblingGroups(): void {
		$child_a = $this->makeBlock( 'core/paragraph' );
		$child_b = $this->makeBlock( 'core/heading' );
		$group_a = $this->makeBlock( 'core/group', array(), array( $child_a ) );
		$group_b = $this->makeBlock( 'core/group', array(), array( $child_b ) );

		$ctx_a = array( 'groupId' => 'a' );
		$ctx_b = array( 'groupId' => 'b' );

		$this->simulateRenderParentBlock( $group_a, $ctx_a, function () use ( $child_a ) {
			$this->simulateRenderBlock( $child_a );
		} );

		$this->simulateRenderParentBlock( $group_b, $ctx_b, function () use ( $child_b ) {
			$this->simulateRenderBlock( $child_b );
		} );

		$post_id = self::factory()->post->create( array(
			'post_content' => '<!-- wp:group --><div class="wp-block-group"><!-- wp:paragraph --><p>test</p><!-- /wp:paragraph --></div><!-- /wp:group --><!-- wp:group --><div class="wp-block-group"><!-- wp:heading --><p>test</p><!-- /wp:heading --></div><!-- /wp:group -->',
		) );

		$this->go_to( (string) get_permalink( $post_id ) );

		$this->collector->process();
		$data = $this->collector->get_data();

		self::assertCount( 2, $data->post_blocks );

		// Verify contexts are correctly aligned with the right blocks.
		self::assertSame( $ctx_a, $data->post_blocks[0]->context );
		self::assertSame( $ctx_b, $data->post_blocks[1]->context );

		// Verify all blocks have valid float timings.
		self::assertIsFloat( $data->post_blocks[0]->timing );
		self::assertIsFloat( $data->post_blocks[0]->innerBlocks[0]->timing );
		self::assertIsFloat( $data->post_blocks[1]->timing );
		self::assertIsFloat( $data->post_blocks[1]->innerBlocks[0]->timing );
	}
}
