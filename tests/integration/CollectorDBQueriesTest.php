<?php declare(strict_types = 1);

namespace QM\Tests;

use QM\Tests\Supports\TestDB;

class CollectorDBQueriesTest extends Test {

	private const MAIN_QUERY_STACK = "require('wp-blog-header.php'), wp, WP->main, WP->query_posts, WP_Query->query, WP_Query->get_posts";

	private const OTHER_STACK = 'do_action, WP_Hook->do_action, WP_Hook->apply_filters, get_option';

	/**
	 * @var array<int, mixed>
	 */
	private $original_queries;

	/**
	 * @var int
	 */
	private $original_num_queries;

	/**
	 * @var ?string
	 */
	private $original_request;

	#[\Override]
	public function set_up(): void {
		parent::set_up();

		global $wpdb, $wp_the_query;

		$this->original_queries = $wpdb->queries;
		$this->original_num_queries = $wpdb->num_queries;
		$this->original_request = $wp_the_query->request;
	}

	#[\Override]
	public function tear_down(): void {
		global $wpdb, $wp_the_query;

		$wpdb->queries = $this->original_queries;
		$wpdb->num_queries = $this->original_num_queries;
		$wp_the_query->request = $this->original_request;

		unset( $_REQUEST['qm_display_admin_bar'] );

		parent::tear_down();
	}

	/**
	 * @param array<int, mixed> $queries
	 */
	private static function process_queries( array $queries ): \QM_Data_DB_Queries {
		global $wpdb;

		$wpdb->queries = $queries;

		$collector = new \QM_Collector_DB_Queries();
		$collector->process();

		/** @var \QM_Data_DB_Queries $data */
		$data = $collector->get_data();

		return $data;
	}

	private static function trace(): \QM_Backtrace {
		return new \QM_Backtrace();
	}

	public function testStandardQueriesAreCollected(): void {
		$data = self::process_queries( array(
			array( ' SELECT 1 ', 0.001, self::OTHER_STACK ),
			array( 'SELECT 2', 0.002, self::OTHER_STACK ),
		) );

		self::assertSame( 2, $data->total_qs );
		self::assertIsArray( $data->rows );
		self::assertCount( 2, $data->rows );
		self::assertSame( 'SELECT 1', $data->rows[0]['sql'] );
		self::assertSame( 0.001, $data->rows[0]['ltime'] );
		self::assertSame( 'SELECT 2', $data->rows[1]['sql'] );
		self::assertArrayHasKey( 'stack', $data->rows[0] );
		self::assertArrayNotHasKey( 'trace', $data->rows[0] );
		self::assertArrayNotHasKey( 'result', $data->rows[0] );
		self::assertFalse( $data->has_trace );
		self::assertFalse( $data->has_result );
		self::assertSame( array(), $data->errors );
	}

	public function testVipQueriesAreCollected(): void {
		$data = self::process_queries( array(
			array(
				'query' => 'SELECT 1',
				'elapsed' => 0.001,
				'debug' => self::OTHER_STACK,
			),
		) );

		self::assertSame( 1, $data->total_qs );
		self::assertIsArray( $data->rows );
		self::assertSame( 'SELECT 1', $data->rows[0]['sql'] );
		self::assertSame( 0.001, $data->rows[0]['ltime'] );
		self::assertArrayHasKey( 'stack', $data->rows[0] );
	}

	public function testMalformedQueriesAreSkipped(): void {
		$data = self::process_queries( array(
			array( 'SELECT 1' ),
			array( 'SELECT 2', 0.002, self::OTHER_STACK ),
		) );

		self::assertSame( 1, $data->total_qs );
		self::assertIsArray( $data->rows );
		self::assertSame( array( 0 ), array_keys( $data->rows ) );
		self::assertSame( 'SELECT 2', $data->rows[0]['sql'] );
	}

