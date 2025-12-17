import {
	PanelProps,
	EmptyPanel,
	TabularPanel,
	getTimeCol,
	TotalTime,
	Component,
} from 'qmi';
import {
	DataTypes,
} from 'qmi/data-types';
import * as React from 'react';

import { __ } from '@wordpress/i18n';

export const DBComponents = ( { data }: PanelProps<DataTypes['db_queries']> ) => {
	if ( ! data.component_times || ! Object.keys( data.component_times ).length ) {
		return (
			<EmptyPanel>
				<p>
					{ __( 'None', 'query-monitor' ) }
				</p>
			</EmptyPanel>
		);
	}

	const tableData = Object.values( data.component_times ).map( row => ( {
		...row,
		types: Object.keys( data.types ).reduce( ( types, type ) => ( {
			...types,
			[ type ]: row.types[type] || '',
		} ), {} ),
	} ) );

	const getTypeCols = () => Object.keys( data.types ).reduce( ( cols, type ) => ( {
		...cols,
		[ type ]: {
			heading: type,
			render: ( row: any ) => row.types[type],
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
					{ Object.entries( data.types ).map( ( [ key, value ] ) => (
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
