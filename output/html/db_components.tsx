import { EmptyPanel } from '../panels/empty-panel';
import { TabularPanel } from '../panels/tabular-panel';
import { getTimeCol } from '../table';
import { TotalTime } from '../components/total-time';
import { Component } from '../component';
import { getQueryType, getQueryTypes } from '../utils';
import { DataTypes, Component as ComponentData } from '../data-types';
import { PanelProps } from '../types';
import { __ } from '@wordpress/i18n';

interface ComponentAggregate {
	component: ComponentData;
	ltime: number;
	types: Record<string, number>;
}

const aggregateByComponent = ( rows: NonNullable<DataTypes['db_queries']['rows']> ): ComponentAggregate[] => {
	const map: Record<string, ComponentAggregate> = {};

	for ( const row of rows ) {
		if ( ! row.trace?.component ) {
			continue;
		}

		const key = `${ row.trace.component.type }-${ row.trace.component.context }`;

		if ( ! map[ key ] ) {
			map[ key ] = {
				component: row.trace.component,
				ltime: 0,
				types: {},
			};
		}

		const type = getQueryType( row.sql );

		map[ key ].ltime += row.ltime;
		map[ key ].types[ type ] = ( map[ key ].types[ type ] || 0 ) + 1;
	}

	return Object.values( map );
};

export const DBComponents = ( { data }: PanelProps<DataTypes['db_queries']> ) => {
	if ( ! data.rows?.length ) {
		return (
			<EmptyPanel>
				<p>
					{ __( 'None', 'query-monitor' ) }
				</p>
			</EmptyPanel>
		);
	}

	const tableData = aggregateByComponent( data.rows );

	const types = getQueryTypes( data.rows );

	const getTypeCols = () => Object.keys( types ).reduce( ( cols, type ) => ( {
		...cols,
		[ type ]: {
			heading: type,
			render: ( row: ComponentAggregate ) => row.types[ type ] || '',
			className: 'qm-num',
		},
	} ), {} );

	return <TabularPanel
		title={ __( 'Queries by Component', 'query-monitor' ) }
		cols={{
			component: {
				heading: __( 'Component', 'query-monitor' ),
				render: ( row ) => (
					<Component
						component={ row.component }
						targetPanel="db_queries"
					/>
				),
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
};
