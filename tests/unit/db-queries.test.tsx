import { describe, expect, it } from 'vitest';
import { render, screen } from '@testing-library/preact';

import { getDupes, normalizeDupeSQL } from '../../output/html/db_queries';
import { DBDupes } from '../../output/html/db_dupes';

import { backtrace, dbQueriesData, queryRow } from './fixtures';

const normalisationCases: [ description: string, sql: string, normalised: string ][] = [
	[ 'replaces newlines with a space', 'SELECT 1\nFROM wp_posts', 'SELECT 1 FROM wp_posts' ],
	[ 'replaces carriage returns with a space', 'SELECT 1\r\nFROM wp_posts', 'SELECT 1 FROM wp_posts' ],
	[ 'removes tabs without leaving a space in their place', 'SELECT\t1 FROM wp_posts', 'SELECT1 FROM wp_posts' ],
	[ 'removes backticks', 'SELECT 1 FROM `wp_posts`', 'SELECT 1 FROM wp_posts' ],
	[ 'collapses repeated spaces', 'SELECT   1  FROM wp_posts', 'SELECT 1 FROM wp_posts' ],
	[ 'trims surrounding whitespace', '  SELECT 1 FROM wp_posts  ', 'SELECT 1 FROM wp_posts' ],
	[ 'removes a trailing semicolon', 'SELECT 1 FROM wp_posts;', 'SELECT 1 FROM wp_posts' ],
	[ 'removes repeated trailing semicolons', 'SELECT 1 FROM wp_posts;;;', 'SELECT 1 FROM wp_posts' ],
	[ 'keeps a semicolon that is not trailing', 'SELECT ";" FROM wp_posts', 'SELECT ";" FROM wp_posts' ],
	[ 'applies every rule at once', '  SELECT   123\nFROM `wp_posts`;  ', 'SELECT 123 FROM wp_posts' ],
	[ 'leaves an already normalised query alone', 'SELECT 1 FROM wp_posts', 'SELECT 1 FROM wp_posts' ],
	[ 'handles an empty query', '', '' ],
];

describe( 'getDupes', () => {
	it( 'ignores a query that only ran once', () => {
		expect( getDupes( [ queryRow( { sql: 'SELECT 1' } ) ] ) ).toEqual( [] );
	} );

	it( 'detects a query that ran more than once', () => {
		const dupes = getDupes( [
			queryRow( { sql: 'SELECT 123 FROM wp_posts' } ),
			queryRow( { sql: 'SELECT 456 FROM wp_posts' } ),
			queryRow( { sql: 'SELECT 123 FROM wp_posts' } ),
		] );

		expect( dupes ).toHaveLength( 1 );
		expect( dupes[0].query ).toBe( 'SELECT 123 FROM wp_posts' );
		expect( dupes[0].count ).toBe( 2 );
	} );

	it( 'tallies the callers of each duplicate', () => {
		const dupes = getDupes( [
			queryRow( { sql: 'SELECT 123', stack: [ 'get_posts()' ] } ),
			queryRow( { sql: 'SELECT 123', stack: [ 'get_posts()' ] } ),
			queryRow( { sql: 'SELECT 123', stack: [ 'get_pages()' ] } ),
			queryRow( { sql: 'SELECT 456', stack: [ 'get_pages()' ] } ),
		] );

		expect( dupes[0].callers ).toEqual( {
			'get_posts()': 2,
			'get_pages()': 1,
		} );
	} );

	it( 'tallies the components of each duplicate', () => {
		const dupes = getDupes( [
			queryRow( { sql: 'SELECT 123', trace: backtrace( 'plugin: foo' ) } ),
			queryRow( { sql: 'SELECT 123', trace: backtrace( 'plugin: bar' ) } ),
			queryRow( { sql: 'SELECT 123', trace: backtrace( 'plugin: foo' ) } ),
			queryRow( { sql: 'SELECT 456', trace: backtrace( 'plugin: bar' ) } ),
		] );

		expect( dupes[0].components ).toEqual( {
			'plugin: foo': 2,
			'plugin: bar': 1,
		} );
	} );

	it( 'keeps separate queries in separate groups', () => {
		const dupes = getDupes( [
			queryRow( { sql: 'SELECT 123' } ),
			queryRow( { sql: 'SELECT 123' } ),
			queryRow( { sql: 'SELECT 456' } ),
			queryRow( { sql: 'SELECT 789' } ),
			queryRow( { sql: 'SELECT 789' } ),
		] );

		expect( dupes.map( ( dupe ) => dupe.query ) ).toEqual( [ 'SELECT 123', 'SELECT 789' ] );
	} );

	describe( 'groups queries that normalise to the same SQL', () => {
		it.each( normalisationCases )( '%s', ( _description, sql, normalised ) => {
			const dupes = getDupes( [
				queryRow( { sql } ),
				queryRow( { sql: normalised } ),
			] );

			expect( dupes ).toHaveLength( 1 );
			expect( dupes[0].count ).toBe( 2 );
			expect( dupes[0].query ).toBe( normalised );
		} );
	} );
} );

describe( 'normalizeDupeSQL', () => {
	it.each( normalisationCases )( '%s', ( _description, sql, normalised ) => {
		expect( normalizeDupeSQL( sql ) ).toBe( normalised );
	} );

	it( 'is idempotent', () => {
		for ( const [ , sql ] of normalisationCases ) {
			const once = normalizeDupeSQL( sql );

			expect( normalizeDupeSQL( once ) ).toBe( once );
		}
	} );

	it( 'preserves the values that distinguish two queries', () => {
		expect( normalizeDupeSQL( 'SELECT 1 FROM wp_posts' ) )
			.not.toBe( normalizeDupeSQL( 'SELECT 2 FROM wp_posts' ) );
	} );
} );

describe( 'DBDupes panel', () => {
	it( 'renders nothing when there are no duplicates', () => {
		const { container } = render(
			<DBDupes data={ dbQueriesData( [ queryRow( { sql: 'SELECT 1' } ) ] ) } enabled={ true } />
		);

		expect( container.innerHTML ).toBe( '' );
	} );

	it( 'renders the query and its count', () => {
		render(
			<DBDupes
				data={ dbQueriesData( [
					queryRow( { sql: 'SELECT 123 FROM wp_posts' } ),
					queryRow( { sql: 'SELECT 123 FROM wp_posts' } ),
				] ) }
				enabled={ true }
			/>
		);

		const row = screen.getByRole( 'row', { name: /SELECT 123/ } );
		const cells = Array.from( row.querySelectorAll( 'td' ) ).map( ( cell ) => cell.textContent );

		// formatSQL() breaks the query across elements at each keyword.
		expect( cells[0] ).toContain( 'SELECT 123' );
		expect( cells[0] ).toContain( 'FROM wp_posts' );
		expect( cells[1] ).toBe( '2' );
	} );
} );
