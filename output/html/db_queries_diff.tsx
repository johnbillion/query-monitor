import { NonTabularPanel } from '../panels/non-tabular-panel';
import { MainContext } from '../contexts/main-context';
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

	const queryDiffResult = useMemo( () => {
		if ( ! queryDiffEnabled ) {
			return null;
		}

		// @TODO: Avoid calculating the diff in every render if the queries haven't changed. This could be done by memoizing the result based on the queries, or by storing the previous queries in a ref and only recalculating when they change.
		return getQueryDiffResult( extractQueries( data.rows ) );
	}, [ queryDiffEnabled, data.rows ] );

	if ( ! queryDiffEnabled ) {
		return (
			<NonTabularPanel>
				<section>
					<h3>{ __( 'Query Diff', 'query-monitor' ) }</h3>
					<p>
						{ __( 'Query diff tracking is disabled. Enable it in the Settings panel to compare database queries between page loads.', 'query-monitor' ) }
					</p>
				</section>
			</NonTabularPanel>
		);
	}

	if ( ! queryDiffResult || queryDiffResult.status === 'waiting' ) {
		return (
			<NonTabularPanel>
				<section>
					<h3>{ __( 'Query Diff', 'query-monitor' ) }</h3>
					<p>
						{ __( 'Refresh this page to compare queries between page loads.', 'query-monitor' ) }
					</p>
				</section>
			</NonTabularPanel>
		);
	}

	const { added, removed, previousCount, currentCount } = queryDiffResult;

	if ( ! added.length && ! removed.length ) {
		return (
			<NonTabularPanel>
				<section>
					<h3>{ __( 'Query Diff', 'query-monitor' ) }</h3>
					<p>
						{ __( 'No query changes detected between page loads.', 'query-monitor' ) }
					</p>
				</section>
			</NonTabularPanel>
		);
	}

	return (
		<NonTabularPanel>
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
