<?php

class Test_Collector_Theme extends QM_UnitTestCase {

	// @TODO need to ideally use a known theme

	public function testThemeTemplateIsCorrectForSearch() {

		$this->go_to_with_template( get_search_link( 'foo' ) );

		$this->assertTrue( is_search() );
		$this->assertSame( 'search.php', self::get_theme_data( 'template_file' ) );

	}

	public function testThemeTemplateIsCorrectForHome() {

		$this->go_to_with_template( home_url() );

		$this->assertTrue( is_home() );
		$this->assertSame( 'index.php', self::get_theme_data( 'template_file' ) );

	}

	public function testThemeTemplateIsCorrectForPage() {

		$page = $this->factory->post->create( array(
			'post_type' => 'page',
		) );

		$this->go_to_with_template( get_permalink( $page ) );

		$this->assertTrue( is_page() );
		$this->assertSame( 'page.php', self::get_theme_data( 'template_file' ) );

	}

	public function testThemeTemplateIsCorrectForPost() {

		$post = $this->factory->post->create();

		$this->go_to_with_template( get_permalink( $post ) );

		$this->assertTrue( is_single() );
		$this->assertSame( 'single.php', self::get_theme_data( 'template_file' ) );

	}

	public function testTemplateHierarchyAssumptionsAreAccurate() {

		$file = ABSPATH . WPINC . '/template-loader.php';

		$this->assertFileExists( $file );

		$contents = file_get_contents( $file );

		$this->assertNotEmpty( $contents );

		$regex = '#^\s*(?:else)?if\s+\(\s*(is_[a-z0-9_]+)\(\)(?:.*?)get_([a-z0-9_]+)_template\(\)#m';
		$count = preg_match_all( $regex, $contents, $matches );

		$this->assertGreaterThan( 0, $count );

		list( , $conditionals, $templates ) = $matches;

		$conditionals[] = '__return_true';
		$templates[]    = 'index';

		$names = QM_Collector_Theme::get_query_template_names();

		if ( false !== $paged = array_search( 'paged', $templates, true ) ) {
			// The paged template was removed in WP 4.7. On older WP versions,
			// skip testing for it.
			unset(
				$conditionals[ $paged ],
				$templates[ $paged ]
			);
		}

		$this->assertEquals( array_values( $names ), array_values( $conditionals ) );
		$this->assertEquals( array_keys( $names ), array_values( $templates ) );

	}

	protected static function get_theme_data( $item ) {

		// @TODO this should be abstracted into a more general method which can be used for any of the collectors

		$theme = QM_Collectors::get( 'theme' );
		$theme->process();

		$data = $theme->get_data();

		return $data[ $item ];
	}

}