	public function testAdminBarQueriesAreSkippedByDefault(): void {
		$data = self::process_queries( array(
			array( 'SELECT 1', 0.001, 'wp_admin_bar_render, get_option' ),
			array( 'SELECT 2', 0.002, self::OTHER_STACK ),
		) );

		self::assertSame( 1, $data->total_qs );
		self::assertIsArray( $data->rows );
		self::assertSame( 'SELECT 2', $data->rows[0]['sql'] );
	}

	public function testAdminBarQueriesAreCollectedWhenRequested(): void {
		$_REQUEST['qm_display_admin_bar'] = '1';

		$data = self::process_queries( array(
			array( 'SELECT 1', 0.001, 'wp_admin_bar_render, get_option' ),
			array( 'SELECT 2', 0.002, self::OTHER_STACK ),
		) );

		self::assertSame( 2, $data->total_qs );
	}

	public function testMainQueryIsIdentified(): void {
		global $wp_the_query;

		$wp_the_query->request = 'SELECT * FROM wp_posts';

		$data = self::process_queries( array(
			array( 'SELECT * FROM wp_posts', 0.001, self::MAIN_QUERY_STACK ),
			array( 'SELECT * FROM wp_posts', 0.001, self::OTHER_STACK ),
			array( 'SELECT * FROM wp_options', 0.001, self::MAIN_QUERY_STACK ),
		) );

		self::assertIsArray( $data->rows );
		self::assertTrue( $data->rows[0]['is_main_query'] ?? false );
		self::assertArrayNotHasKey( 'is_main_query', $data->rows[1] );
		self::assertArrayNotHasKey( 'is_main_query', $data->rows[2] );
	}

	public function testMainQueryIsIdentifiedWhenAHostAppendsAComment(): void {
		global $wp_the_query;

		$wp_the_query->request = 'SELECT * FROM wp_posts';

		$data = self::process_queries( array(
			array( "SELECT * FROM wp_posts /* From [example.com/] in [/wp-content/db.php:29] */\n", 0.001, self::MAIN_QUERY_STACK ),
		) );

		self::assertIsArray( $data->rows );
		self::assertTrue( $data->rows[0]['is_main_query'] ?? false );
	}

	public function testExpensiveQueriesAreIdentified(): void {
		$data = self::process_queries( array(
			array( 'SELECT 1', QM_DB_EXPENSIVE, self::OTHER_STACK ),
			array( 'SELECT 2', QM_DB_EXPENSIVE + 0.01, self::OTHER_STACK ),
			array( 'SELECT 3', 0.001, self::OTHER_STACK ),
		) );

		self::assertSame( array( 1 ), $data->expensive );
	}

	public function testTraceAndResultFromDropInAreCollected(): void {
		$error = new \WP_Error( 1146, "Table 'wp_missing' doesn't exist" );

		$data = self::process_queries( array(
			array(
				'SELECT 1',
				0.001,
				self::OTHER_STACK,
				'trace' => self::trace(),
				'result' => 1,
			),
			array(
				'SELECT * FROM wp_missing',
				0.001,
				self::OTHER_STACK,
				'trace' => self::trace(),
				'result' => $error,
			),
		) );

		self::assertTrue( $data->has_trace );
		self::assertTrue( $data->has_result );
		self::assertIsArray( $data->rows );
		self::assertInstanceOf( \QM_Backtrace::class, $data->rows[0]['trace'] ?? null );
		self::assertArrayNotHasKey( 'stack', $data->rows[0] );
		self::assertSame( 1, $data->rows[0]['result'] ?? null );
		self::assertSame( $error, $data->rows[1]['result'] ?? null );
		self::assertSame( array( 1 ), $data->errors );
	}

	public function testTraceAndResultDoNotLeakIntoFollowingQueries(): void {
		$data = self::process_queries( array(
			array(
				'SELECT * FROM wp_missing',
				0.001,
				self::OTHER_STACK,
				'trace' => self::trace(),
				'result' => new \WP_Error( 1146, "Table 'wp_missing' doesn't exist" ),
			),
			array( 'SELECT 1', 0.001, self::OTHER_STACK ),
		) );

		self::assertIsArray( $data->rows );
		self::assertArrayNotHasKey( 'trace', $data->rows[1] );
		self::assertArrayNotHasKey( 'result', $data->rows[1] );
		self::assertArrayHasKey( 'stack', $data->rows[1] );
		self::assertSame( array( 0 ), $data->errors );
	}

