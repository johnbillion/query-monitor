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

	function testLeafBlocksGetIndependentTimings(): void {
		$block_a = $this->makeBlock( 'core/paragraph' );
		$block_b = $this->makeBlock( 'core/heading' );

		$this->simulateRenderBlock( $block_a );
		$this->simulateRenderBlock( $block_b );

		$post_id = self::factory()->post->create( array(
			'post_content' => '<!-- wp:paragraph --><p>test</p><!-- /wp:paragraph --><!-- wp:heading --><p>test</p><!-- /wp:heading -->',
		) );

		$this->go_to( get_permalink( $post_id ) );

		$this->collector->process();
		$data = $this->collector->get_data();

		self::assertCount( 2, $data->post_blocks );
		self::assertIsFloat( $data->post_blocks[0]->timing );
		self::assertIsFloat( $data->post_blocks[1]->timing );
		self::assertNotSame( $data->post_blocks[0]->timing, $data->post_blocks[1]->timing );
	}

	function testNestedBlocksGetIndependentTimings(): void {
		$child_a = $this->makeBlock( 'core/paragraph' );
		$child_b = $this->makeBlock( 'core/heading' );
		$parent = $this->makeBlock( 'core/group', array(), array( $child_a, $child_b ) );

		// Simulate the rendering order WordPress uses:
		// parent start -> child_a start -> child_a end -> child_b start -> child_b end -> parent end
		$this->simulateRenderParentBlock( $parent, array(), function () use ( $child_a, $child_b ) {
			$this->simulateRenderBlock( $child_a );
			$this->simulateRenderBlock( $child_b );
		} );

		$post_id = self::factory()->post->create( array(
			'post_content' => '<!-- wp:group --><div class="wp-block-group"><!-- wp:paragraph --><p>test</p><!-- /wp:paragraph --><!-- wp:heading --><p>test</p><!-- /wp:heading --></div><!-- /wp:group -->',
		) );

		$this->go_to( get_permalink( $post_id ) );

		$this->collector->process();
		$data = $this->collector->get_data();

		self::assertCount( 1, $data->post_blocks );

		$group = $data->post_blocks[0];

		self::assertSame( 'core/group', $group->blockName );
		self::assertIsFloat( $group->timing );
		self::assertCount( 2, $group->innerBlocks );

		$inner_a = $group->innerBlocks[0];
		$inner_b = $group->innerBlocks[1];

		self::assertSame( 'core/paragraph', $inner_a->blockName );
		self::assertSame( 'core/heading', $inner_b->blockName );
		self::assertIsFloat( $inner_a->timing );
		self::assertIsFloat( $inner_b->timing );

		// The parent timing must be different from the children.
		self::assertNotSame( $group->timing, $inner_a->timing );
		self::assertNotSame( $group->timing, $inner_b->timing );

		// The children must have different timings from each other.
		self::assertNotSame( $inner_a->timing, $inner_b->timing );

		// The parent timing must be greater than or equal to each child
		// because it encompasses the rendering of its children.
		self::assertGreaterThanOrEqual( $inner_a->timing, $group->timing );
		self::assertGreaterThanOrEqual( $inner_b->timing, $group->timing );
	}

	function testDeeplyNestedBlocksGetIndependentTimings(): void {
		$leaf = $this->makeBlock( 'core/paragraph' );
		$mid = $this->makeBlock( 'core/group', array(), array( $leaf ) );
		$root = $this->makeBlock( 'core/group', array( 'tagName' => 'main' ), array( $mid ) );

		// Simulate: root start -> mid start -> leaf start -> leaf end -> mid end -> root end
		$this->simulateRenderParentBlock( $root, array(), function () use ( $mid, $leaf ) {
			$this->simulateRenderParentBlock( $mid, array(), function () use ( $leaf ) {
				$this->simulateRenderBlock( $leaf );
			} );
		} );

		$post_id = self::factory()->post->create( array(
			'post_content' => '<!-- wp:group {"tagName":"main"} --><main class="wp-block-group"><!-- wp:group --><div class="wp-block-group"><!-- wp:paragraph --><p>test</p><!-- /wp:paragraph --></div><!-- /wp:group --></main><!-- /wp:group -->',
		) );

		$this->go_to( get_permalink( $post_id ) );

		$this->collector->process();
		$data = $this->collector->get_data();

		self::assertCount( 1, $data->post_blocks );

		$root_block = $data->post_blocks[0];
		$mid_block = $root_block->innerBlocks[0];
		$leaf_block = $mid_block->innerBlocks[0];

		self::assertSame( 'core/group', $root_block->blockName );
		self::assertSame( 'core/group', $mid_block->blockName );
		self::assertSame( 'core/paragraph', $leaf_block->blockName );

		// All three blocks must have independent timings.
		self::assertIsFloat( $root_block->timing );
		self::assertIsFloat( $mid_block->timing );
		self::assertIsFloat( $leaf_block->timing );
		self::assertNotSame( $root_block->timing, $mid_block->timing );
		self::assertNotSame( $root_block->timing, $leaf_block->timing );
		self::assertNotSame( $mid_block->timing, $leaf_block->timing );
	}

	function testParentWithMultipleChildrenAllHaveTimings(): void {
		$child_a = $this->makeBlock( 'core/paragraph' );
		$child_b = $this->makeBlock( 'core/heading' );
		$child_c = $this->makeBlock( 'core/image' );
		$parent = $this->makeBlock( 'core/group', array(), array( $child_a, $child_b, $child_c ) );

		$this->simulateRenderParentBlock( $parent, array(), function () use ( $child_a, $child_b, $child_c ) {
			$this->simulateRenderBlock( $child_a );
			$this->simulateRenderBlock( $child_b );
			$this->simulateRenderBlock( $child_c );
		} );

		$post_id = self::factory()->post->create( array(
			'post_content' => '<!-- wp:group --><div class="wp-block-group"><!-- wp:paragraph --><p>test</p><!-- /wp:paragraph --><!-- wp:heading --><p>test</p><!-- /wp:heading --><!-- wp:image --><p>test</p><!-- /wp:image --></div><!-- /wp:group -->',
		) );

		$this->go_to( get_permalink( $post_id ) );

		$this->collector->process();
		$data = $this->collector->get_data();

		$group = $data->post_blocks[0];

		self::assertCount( 3, $group->innerBlocks );

		// All blocks must have valid float timings.
		self::assertIsFloat( $group->timing );
		self::assertIsFloat( $group->innerBlocks[0]->timing );
		self::assertIsFloat( $group->innerBlocks[1]->timing );
		self::assertIsFloat( $group->innerBlocks[2]->timing );

		// Parent timing must encompass children.
		self::assertGreaterThanOrEqual( $group->innerBlocks[0]->timing, $group->timing );
		self::assertGreaterThanOrEqual( $group->innerBlocks[1]->timing, $group->timing );
		self::assertGreaterThanOrEqual( $group->innerBlocks[2]->timing, $group->timing );
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

		$this->go_to( get_permalink( $post_id ) );

		$this->collector->process();
		$data = $this->collector->get_data();

		self::assertCount( 2, $data->post_blocks );

		// Verify contexts are correctly aligned with the right blocks.
		self::assertSame( $ctx_a, $data->post_blocks[0]->context );
		self::assertSame( $ctx_b, $data->post_blocks[1]->context );

		// Verify each block and its child have independent timings.
		self::assertNotSame( $data->post_blocks[0]->timing, $data->post_blocks[0]->innerBlocks[0]->timing );
		self::assertNotSame( $data->post_blocks[1]->timing, $data->post_blocks[1]->innerBlocks[0]->timing );
	}
}
