import { expect, test } from '@playwright/test';

import { computeQueryDiff, extractQueries } from '../../src/query-diff';
import type { QuerySnapshot } from '../../src/query-diff';
import { setFrameLookup } from '../../output/frame-lookup';
import type { Backtrace, QueryRow } from '../../output/data-types';

const q = ( sql: string, caller = '' ): QuerySnapshot => ( { sql, caller } );

const times = ( n: number, snapshot: QuerySnapshot ): QuerySnapshot[] => Array( n ).fill( snapshot );

test.describe( 'computeQueryDiff: basic diffing', () => {
	test( 'identical query sets produce no changes', () => {
		const queries = [
			q( 'SELECT * FROM wp_posts', 'WP_Query->get_posts' ),
			q( 'SELECT option_value FROM wp_options', 'get_option' ),
		];

		const result = computeQueryDiff( queries, queries );

		expect( result.added ).toEqual( [] );
		expect( result.removed ).toEqual( [] );
	} );

	test( 'empty query sets produce no changes', () => {
		const result = computeQueryDiff( [], [] );

		expect( result.added ).toEqual( [] );
		expect( result.removed ).toEqual( [] );
		expect( result.previousCount ).toBe( 0 );
		expect( result.currentCount ).toBe( 0 );
	} );

	test( 'query order is irrelevant', () => {
		const previous = [ q( 'SELECT 1' ), q( 'SELECT 2' ), q( 'SELECT 3' ) ];
		const current = [ q( 'SELECT 3' ), q( 'SELECT 1' ), q( 'SELECT 2' ) ];

		const result = computeQueryDiff( previous, current );

		expect( result.added ).toEqual( [] );
		expect( result.removed ).toEqual( [] );
	} );

	test( 'a query only present in the current set is reported as added', () => {
		const previous = [ q( 'SELECT 1' ) ];
		const current = [ q( 'SELECT 1' ), q( 'SELECT 2', 'get_option' ) ];

		const result = computeQueryDiff( previous, current );

		expect( result.added ).toEqual( [ q( 'SELECT 2', 'get_option' ) ] );
		expect( result.removed ).toEqual( [] );
	} );

	test( 'a query only present in the previous set is reported as removed', () => {
		const previous = [ q( 'SELECT 1' ), q( 'SELECT 2', 'get_option' ) ];
		const current = [ q( 'SELECT 1' ) ];

		const result = computeQueryDiff( previous, current );

		expect( result.added ).toEqual( [] );
		expect( result.removed ).toEqual( [ q( 'SELECT 2', 'get_option' ) ] );
	} );

	test( 'disjoint query sets report everything as added and removed', () => {
		const previous = [ q( 'SELECT 1' ), q( 'SELECT 2' ) ];
		const current = [ q( 'SELECT 3' ), q( 'SELECT 4' ) ];

		const result = computeQueryDiff( previous, current );

		expect( result.added ).toEqual( current );
		expect( result.removed ).toEqual( previous );
	} );
} );

test.describe( 'computeQueryDiff: duplicate queries are counted, not deduplicated', () => {
	const query = q( 'SELECT * FROM wp_postmeta WHERE post_id = 1', 'get_post_meta' );

	test( 'an increase from 3 to 5 occurrences reports exactly 2 added', () => {
		const result = computeQueryDiff( times( 3, query ), times( 5, query ) );

		expect( result.added ).toEqual( times( 2, query ) );
		expect( result.removed ).toEqual( [] );
	} );

	test( 'a decrease from 5 to 3 occurrences reports exactly 2 removed', () => {
		const result = computeQueryDiff( times( 5, query ), times( 3, query ) );

		expect( result.added ).toEqual( [] );
		expect( result.removed ).toEqual( times( 2, query ) );
	} );

	test( 'an unchanged occurrence count reports no changes', () => {
		const result = computeQueryDiff( times( 4, query ), times( 4, query ) );

		expect( result.added ).toEqual( [] );
		expect( result.removed ).toEqual( [] );
	} );
} );

