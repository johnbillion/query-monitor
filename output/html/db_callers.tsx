import { EmptyPanel } from '../panels/empty-panel';
import { TabularPanel } from '../panels/tabular-panel';
import { getTimeCol } from '../table';
import { TotalTime } from '../components/total-time';
import { FilterLink } from '../components/filter-link';
import { Component } from '../component';
import { getQueryType, getQueryTypes } from '../utils';
import { Component as ComponentType, DataTypes } from '../data-types';
import { resolveFrame } from '../frame-lookup';
import { PanelProps } from '../types';
import { __ } from '@wordpress/i18n';

interface CallerAggregate {
	caller: string;
	ltime: number;
	types: Record<string, number>;
	components: Record<string, ComponentType>;
}

const aggregateByCaller = ( rows: NonNullable<DataTypes['db_queries']['rows']> ): CallerAggregate[] => {
	const map: Record<string, CallerAggregate> = {};

	for ( const row of rows ) {
		let caller: string | undefined;

		if ( row.trace?.frames?.length ) {
			caller = resolveFrame( row.trace.frames[0] ).id;
		} else if ( row.stack?.length ) {
			caller = row.stack[0];
		}

		if ( ! caller ) {
			continue;
		}

		if ( ! map[ caller ] ) {
			map[ caller ] = {
				caller,
				ltime: 0,
				types: {},
				components: {},
			};
		}

		const type = getQueryType( row.sql );

		map[ caller ].ltime += row.ltime;
		map[ caller ].types[ type ] = ( map[ caller ].types[ type ] || 0 ) + 1;

		const component = row.trace?.component;
		if ( component ) {
			map[ caller ].components[ `${ component.type }-${ component.context }` ] = component;
		}
	}

	return Object.values( map );
};

export const DBCallers = ( { data }: PanelProps<DataTypes['db_queries']> ) => {
	if ( ! data.rows?.length ) {
		return (
			<EmptyPanel>
				<p>
					{ __( 'None', 'query-monitor' ) }
				</p>
			</EmptyPanel>
		);
	}

	const tableData = aggregateByCaller( data.rows );

	const types = getQueryTypes( data.rows );

	const getTypeCols = () => Object.keys( types ).reduce( ( cols, type ) => ( {
		...cols,
		[ type ]: {
			heading: type,
			render: ( row: CallerAggregate ) => row.types[ type ] || '',
			className: 'qm-num',
		},
	} ), {} );

	return (
		<TabularPanel
			title={ __( 'Queries by Caller', 'query-monitor' ) }
			cols={{
				caller: {
					heading: __( 'Caller', 'query-monitor' ),
					render: ( row ) => (
						<FilterLink
							targetPanel="db_queries"
							filterName="caller"
							filterValue={ row.caller }
						>
							<code>{ row.caller }</code>
						</FilterLink>
					)
				},
				component: {
					heading: __( 'Components', 'query-monitor' ),
					render: ( row ) => Object.values( row.components ).sort( ( a, b ) => a.name.localeCompare( b.name ) ).map( ( component ) => (
						<div key={ `${ component.type }-${ component.context }` }>
							<Component component={ component } />
						</div>
					) ),
				},
				...getTypeCols(),
				time: getTimeCol( tableData ),
			}}
			orderby="time"
			order="desc"
			data={ tableData }
			footer={ () => (
				<tfoot>
					<tr>
						<td></td>
						<td></td>
						{ Object.entries( types ).map( ( [ key, value ] ) => (
							<td key={ key } className="qm-num">
								{ value }
							</td>
						) ) }
						<td className="qm-num">
							<TotalTime rows={ tableData }/>
						</td>
					</tr>
				</tfoot>
			) }
		/>
	);
};
