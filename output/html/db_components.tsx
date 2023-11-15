import {
	PanelProps,
	TabularPanel,
	getTimeCol,
	TotalTime,
} from 'qmi';
import {
	DataTypes,
} from 'qmi/data-types';
import * as React from 'react';

import { __ } from '@wordpress/i18n';

export const DBComponents = ( { data }: PanelProps<DataTypes['DB_Queries']> ) => {
	if ( ! data.component_times ) {
		return null;
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
				render: ( row ) => row.component,
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
