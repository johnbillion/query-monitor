import { describe, expect, it } from 'vitest';
import { render } from '@testing-library/preact';

import * as Utils from '../../output/utils';

/**
 * Renders the elements returned by a formatter and returns the resulting markup.
 */
const html = ( elements: ReturnType<typeof Utils.formatSQL> ): string => (
	render( <>{ elements }</> ).container.innerHTML
);

describe( 'numberFormat', () => {
	it.each( [
		[ 0, '0' ],
		[ 1, '1' ],
		[ 999, '999' ],
		[ 1000, '1,000' ],
		[ 12345, '12,345' ],
		[ 1234567, '1,234,567' ],
	] )( 'groups thousands in %i as %s', ( value, expected ) => {
		expect( Utils.numberFormat( value ) ).toBe( expected );
	} );

	it( 'truncates rather than rounds when no decimals are requested', () => {
		expect( Utils.numberFormat( 1999.9 ) ).toBe( '1,999' );
	} );

	it( 'renders the requested number of decimal places', () => {
		expect( Utils.numberFormat( 1.5, 2 ) ).toBe( '1.50' );
		expect( Utils.numberFormat( 1234.5678, 2 ) ).toBe( '1,234.57' );
	} );
} );

describe( 'formatDuration', () => {
	it( 'renders four decimal places', () => {
		expect( Utils.formatDuration( 0.1 ) ).toBe( '0.1000' );
		expect( Utils.formatDuration( 1.23456 ) ).toBe( '1.2346' );
	} );

	it( 'groups thousands in a long duration', () => {
		expect( Utils.formatDuration( 1234.5 ) ).toBe( '1,234.5000' );
	} );
} );

describe( 'formatSQL', () => {
	it.each( [
		[
			'emboldens each recognised keyword and breaks the line before it',
			'SELECT foo FROM bar',
			'<b>SELECT</b> foo<br><b>FROM</b> bar',
		],
		[
			'breaks at every keyword, not just the first two',
			'SELECT foo FROM bar WHERE baz = 1',
			'<b>SELECT</b> foo<br><b>FROM</b> bar<br><b>WHERE</b> baz = 1',
		],
		[
			'treats a multi-word keyword as one keyword',
			'SELECT foo FROM bar INNER JOIN baz ON baz.id = bar.id',
			'<b>SELECT</b> foo<br><b>FROM</b> bar<br><b>INNER JOIN</b> baz<br><b>ON</b> baz.id = bar.id',
		],
		[
			'collapses newlines and tabs in the source query',
			'SELECT foo\n\tFROM bar',
			'<b>SELECT</b> foo<br><b>FROM</b> bar',
		],
		[
			'highlights a quoted value',
			"SELECT foo FROM bar WHERE baz = 'qux'",
			'<b>SELECT</b> foo<br><b>FROM</b> bar<br><b>WHERE</b> baz = <span class="qm-sql-value">\'qux\'</span>',
		],
		[
			'highlights each of several quoted values',
			"SELECT 'a', 'b' FROM bar",
			'<b>SELECT</b> <span class="qm-sql-value">\'a\'</span>, <span class="qm-sql-value">\'b\'</span><br><b>FROM</b> bar',
		],
		[
			'escapes markup inside a quoted value',
			'SELECT foo FROM bar WHERE baz = "<script>"',
			'<b>SELECT</b> foo<br><b>FROM</b> bar<br><b>WHERE</b> baz = <span class="qm-sql-value">"&lt;script&gt;"</span>',
		],
		[
			'puts a leading comment on its own line',
			'/* my comment */ SELECT foo FROM bar',
			'/* my comment */<br><b>SELECT</b> foo<br><b>FROM</b> bar',
		],
		[
			'puts a trailing comment on its own line',
			'SELECT foo FROM bar /* my comment */',
			'<b>SELECT</b> foo<br><b>FROM</b> bar<br>/* my comment */',
		],
		[
			'leaves a query containing no recognised keyword unformatted',
			'FLUSH TABLES',
			'FLUSH TABLES',
		],
	] )( '%s', ( _description, sql, expected ) => {
		expect( html( Utils.formatSQL( sql ) ) ).toBe( expected );
	} );
} );

describe( 'formatURL', () => {
	it.each( [
		[
			'breaks the URL before each query parameter and escapes the ampersand',
			'https://example.org/?foo=1&bar=2',
			'https://example.org/<br>?foo=1<br>&amp;bar=2',
		],
		[
			'leaves a URL with no query string on one line',
			'https://example.org/foo',
			'https://example.org/foo',
		],
	] )( '%s', ( _description, url, expected ) => {
		expect( html( Utils.formatURL( url ) ) ).toBe( expected );
	} );
} );

describe( 'getAssetDisplay', () => {
	it.each( [
		[
			'an absolute URL',
			'https://example.org/wp-includes/js/foo.js',
			'wp-includes/js/foo.js',
		],
		[
			'a protocol-relative URL',
			'//example.org/foo.js',
			'foo.js',
		],
		[
			'a versioned URL',
			'https://example.org/foo.js?ver=1.0',
			'foo.js',
		],
		[
			'a URL with other query args',
			'https://example.org/foo.js?ver=1.0&a=b',
			'foo.js?a=b',
		],
		[
			'an empty URL',
			'',
			'',
		],
	] )( 'displays %s as a path', ( _label, absolute, expected ) => {
		expect( Utils.getAssetDisplay( {
			host: 'example.org',
			local: true,
			absolute,
		} ) ).toBe( expected );
	} );
} );

describe( 'arrayIntersect', () => {
	it( 'returns an empty array when given no arrays', () => {
		expect( Utils.arrayIntersect( [] ) ).toEqual( [] );
	} );

	it( 'returns the values present in every array', () => {
		expect( Utils.arrayIntersect( [
			[ 'a', 'b', 'c' ],
			[ 'b', 'c', 'd' ],
			[ 'c', 'b' ],
		] ) ).toEqual( [ 'b', 'c' ] );
	} );

	it( 'returns an empty array when nothing is common', () => {
		expect( Utils.arrayIntersect( [ [ 'a' ], [ 'b' ] ] ) ).toEqual( [] );
	} );
} );

describe( 'arrayCountValues', () => {
	it( 'counts the occurrences of each value', () => {
		expect( Utils.arrayCountValues( [ 'a', 'b', 'a', 'a' ] ) ).toEqual( {
			a: 3,
			b: 1,
		} );
	} );
} );
