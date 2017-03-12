<?php

class TestCollectorTheme extends QM_UnitTestCase {

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

}