test.describe( 'computeQueryDiff: query identity is the SQL plus the caller', () => {
	test( 'the same SQL from a different caller is a different query', () => {
		const previous = [ q( 'SELECT * FROM wp_users', 'WP_User::get_data_by' ) ];
		const current = [ q( 'SELECT * FROM wp_users', 'get_userdata' ) ];

		const result = computeQueryDiff( previous, current );

		// The two must not cancel each other out.
		expect( result.added ).toEqual( current );
		expect( result.removed ).toEqual( previous );
	} );

	test( 'the same caller with different SQL is a different query', () => {
		const previous = [ q( 'SELECT 1', 'get_option' ) ];
		const current = [ q( 'SELECT 2', 'get_option' ) ];

		const result = computeQueryDiff( previous, current );

		expect( result.added ).toEqual( current );
		expect( result.removed ).toEqual( previous );
	} );

	test( 'a || inside the SQL does not collide with the caller boundary', () => {
		// Regression test: a plain `sql + '||' + caller` fingerprint would give
		// both of these the same fingerprint and wrongly report no changes.
		const previous = [ q( 'SELECT a', 'b||x' ) ];
		const current = [ q( 'SELECT a||b', 'x' ) ];

		const result = computeQueryDiff( previous, current );

		expect( result.added ).toEqual( current );
		expect( result.removed ).toEqual( previous );
	} );

	test( 'SQL is compared verbatim, so a differing literal value is a different query', () => {
		// Queries that embed run-time values (timestamps, nonces) diff as
		// changed on every page load even though they are logically the same.
		const previous = [ q( "SELECT ID FROM wp_posts WHERE post_date <= '2026-07-09 10:00:00'", 'WP_Query->get_posts' ) ];
		const current = [ q( "SELECT ID FROM wp_posts WHERE post_date <= '2026-07-10 10:00:00'", 'WP_Query->get_posts' ) ];

		const result = computeQueryDiff( previous, current );

		expect( result.added ).toEqual( current );
		expect( result.removed ).toEqual( previous );
	} );
} );

test.describe( 'extractQueries', () => {
	const row = ( sql: string, extra: Partial<QueryRow> = {} ): QueryRow => ( {
		sql,
		ltime: 0,
		...extra,
	} );

	const trace = ( frames: Backtrace['frames'] ): Backtrace => ( {
		component: { type: 'core', name: 'Core', context: 'core' },
		frames,
		time: 0,
	} );

	test.beforeEach( () => {
		setFrameLookup( [
			{ id: 'WP_Query->get_posts', file: 'wp-includes/class-wp-query.php' },
			{ id: 'get_option', file: 'wp-includes/option.php' },
		] );
	} );

	test( 'undefined rows produce an empty array', () => {
		expect( extractQueries( undefined ) ).toEqual( [] );
	} );

	test( 'an empty rows array produces an empty array', () => {
		expect( extractQueries( [] ) ).toEqual( [] );
	} );

	test( 'the caller is resolved from the first trace frame', () => {
		const rows = [
			row( 'SELECT * FROM wp_posts', { trace: trace( [ [ 0, 123 ], [ 1, 456 ] ] ) } ),
		];

		expect( extractQueries( rows ) ).toEqual( [
			q( 'SELECT * FROM wp_posts', 'WP_Query->get_posts' ),
		] );
	} );

	test( 'the first stack entry is used when there is no trace', () => {
		const rows = [
			row( 'SELECT 1', { stack: [ 'get_transient', 'do_action' ] } ),
		];

		expect( extractQueries( rows ) ).toEqual( [
			q( 'SELECT 1', 'get_transient' ),
		] );
	} );

	test( 'the trace takes precedence over the stack when both are present', () => {
		const rows = [
			row( 'SELECT 1', {
				trace: trace( [ [ 1, 10 ] ] ),
				stack: [ 'get_transient' ],
			} ),
		];

		expect( extractQueries( rows ) ).toEqual( [
			q( 'SELECT 1', 'get_option' ),
		] );
	} );

	test( 'a trace with no frames falls back to the stack', () => {
		const rows = [
			row( 'SELECT 1', {
				trace: trace( [] ),
				stack: [ 'get_transient' ],
			} ),
		];

		expect( extractQueries( rows ) ).toEqual( [
			q( 'SELECT 1', 'get_transient' ),
		] );
	} );

	test( 'a row with neither trace nor stack gets an empty caller', () => {
		const rows = [
			row( 'SELECT 1' ),
			row( 'SELECT 2', { stack: [] } ),
		];

		expect( extractQueries( rows ) ).toEqual( [
			q( 'SELECT 1', '' ),
			q( 'SELECT 2', '' ),
		] );
	} );

	test( 'the SQL is copied verbatim and row order is preserved', () => {
		const rows = [
			row( "SELECT ID FROM wp_posts WHERE post_date <= '2026-07-09 10:00:00'" ),
			row( 'SELECT   *\nFROM wp_options' ),
		];

		expect( extractQueries( rows ) ).toEqual( [
			q( "SELECT ID FROM wp_posts WHERE post_date <= '2026-07-09 10:00:00'", '' ),
			q( 'SELECT   *\nFROM wp_options', '' ),
		] );
	} );
} );

test.describe( 'computeQueryDiff: result metadata', () => {
	test( 'previousCount and currentCount are total query counts including duplicates', () => {
		const query = q( 'SELECT 1' );

		const result = computeQueryDiff( times( 3, query ), times( 5, query ) );

		expect( result.previousCount ).toBe( 3 );
		expect( result.currentCount ).toBe( 5 );
	} );

	test( 'the status is always ready', () => {
		expect( computeQueryDiff( [], [] ).status ).toBe( 'ready' );
	} );
} );
