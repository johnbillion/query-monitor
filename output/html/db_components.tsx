import {
	iPanelProps,
	Tabular,
	TimeCell,
	TotalTime,
} from 'qmi';
import {
	DataTypes,
} from 'qmi/data-types';
import * as React from 'react';

import { __ } from '@wordpress/i18n';

export default ( { data, id }: iPanelProps<DataTypes['DB_Components']> ) => {
	if ( ! data.times || ! data.times.length ) {
		return null;
	}

	return (
		<Tabular id={ id }>
			<thead>
				<tr>
					<th scope="col">
						{ __( 'Component', 'query-monitor' ) }
					</th>
					{ Object.keys( data.types ).map( key => (
						<th key={ key } className="qm-num" scope="col">
							{ key }
						</th>
					) ) }
					<th className="qm-num" scope="col">
						{ __( 'Time', 'query-monitor' ) }
					</th>
				</tr>
			</thead>
			<tbody>
				{ Object.values( data.times ).map( comp => (
					<tr key={ comp.component }>
						<td>{ comp.component }</td>
						{ Object.keys( data.types ).map( key =>
							( <td key={ key } className="qm-num">
								{ comp.types[key] || '' }
							</td> )
						) }
						<TimeCell value={ comp.ltime }/>
					</tr>
				) ) }
			</tbody>
			<tfoot>
				<tr>
					<td></td>
					{ Object.entries( data.types ).map( ( [ key, value ] ) => (
						<td key={ key } className="qm-num">
							{ value }
						</td>
					) ) }
					<TotalTime rows={ Object.values( data.times ) }/>
				</tr>
			</tfoot>
		</Tabular>
	);
};
