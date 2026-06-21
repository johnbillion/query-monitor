import { MainContext } from '../contexts/main-context';
import { EmptyPanel } from '../panels/empty-panel';
import { TabularPanel } from '../panels/tabular-panel';
import * as Utils from '../utils';
import { Warning } from '../components/warning';
import { getCallerCol, getComponentCol, getStackCol, getTimeCol } from '../table';
import { PanelFooter } from '../panels/panel-footer';
import { TotalTime } from '../components/total-time';
import { DataTypes } from '../data-types';
import { PanelProps } from '../types';
import { useContext } from 'preact/hooks';

import {
	__,
	sprintf,
} from '@wordpress/i18n';

const getExtendedQueryPromptMessage = ( reason: 'conflict' | 'disabled' | 'failed' ) => {
	switch ( reason ) {
		case 'conflict':
			return sprintf(
				/* translators: %s: File name */
				__( 'Extended query information such as the component and affected rows is not available. A conflicting %s file is present.', 'query-monitor' ),
				'db.php'
			);
		case 'disabled':
			return sprintf(
				/* translators: 1: File name, 2: Configuration constant name */
				__( 'Extended query information such as the component and affected rows is not available. Query Monitor was prevented from symlinking its %1$s file into place by the %2$s constant.', 'query-monitor' ),
				'db.php',
				'QM_DB_SYMLINK'
			);
		case 'failed':
			return sprintf(
				/* translators: %s: File name */
				__( 'Extended query information such as the component and affected rows is not available. Query Monitor was unable to symlink its %s file into place.', 'query-monitor' ),
				'db.php'
			);
	}
};

export const DBQueries = ( { data }: PanelProps<DataTypes['db_queries']> ) => {
	const { settings } = useContext( MainContext );

	if ( ! data.total_qs ) {
		return <EmptyPanel>
			<p>
				{ __( 'No queries! Nice work.', 'query-monitor' ) }
			</p>
		</EmptyPanel>
	}

	if ( ! data.rows?.length ) {
		return <EmptyPanel>
			<p>
				{ sprintf(
					/* translators: %s: Number of database queries */
					__( '%s database queries were performed, but none were logged.', 'query-monitor' ),
					Utils.numberFormat( data.total_qs )
				) }
			</p>
		</EmptyPanel>
	}

	const types = Utils.getQueryTypes( data.rows );
	const promptReason = ! data.has_trace ? settings.extended_query_prompt_reason : null;

	return <TabularPanel
		title={ __( 'Database Queries', 'query-monitor' ) }
		warning={ promptReason ? () => (
			<>
				{ getExtendedQueryPromptMessage( promptReason ) }
				{ ' ' }
				<a href="https://querymonitor.com/help/db-php-symlink/" target="_blank" rel="noopener noreferrer" className="qm-external-link">
					{ __( 'See this help page for more information.', 'query-monitor' ) }
				</a>
			</>
		) : undefined }
		cols={ {
			i: {
				className: 'qm-num',
				heading: '#',
				render: ( row, i ) => ( i + 1 ),
			},
			sql: {
				className: ( row ) => Utils.getQueryType( row.sql ) !== 'SELECT' ? 'qm-nonselectsql' : '',
				heading: __( 'Query', 'query-monitor' ),
				render: ( row ) => {
					const sql = row.sqlite_queries?.length ? (
						<ol>
							{ row.sqlite_queries.map( ( q, j ) => (
								<li key={ j }>
									<code>
										{ Utils.formatSQL( q.sql ) }
									</code>
								</li>
							) ) }
						</ol>
					) : (
						<code>
							{ Utils.formatSQL( row.sql ) }
						</code>
					);

					return (
						<>
							{ sql }
							{ Utils.isWPError( row.result ) && (
								<>
									<br />
									<br />
									<Warning>
										{ Utils.getErrorMessage( row.result ) }
									</Warning>
								</>
							) }
						</>
					);
				},
				filters: {
					options: ( () => {
						const filters = Object.keys( types ).map( ( type ) => ( {
							key: type,
							label: type,
						} ) );
						const groups = [];

						if ( filters.length > 1 ) {
							groups.push( filters );

							groups.push ( [
								{
									key: 'non-select',
									label: __( 'Non-SELECT', 'query-monitor' ),
								},
							] );
						}

						return groups;
					} )(),
					callback: ( row, value ) => {
						const type = Utils.getQueryType( row.sql );

						if ( value === 'non-select' ) {
							return ( type !== 'SELECT' );
						}

						return ( type === value );
					},
				},
				wrap: true
			},
			caller: data.has_trace ? getCallerCol( data.rows, settings ) : getStackCol( data.rows ),
			component: data.has_trace ? getComponentCol( data.rows ) : null,
			result: data.has_result ? {
				className: 'qm-num',
				heading: __( 'Rows', 'query-monitor' ),
				render: ( row ) => ( ! Utils.isWPError( row.result ) && row.result ),
			} : null,
			time: getTimeCol( data.rows, ( row, i ) => data.expensive?.includes( i ) ?? false ),
		} }
		data={ data.rows }
		rowHasError={ Utils.queryRowHasError }
		footer={ ( { cols, count, total, data: filteredData } ) => (
			<PanelFooter
				cols={ cols - 1 }
				count={ count }
				total={ total }
			>
				<td className="qm-num">
					<TotalTime rows={ filteredData }/>
				</td>
			</PanelFooter>
		) }
	/>
};
