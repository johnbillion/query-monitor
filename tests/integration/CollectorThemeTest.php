<?php

declare(strict_types = 1);

namespace QM\Tests;

class CollectorTheme extends Test {

	public function testTemplateHierarchyAssumptionsAreAccurate(): void {

		$file = ABSPATH . WPINC . '/template-loader.php';

		self::assertFileExists( $file );

		$contents = file_get_contents( $file );

		self::assertNotEmpty( $contents );

		// Pre-5.3 regex:
		$regex = '#^\s*(?:else)?if\s+\(\s*(is_[a-z0-9_]+)\(\)(?:.*?)get_([a-z0-9_]+)_template\(\)#m';
		$count = preg_match_all( $regex, $contents, $matches );

		if ( ! $count ) {
			// 5.3+ regex:
			$regex = '#^\s*\'(is_[a-z0-9_]+)\' +=> \'get_([a-z0-9_]+)_template\'#m';
			$count = preg_match_all( $regex, $contents, $matches );
		}

		self::assertGreaterThan( 0, $count );

		list( , $conditionals, $templates ) = $matches;

		$conditionals[] = '__return_true';
		$templates[] = 'index';

		$names = \QM_Collector_Theme::get_query_template_names();

		if ( false !== $paged = array_search( 'paged', $templates, true ) ) {
			// The paged template was removed in WP 4.7. On older WP versions,
			// skip testing for it.
			unset(
				$conditionals[ $paged ],
				$templates[ $paged ]
			);
		}

		self::assertEquals( array_values( $names ), array_values( $conditionals ) );
		self::assertEquals( array_keys( $names ), array_values( $templates ) );

	}

}
