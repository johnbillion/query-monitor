<?php declare(strict_types = 1);

namespace QM\Tests;

class CollectorAssetsTest extends Test {

	/**
	 * @dataProvider dataUrlCharacteristics
	 *
	 * @param string $url        The asset URL to parse.
	 * @param string $site_url   The URL of the current page.
	 * @param array<string, string|bool> $expected The expected parsed values.
	 * @phpstan-param array{
	 *   scheme: string,
	 *   hostname: string,
	 *   origin: string,
	 *   absolute: string,
	 *   insecure: bool,
	 *   local: bool,
	 * } $expected
	 */
	public function testUrlCharacteristicsAreDetermined( string $url, string $site_url, array $expected ): void {
		$site = self::build_site_url( $site_url );

		$parsed = \QM_Collector_Assets::determine_url_characteristics( $url, $site );

		self::assertSame( $expected['scheme'], $parsed->scheme );
		self::assertSame( $expected['hostname'], $parsed->hostname );
		self::assertSame( $expected['origin'], $parsed->origin );
		self::assertSame( $expected['absolute'], $parsed->absolute );
		self::assertSame( $expected['insecure'], $parsed->insecure, 'Insecure flag mismatch' );
		self::assertSame( $expected['local'], $parsed->local, 'Local flag mismatch' );
	}

	/**
	 * @return array<string, array<int, mixed>>
	 * @phpstan-return array<string, array{
	 *   0: string,
	 *   1: string,
	 *   2: array{scheme: string, hostname: string, origin: string, absolute: string, insecure: bool, local: bool},
	 * }>
	 */
	public function dataUrlCharacteristics() {
		return array(
			// Regression: a protocol-relative URL inherits HTTP from a localhost page and must not be flagged insecure.
			'protocol-relative URL on insecure localhost page' => array(
				'//foo.com/file.js',
				'http://localhost:8889',
				array(
					'scheme' => 'http',
					'hostname' => 'foo.com',
					'origin' => 'http://foo.com',
					'absolute' => '//foo.com/file.js',
					'insecure' => false,
					'local' => false,
				),
			),
			'http asset on a plain http page' => array(
				'http://foo.com/file.js',
				'http://example.com',
				array(
					'scheme' => 'http',
					'hostname' => 'foo.com',
					'origin' => 'http://foo.com',
					'absolute' => 'http://foo.com/file.js',
					'insecure' => false,
					'local' => false,
				),
			),
			'http asset on an https page is mixed content' => array(
				'http://foo.com/file.js',
				'https://example.com',
				array(
					'scheme' => 'http',
					'hostname' => 'foo.com',
					'origin' => 'http://foo.com',
					'absolute' => 'http://foo.com/file.js',
					'insecure' => true,
					'local' => false,
				),
			),
			'protocol-relative URL on an https page inherits https' => array(
				'//foo.com/file.js',
				'https://example.com',
				array(
					'scheme' => 'https',
					'hostname' => 'foo.com',
					'origin' => 'https://foo.com',
					'absolute' => '//foo.com/file.js',
					'insecure' => false,
					'local' => false,
				),
			),
			'localhost asset on an https page is exempt' => array(
				'http://localhost/file.js',
				'https://example.com',
				array(
					'scheme' => 'http',
					'hostname' => 'localhost',
					'origin' => 'http://localhost',
					'absolute' => 'http://localhost/file.js',
					'insecure' => false,
					'local' => false,
				),
			),
			'root-relative URL inherits the page origin' => array(
				'/wp-content/script.js',
				'https://example.com',
				array(
					'scheme' => 'https',
					'hostname' => 'example.com',
					'origin' => 'https://example.com',
					'absolute' => 'https://example.com/wp-content/script.js',
					'insecure' => false,
					'local' => true,
				),
			),
			'absolute URL matching the page origin is local' => array(
				'https://example.com/wp-content/script.js',
				'https://example.com',
				array(
					'scheme' => 'https',
					'hostname' => 'example.com',
					'origin' => 'https://example.com',
					'absolute' => 'https://example.com/wp-content/script.js',
					'insecure' => false,
					'local' => true,
				),
			),
		);
	}

	private static function build_site_url( string $origin ): \QM_Data_URL {
		$site = new \QM_Data_URL();
		$site->origin = $origin;
		$site->scheme = (string) parse_url( $origin, PHP_URL_SCHEME );
		$site->hostname = (string) parse_url( $origin, PHP_URL_HOST );
		$port = (string) parse_url( $origin, PHP_URL_PORT );
		$site->host = $port ? "{$site->hostname}:{$port}" : $site->hostname;
		$site->insecure = ( 'https' !== $site->scheme ) && ( 'localhost' !== $site->hostname );
		$site->local = true;
		$site->absolute = $origin;

		return $site;
	}

}
