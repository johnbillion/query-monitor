import { NonTabularPanel } from '../panels/non-tabular-panel';
import { type ComponentChildren } from 'preact';
import { MainContext } from '../contexts/main-context';
import { Warning } from '../components/warning';
import * as Utils from '../utils';
import { DataTypes } from '../data-types';
import { PanelProps } from '../types';
import { useContext, useMemo } from 'preact/hooks';
import { extractQueries, getQueryDiffResult } from '../../src/query-diff';
import type { QuerySnapshot } from '../../src/query-diff';

import {
	__,
	sprintf,
} from '@wordpress/i18n';

export const DBQueriesDiff = ( { data }: PanelProps<DataTypes['db_queries']> ) => {
	const {
		queryDiffEnabled,
	} = useContext( MainContext );

	// Queries were performed but not logged, e.g. with SAVEQUERIES defined as
	// false. Skipping the diff computation also avoids saving an empty
	// snapshot that the next page load would be compared against.
	const queriesNotLogged = !! data.total_qs && ! data.rows?.length;

	const queryDiffResult = useMemo( () => {
		if ( ! queryDiffEnabled || queriesNotLogged ) {
			return null;
		}

		return getQueryDiffResult( extractQueries( data.rows ) );
	}, [ queryDiffEnabled, queriesNotLogged, data.rows ] );

	if ( ! queryDiffEnabled ) {
		return (
			<QueryDiffMessage>
				{ __( 'Query diff tracking is disabled. Enable it in the Settings panel to compare database queries between page loads.', 'query-monitor' ) }
			</QueryDiffMessage>
		);
	}

	if ( queriesNotLogged ) {
		return (
			<QueryDiffMessage>
				{ __( 'Database queries were not logged, so query diffs cannot be tracked between page loads.', 'query-monitor' ) }
			</QueryDiffMessage>
		);
	}

	if ( queryDiffResult?.status === 'error' ) {
		return (
			<QueryDiffMessage error>
				<Warning>
					{ __( 'Browser session storage could not be accessed, so query diffs cannot be tracked between page loads. This can happen in private browsing mode or when the storage quota has been exceeded.', 'query-monitor' ) }
				</Warning>
			</QueryDiffMessage>
		);
	}

	if ( ! queryDiffResult || queryDiffResult.status === 'waiting' ) {
		return (
			<QueryDiffMessage>
				{ __( 'Refresh this page to compare queries between page loads.', 'query-monitor' ) }
			</QueryDiffMessage>
		);
	}

	const { added, removed, previousCount, currentCount } = queryDiffResult;

	if ( ! added.length && ! removed.length ) {
		return (
			<QueryDiffMessage>
				{ __( 'No query changes detected between page loads.', 'query-monitor' ) }
			</QueryDiffMessage>
		);
	}

	return (
		<NonTabularPanel title={ __( 'Query Diff', 'query-monitor' ) }>
			<section>
				<h3>{ __( 'Query Diff', 'query-monitor' ) }</h3>
				<div className="qm-query-diff-summary">
					<p>
						{ sprintf(
							/* translators: 1: Previous query count, 2: Current query count */
							__( 'Previous: %1$s queries. Current: %2$s queries.', 'query-monitor' ),
							previousCount,
							currentCount
						) }
					</p>
				</div>
			</section>

			{ added.length > 0 && (
				<QueryDiffSection
					type="added"
					title={ sprintf(
						/* translators: %s: Number of added queries */
						__( 'Added (%s)', 'query-monitor' ),
						added.length
					) }
					queries={ added }
				/>
			) }

			{ removed.length > 0 && (
				<QueryDiffSection
					type="removed"
					title={ sprintf(
						/* translators: %s: Number of removed queries */
						__( 'Removed (%s)', 'query-monitor' ),
						removed.length
					) }
					queries={ removed }
				/>
			) }
		</NonTabularPanel>
	);
};

interface QueryDiffMessageProps {
	error?: boolean;
	children: ComponentChildren;
}

const QueryDiffMessage = ( { error = false, children }: QueryDiffMessageProps ) => (
	<NonTabularPanel title={ __( 'Query Diff', 'query-monitor' ) }>
		<section className={ error ? 'qm-error' : undefined }>
			<h3>{ __( 'Query Diff', 'query-monitor' ) }</h3>
			<p>
				{ children }
			</p>
		</section>
	</NonTabularPanel>
);

interface QueryDiffSectionProps {
	type: 'added' | 'removed';
	title: string;
	queries: QuerySnapshot[];
}

const QueryDiffSection = ( { type, title, queries }: QueryDiffSectionProps ) => (
	<section className={ `qm-query-diff-${ type }` }>
		<h3>{ title }</h3>
		<ul>
			{ queries.map( ( query, i ) => (
				<li key={ i }>
					<code className="qm-query-diff-sql">
						{ Utils.formatSQL( query.sql ) }
					</code>
					{ query.caller && (
						<span className="qm-query-diff-caller">
							{ query.caller }
						</span>
					) }
				</li>
			) ) }
		</ul>
	</section>
);
