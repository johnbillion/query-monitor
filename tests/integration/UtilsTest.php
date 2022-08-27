<?php

declare(strict_types = 1);

namespace QM\Tests;

class Utils extends Test {

	/**
	 * @dataProvider dataClientVersion
	 *
	 * @param int $client
	 * @param array<int, int> $expected
	 */
	public function testDatabaseDriverClientVersionIsDetected( int $client, array $expected ): void {

		$ver = \QM_Util::get_client_version( $client );

		self::assertEquals( $expected, array_values( $ver ) );

	}

	/**
	 * @return array<string, array{
	 *   0: int,
	 *   1: array<int, int>
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

}
