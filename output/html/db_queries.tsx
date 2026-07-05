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
import { PanelMenuItem } from '../panels/panel-registry';
import { useContext } from 'preact/hooks';

import {
	__,
	_n,
	_x,
	sprintf,
} from '@wordpress/i18n';

export const dbQueriesMenu = ( data: DataTypes['db_queries'] ): PanelMenuItem[] => {
	const children: PanelMenuItem[] = [];

	if ( data.errors?.length ) {
		children.push( {
			id: 'db_errors',
			panel: 'db_errors',
			title: __( 'Database Errors', 'query-monitor' ),
			warning_count: data.errors.length,
		} );
	}

	if ( data.expensive?.length ) {
		children.push( {
			id: 'db_expensive',
			panel: 'db_expensive',
			title: __( 'Slow Queries', 'query-monitor' ),
			notice_count: data.expensive.length,
		} );
	}

	if ( data.dupes?.length ) {
		children.push( {
			id: 'db_dupes',
			panel: 'db_dupes',
			title: __( 'Duplicate Queries', 'query-monitor' ),
			notice_count: data.dupes.reduce( ( sum, dupe ) => sum + dupe.count, 0 ),
			adminBar: false,
		} );
	}

	if ( data.rows?.length ) {
		children.push( {
			id: 'db_callers',
			panel: 'db_callers',
			title: __( 'Queries by Caller', 'query-monitor' ),
			adminBar: false,
		} );
	}

	if ( data.rows?.length && data.has_trace ) {
		children.push( {
			id: 'db_components',
			panel: 'db_components',
			title: __( 'Queries by Component', 'query-monitor' ),
			adminBar: false,
		} );
	}

	const okCount = ( data.total_qs ?? 0 );

	return [ {
		id: 'db_queries',
		panel: 'db_queries',
		title: __( 'Database Queries', 'query-monitor' ),
		ok_count: okCount || null,
		children,
	} ];
};

export const dbQueriesTitle = ( data: DataTypes['db_queries'] ): string[] => {
	const titles: string[] = [];

	const queryCount = (): string => (
		sprintf(
			/* translators: %s: Number of database queries. Note the space between value and unit symbol. */
			_n( '%s Q', '%s Q', data.total_qs, 'query-monitor' ) || '%s Q',
			Utils.numberFormat( data.total_qs ),
		).replace( /\s?([^0-9,.]+)/g, '<small>$1</small>' )
	);

	if ( data.rows ) {
		const totalTime = data.rows.reduce( ( sum, row ) => sum + row.ltime, 0 );

		titles.push(
			sprintf(
				/* translators: %s: A time in seconds with a decimal fraction. No space between value and unit symbol. */
				_x( '%ss', 'Time in seconds', 'query-monitor' ),
				Utils.numberFormat( totalTime, 2 )
			)
		);
		titles.push( queryCount() );
	} else if ( data.total_qs != null ) {
		titles.push( queryCount() );
	}

	return titles;
};

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
	const promptReason = ! data.has_trace ? data.extended_query_prompt_reason : null;

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
				className: 'qm-i',
				heading: '#',
				render: ( row, i ) => ( i + 1 ),
			},
			sql: {
				heading: __( 'Query', 'query-monitor' ),
				render: ( row ) => (
					<>
						<code>
							{ Utils.formatSQL( row.sql ) }
						</code>
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
				),
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
