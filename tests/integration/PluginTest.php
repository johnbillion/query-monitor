<?php declare(strict_types = 1);

namespace QM\Tests;

class PluginTest extends Test {
	/**
	 * @var ?array{
	 *   stable_tag: string,
	 * }
	 */
	private $readme_data;

	public function testStableTagIsUpToDate(): void {
		if ( ! $readme_data = $this->get_readme() ) {
			self::fail( 'There is no readme file' );
		}

		$plugin_data = get_plugin_data( dirname( dirname( dirname( __FILE__ ) ) ) . '/query-monitor.php' );

		self::assertSame( $readme_data['stable_tag'], $plugin_data['Version'] );
	}

	/**
	 * @return array{
	 *   stable_tag: string,
	 * }|false
	 */
	private function get_readme() {
		if ( ! isset( $this->readme_data ) ) {
			$file = dirname( dirname( dirname( __FILE__ ) ) ) . '/readme.txt';

			if ( ! is_file( $file ) ) {
				return false;
			}

			$file_contents = file( $file );

			if ( ! $file_contents ) {
				return false;
			}

			$file_contents = implode( '', $file_contents );

			if ( preg_match( '|Stable tag:(.*)|i', $file_contents, $_stable_tag ) !== 1 ) {
				return false;
			}

			$this->readme_data = array(
				'stable_tag' => trim( trim( $_stable_tag[1], '*' ) )
			);
		}

		return $this->readme_data;
	}

}
