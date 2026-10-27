<?php declare(strict_types = 1);

namespace QM\Tests;

class UtilsTest extends Test {

	/**
	 * @dataProvider dataClientVersion
	 *
	 * @param int $client
	 * @param array<int, int> $expected
	 */
	public function testDatabaseDriverClientVersionIsDetected( int $client, array $expected ): void {

		$ver = \QM_Util::get_client_version( $client );

		self::assertSame( $expected, array_values( $ver ) );

	}

	/**
	 * @return array<string, array<int, int|array<int, int>>>
	 * @phpstan-return array<string, array{
	 *   0: int,
	 *   1: list<int>
	 * }>
	 */
	public function dataClientVersion() {
		return array(
			'client 12345' => array(
				12345,
				array( 1, 23, 45 ),
			),
			'client 10511' => array(
				10511,
				array( 1, 5, 11 ),
			),
			'client 10001' => array(
				10001,
				array( 1, 0, 1 ),
			),
			'client 31010' => array(
				31010,
				array( 3, 10, 10 ),
			),
			'client 20000' => array(
				20000,
				array( 2, 0, 0 ),
			),
		);
	}

	public function testJsonSerializableObjectsAreRetainedWhenDataContainsNonUtf8Strings(): void {
		$serializable = new class() implements \JsonSerializable {
			/**
			 * @return array<string, string>
			 */
			public function jsonSerialize(): array {
				return array(
					'serialized' => 'yes',
				);
			}
		};

		$container = new \stdClass();
		$container->rows = array(
			array(
				'sql' => "INSERT INTO `t` (`ip`) VALUES ('" . inet_pton( '192.168.1.0' ) . "')",
				'trace' => $serializable,
			),
		);

		$json = \QM_Util::json_encode( array(
			'data' => $container,
		) );

		self::assertIsString( $json );

		$decoded = json_decode( $json, true );

		self::assertIsArray( $decoded );
		self::assertSame( array(
			'serialized' => 'yes',
		), $decoded['data']['rows'][0]['trace'] );
		self::assertStringStartsWith( 'INSERT INTO', $decoded['data']['rows'][0]['sql'] );
	}

}
