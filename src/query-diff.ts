import type { QueryRow } from '../output/data-types';
import { resolveFrame } from '../output/frame-lookup';

export interface QuerySnapshot {
	sql: string;
	caller: string;
}

export interface QueryDiffResult {
	status: 'waiting' | 'ready';
	added: QuerySnapshot[];
	removed: QuerySnapshot[];
	previousCount: number;
	currentCount: number;
}

interface StoredSnapshot {
	url: string;
	queries: QuerySnapshot[];
	timestamp: number;
}

const STORAGE_KEY = 'qm-query-diff-data';

/**
 * Cached diff result. Since queries don't change once the page is loaded,
 * we only need to compute the diff once.
 */
let cachedDiffResult: QueryDiffResult | null = null;

/**
 * Extracts simplified query snapshots from the full query rows.
 */
export function extractQueries( rows?: QueryRow[] ): QuerySnapshot[] {
	if ( ! rows ) {
		return [];
	}

	return rows.map( ( row ) => {
		let caller = '';

		if ( row.trace?.frames?.length ) {
			caller = resolveFrame( row.trace.frames[ 0 ] ).id;
		} else if ( row.stack?.length ) {
			caller = row.stack[ 0 ];
		}

		return {
			sql: row.sql,
			caller,
		};
	} );
}

/**
 * Creates a fingerprint string for a query to use as a comparison key.
 */
function getFingerprint( query: QuerySnapshot ): string {
	return query.sql + '||' + query.caller;
}

/**
 * Builds a map of fingerprint → count for an array of queries.
 */
function buildFingerprintCounts( queries: QuerySnapshot[] ): Map<string, number> {
	const counts = new Map<string, number>();

	for ( const query of queries ) {
		const fp = getFingerprint( query );
		counts.set( fp, ( counts.get( fp ) ?? 0 ) + 1 );
	}

	return counts;
}

/**
 * Computes the diff between two sets of query snapshots.
 *
 * Uses fingerprint-based counting so duplicate queries are handled correctly:
 * if a query appears 3 times previously and 5 times now, 2 are "added".
 *
 * @TODO: Deeply review the diffing logic and test with various edge cases (e.g. all queries changed, some queries with same SQL but different callers, etc.)
 */
export function computeQueryDiff( previous: QuerySnapshot[], current: QuerySnapshot[] ): QueryDiffResult {
	const prevCounts = buildFingerprintCounts( previous );
	const currCounts = buildFingerprintCounts( current );

	// Build a lookup from fingerprint to a representative QuerySnapshot
	const prevLookup = new Map<string, QuerySnapshot>();
	for ( const query of previous ) {
		prevLookup.set( getFingerprint( query ), query );
	}
	const currLookup = new Map<string, QuerySnapshot>();
	for ( const query of current ) {
		currLookup.set( getFingerprint( query ), query );
	}

	const added: QuerySnapshot[] = [];
	const removed: QuerySnapshot[] = [];

	// Find added queries (in current but not in previous, or more occurrences)
	for ( const [ fp, currCount ] of currCounts ) {
		const prevCount = prevCounts.get( fp ) ?? 0;
		const diff = currCount - prevCount;

		if ( diff > 0 ) {
			const query = currLookup.get( fp )!;
			for ( let i = 0; i < diff; i++ ) {
				added.push( query );
			}
		}
	}

	// Find removed queries (in previous but not in current, or fewer occurrences)
	for ( const [ fp, prevCount ] of prevCounts ) {
		const currCount = currCounts.get( fp ) ?? 0;
		const diff = prevCount - currCount;

		if ( diff > 0 ) {
			const query = prevLookup.get( fp )!;
			for ( let i = 0; i < diff; i++ ) {
				removed.push( query );
			}
		}
	}

	return {
		status: 'ready',
		added,
		removed,
		previousCount: previous.length,
		currentCount: current.length,
	};
}

/**
 * Clears any stored query diff snapshot from sessionStorage.
 * Called when the user disables the feature.
 */
export function clearQuerySnapshot(): void {
	try {
		sessionStorage.removeItem( STORAGE_KEY );
	} catch {
		// Ignore storage errors.
	}
}

/**
 * Reads the previous snapshot from sessionStorage, computes a diff against the
 * provided current queries, then saves the current queries for the next page
 * load. Called by the panel component when it renders.
 *
 * Returns a "waiting" result if there is no previous snapshot or the URL
 * doesn't match.
 *
 * @TODO The snapshot is only saved when the panel renders, meaning the user
 * must open the Query Diff panel at least once per page load for the next
 * comparison to work. Consider informing users of this in the UI (e.g. in
 * the "waiting" state message or the settings toggle description).
 */
export function getQueryDiffResult( currentQueries: QuerySnapshot[] ): QueryDiffResult {
	if ( cachedDiffResult ) {
		return cachedDiffResult;
	}

	let result: QueryDiffResult | null = null;

	try {
		const stored = sessionStorage.getItem( STORAGE_KEY );

		if ( stored ) {
			const snapshot: StoredSnapshot = JSON.parse( stored );

			if ( snapshot.url === window.location.href ) {
				result = computeQueryDiff( snapshot.queries, currentQueries );
			}
		}

		// Save current queries for next page load.
		const newSnapshot: StoredSnapshot = {
			url: window.location.href,
			queries: currentQueries,
			timestamp: Date.now(),
		};
		sessionStorage.setItem( STORAGE_KEY, JSON.stringify( newSnapshot ) );
	} catch {
		// Ignore storage errors (e.g. private browsing, quota exceeded).
	}

	cachedDiffResult = result ?? {
		status: 'waiting',
		added: [],
		removed: [],
		previousCount: 0,
		currentCount: currentQueries.length,
	};

	return cachedDiffResult;
}
