<?php

class Test_Plugin extends QM_UnitTestCase {
	private $readme_data;

	public function test_stable_tag() {
		if ( ! $readme_data = $this->get_readme() ) {
			$this->markTestSkipped( 'There is no readme file' );
			return;
		}
		$plugin_data = get_plugin_data( dirname( dirname( dirname( __FILE__ ) ) ) . '/query-monitor.php' );

		$this->assertEquals( $readme_data['stable_tag'], $plugin_data['Version'] );
	}

	private function get_readme() {
		if( ! isset( $this->readme_data ) ) {
			$file = dirname( dirname( dirname( __FILE__ ) ) ) . '/readme.txt';

			if ( ! is_file( $file ) ) {
				return false;
			}

			$file_contents = implode( '', file( $file ) );

			preg_match( '|Tested up to:(.*)|i', $file_contents, $_tested_up_to );
			preg_match( '|Stable tag:(.*)|i', $file_contents, $_stable_tag );

			$this->readme_data = array(
				'tested_up_to' => trim( trim( $_tested_up_to[1], '*' ) ),
				'stable_tag'   => trim( trim( $_stable_tag[1], '*' ) )
			);
		}

		return $this->readme_data;
	}

}
