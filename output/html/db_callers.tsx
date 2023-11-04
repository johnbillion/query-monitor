import {
	iPanelProps,
	Panel,
	Time,
	TotalTime,
} from 'qmi';
import {
	DataTypes,
} from 'qmi/data-types';
import * as React from 'react';

import { __ } from '@wordpress/i18n';

export default ( { data }: iPanelProps<DataTypes['DB_Queries']> ) => {
	if ( ! data.times ) {
		return null;
	}

	return (
		<Panel>
			<table>
				<caption>
					<h2 id="qm-panel-title">
						{ __( 'Queries by Caller', 'query-monitor' ) }
					</h2>
				</caption>
				<thead>
					<tr>
						<th scope="col">
							{ __( 'Caller', 'query-monitor' ) }
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
					{ Object.values( data.times ).map( caller => (
						<tr key={ caller.caller }>
							<td>
								{ caller.caller }
							</td>
							{ Object.keys( data.types ).map( key => (
								<td key={ key } className="qm-num">
									{ caller.types[key] || '' }
								</td>
							) ) }
							<td>
								<Time value={ caller.ltime }/>
							</td>
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
			</table>
		</Panel>
	);
};
