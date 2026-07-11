import type { QueryRow } from '../output/data-types';
import { resolveFrame } from '../output/frame-lookup';

export interface QuerySnapshot {
	sql: string;
	caller: string;
}

export interface QueryDiffResult {
	status: 'waiting' | 'ready' | 'error';
	added: QuerySnapshot[];
	removed: QuerySnapshot[];
	previousCount: number;
	currentCount: number;
}

interface StoredSnapshot {
	url: string;
	queries: QuerySnapshot[];
	// @TODO: The timestamp is currently unused, but could be used to implement a "stale" state if the snapshot is too old.
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

		// @TODO: This caller resolution logic is duplicated in output/html/db_callers.tsx and output/table.tsx. Extract a shared getRowCaller() helper so the three stay in sync.
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
	// JSON encoding keeps the sql/caller boundary unambiguous regardless of
	// their content, unlike a plain string delimiter.
	return JSON.stringify( [ query.sql, query.caller ] );
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
 * Returns the queries that occur more often in `queries` than in `other`,
 * once per surplus occurrence.
 */
function getSurplusQueries( queries: QuerySnapshot[], other: QuerySnapshot[] ): QuerySnapshot[] {
	const unmatched = buildFingerprintCounts( other );

	return queries.filter( ( query ) => {
		const fp = getFingerprint( query );
		const remaining = unmatched.get( fp ) ?? 0;

		if ( remaining === 0 ) {
			return true;
		}

		unmatched.set( fp, remaining - 1 );
		return false;
	} );
}

/**
 * Computes the diff between two sets of query snapshots.
 *
 * Uses fingerprint-based counting so duplicate queries are handled correctly:
 * if a query appears 3 times previously and 5 times now, 2 are "added".
 */
export function computeQueryDiff( previous: QuerySnapshot[], current: QuerySnapshot[] ): QueryDiffResult {
	return {
		status: 'ready',
		added: getSurplusQueries( current, previous ),
		removed: getSurplusQueries( previous, current ),
		previousCount: previous.length,
		currentCount: current.length,
	};
}

/**
 * Clears any stored query diff snapshot from sessionStorage.
 * Called when the user disables the feature.
 *
 * Returns whether the snapshot was cleared, so the settings UI can inform
 * the user when the stored snapshot could not be removed.
 */
export function clearQuerySnapshot(): boolean {
	// The cache must be reset too, otherwise re-enabling the feature in the
	// same page load would return the cached result without re-saving the
	// snapshot, silently losing the comparison baseline for the next load.
	cachedDiffResult = null;

	try {
		sessionStorage.removeItem( STORAGE_KEY );
		return true;
	} catch {
		// Storage can be unavailable (e.g. private browsing, blocked storage
		// access). The stale snapshot remains, but sessionStorage is discarded
		// when the browser session ends anyway.
		return false;
	}
}

/**
 * Reads the previous snapshot from sessionStorage, computes a diff against the
 * provided current queries, then saves the current queries for the next page
 * load. Called by the panel component when it renders.
 *
 * Returns a "waiting" result if there is no previous snapshot or the URL
 * doesn't match, and an "error" result if sessionStorage could not be
 * accessed.
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
		// Storage can be unavailable (e.g. private browsing, blocked storage
		// access, quota exceeded) or the stored snapshot can be corrupt. Any
		// computed diff is discarded because it can't be tracked reliably.
		result = {
			status: 'error',
			added: [],
			removed: [],
			previousCount: 0,
			currentCount: currentQueries.length,
		};
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

/**
 * Resets the cached diff result. Only needed by unit tests.
 */
export function resetCachedQueryDiffResult(): void {
	cachedDiffResult = null;
}
