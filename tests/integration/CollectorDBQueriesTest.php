<?php declare(strict_types = 1);

namespace QM\Tests;

class CollectorDBQueriesTest extends Test {

	/**
	 * @var array<int, mixed>
	 */
	private array $queries;

	private int $num_queries;

	private int $cease_count;

	#[\Override]
	public function set_up(): void {
		parent::set_up();

		global $wpdb, $wp_actions;

		$this->queries = $wpdb->queries;
		$this->num_queries = $wpdb->num_queries;
		$this->cease_count = $wp_actions['qm/cease'] ?? 0;

		$wpdb->queries = array();
		$wpdb->num_queries = 0;

		// QM ceases on CLI requests, which makes the QM_DB drop-in discard logged queries.
		unset( $wp_actions['qm/cease'] );
	}

	#[\Override]
	public function tear_down(): void {
		global $wpdb, $wp_actions;

		$wpdb->queries = $this->queries;
		$wpdb->num_queries = $this->num_queries;
		unset( $wpdb->save_queries );

		if ( $this->cease_count ) {
			$wp_actions['qm/cease'] = $this->cease_count;
		}

		parent::tear_down();
	}

	public function testQueryContainingNonUtf8DataCanBeEncodedAsJson(): void {
		global $wpdb;

		$table = $wpdb->prefix . 'qm_binary_ip_test';

		$wpdb->query( "CREATE TABLE `{$table}` ( `ip` varbinary(16) NOT NULL )" );
		$wpdb->insert(
			$table,
			array(
				'ip' => inet_pton( '192.168.1.0' ),
			),
			array( '%s' )
		);
		$wpdb->query( "DROP TABLE `{$table}`" );

		$collector = new \QM_Collector_DB_Queries();
		$collector->process();

		$data = $collector->get_data();

		self::assertIsArray( $data->rows );

		$insert_rows = array_values( array_filter(
			$data->rows,
			static fn ( array $row ): bool => str_starts_with( $row['sql'], "INSERT INTO `{$table}`" )
		) );

		self::assertCount( 1, $insert_rows );
		self::assertFalse( json_encode( $insert_rows[0]['sql'] ) );

		$json = \QM_Util::json_encode( array(
			'data' => $data,
		), JSON_UNESCAPED_SLASHES | JSON_HEX_TAG );

		self::assertIsString( $json );

		$decoded = json_decode( $json, true );

		self::assertIsArray( $decoded );
		self::assertCount( count( $data->rows ), $decoded['data']['rows'] );

		$decoded_insert_rows = array_filter(
			array_column( $decoded['data']['rows'], 'sql' ),
			static fn ( string $sql ): bool => str_starts_with( $sql, "INSERT INTO `{$table}` (`ip`) VALUES ('" )
		);

		self::assertCount( 1, $decoded_insert_rows );
	}

	public function testClearedQueriesAreCountedWithUnknownReason(): void {
		global $wpdb;

		$wpdb->num_queries = 5;

		$collector = new \QM_Collector_DB_Queries();
		$collector->process();

		$data = $collector->get_data();

		self::assertSame( 5, $data->total_qs );
		self::assertSame( 'unknown', $data->not_logged_reason );
		self::assertNull( $data->rows );
	}

	public function testClearedQueriesWithSaveQueriesPropertyDisabledAreExplained(): void {
		global $wpdb;

		$wpdb->num_queries = 5;
		$wpdb->save_queries = false;

		$collector = new \QM_Collector_DB_Queries();
		$collector->process();

		$data = $collector->get_data();

		self::assertSame( 5, $data->total_qs );
		self::assertSame( 'property', $data->not_logged_reason );
	}

}
