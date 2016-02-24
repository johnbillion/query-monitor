<?php

class Test_Utils extends QM_UnitTestCase {

	/**
	 * @dataProvider clientVersionData
	 *
	 */
	public function testClientVersion( $client, $expected ) {

		$ver = QM_Util::get_client_version( $client );

		$this->assertEquals( $expected, array_values( $ver ) );

	}

	public function clientVersionData() {
		return array(
			array(
				12345,
				array( 1, 23, 45 ),
			),
			array(
				10511,
				array( 1, 5, 11 ),
			),
			array(
				10001,
				array( 1, 0, 1 ),
			),
			array(
				31010,
				array( 3, 10, 10 ),
			),
			array(
				20000,
				array( 2, 0, 0 ),
			),
		);
	}

}