	public function testExtendedQueryPromptCanBeDisabled(): void {
		add_filter( 'qm/show_extended_query_prompt', '__return_false' );

		try {
			$data = self::process_queries( array(
				array( 'SELECT 1', 0.001, self::OTHER_STACK ),
			) );
		} finally {
			remove_filter( 'qm/show_extended_query_prompt', '__return_false' );
		}

		self::assertNull( $data->extended_query_prompt_reason );
	}

	public function testNoReasonIsGivenWhenNoQueriesWerePerformed(): void {
		global $wpdb;

		$wpdb->num_queries = 0;

		$data = self::process_queries( array() );

		self::assertSame( 0, $data->total_qs );
		self::assertNull( $data->rows );
		self::assertNull( $data->not_logged_reason );
		self::assertNull( $data->db_class );
	}

	public function testUnknownReasonIsGivenWhenQueriesWereNotLogged(): void {
		global $wpdb;

		$wpdb->num_queries = 3;

		$data = self::process_queries( array() );

		self::assertSame( 3, $data->total_qs );
		self::assertNull( $data->rows );
		self::assertSame( 'unknown', $data->not_logged_reason );
		self::assertNull( $data->db_class );
	}

	public function testPropertyReasonIsGivenWhenDropInDoesNotSaveQueries(): void {
		$original_wpdb = $GLOBALS['wpdb'];

		$db = new TestDB();
		$db->queries = array();
		$db->num_queries = 3;

		$GLOBALS['wpdb'] = $db;

		try {
			$collector = new \QM_Collector_DB_Queries();
			$collector->process();
		} finally {
			$GLOBALS['wpdb'] = $original_wpdb;
		}

		/** @var \QM_Data_DB_Queries $data */
		$data = $collector->get_data();

		self::assertSame( 3, $data->total_qs );
		self::assertSame( 'property', $data->not_logged_reason );
		self::assertSame( TestDB::class, $data->db_class );
	}

	public function testDropInLogsTraceAndResult(): void {
		global $wp_actions;

		$db = new \QM_DB( DB_USER, DB_PASSWORD, DB_NAME, DB_HOST );

		$original_error_log = ini_get( 'error_log' );
		$original_cease = $wp_actions['qm/cease'] ?? null;

		// wpdb logs failed queries with error_log(), which would otherwise be printed by the CLI.
		ini_set( 'error_log', '/dev/null' );

		// QM ceases during CLI requests, which would cause QM_DB to discard its logged queries.
		unset( $wp_actions['qm/cease'] );

		try {
			$db->query( 'SELECT 1' );
			$db->query( 'SELECT * FROM qm_table_that_does_not_exist' );
		} finally {
			ini_set( 'error_log', (string) $original_error_log );

			if ( null !== $original_cease ) {
				$wp_actions['qm/cease'] = $original_cease;
			}

			$db->close();
		}

		// Any queries performed while connecting are not relevant.
		/** @var list<array<int|string, mixed>> $queries */
		$queries = array_slice( $db->queries, -2 );

		self::assertCount( 2, $queries );

		self::assertSame( 'SELECT 1', $queries[0][0] );
		self::assertInstanceOf( \QM_Backtrace::class, $queries[0]['trace'] );
		self::assertSame( 1, $queries[0]['result'] );

		self::assertSame( 'SELECT * FROM qm_table_that_does_not_exist', $queries[1][0] );
		self::assertInstanceOf( \QM_Backtrace::class, $queries[1]['trace'] );
		self::assertInstanceOf( \WP_Error::class, $queries[1]['result'] );
		self::assertSame( 1146, $queries[1]['result']->get_error_code() );
	}

}
