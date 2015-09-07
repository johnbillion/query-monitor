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

	protected static function get_theme_data( $item ) {

		// @TODO this should be abstracted into a more general method which can be used for any of the collectors

		$theme = QM_Collectors::get( 'theme' );
		$theme->process();

		$data = $theme->get_data();

		return $data[ $item ];
	}

}
