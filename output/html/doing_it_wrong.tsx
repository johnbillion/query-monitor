import {
	Caller,
	iPanelProps,
	Notice,
	PanelFooter,
	QMComponent,
	Tabular,
} from 'qmi';
import {
	DataTypes,
} from 'qmi/data-types';
import * as React from 'react';

import {
	__,
} from '@wordpress/i18n';

export default ( { data, id }: iPanelProps<DataTypes['Doing_It_Wrong']> ) => {
	if ( ! data.actions?.length ) {
		return (
			<Notice id={ id }>
				<p>
					{ __( 'No occurrences.', 'query-monitor' ) }
				</p>
			</Notice>
		);
	}

	return (
		<Tabular id={ id }>
			<thead>
				<tr>
					<th scope="col">
						{ __( 'Message', 'query-monitor' ) }
					</th>
					<th scope="col">
						{ __( 'Caller', 'query-monitor' ) }
					</th>
					<th scope="col">
						{ __( 'Component', 'query-monitor' ) }
					</th>
				</tr>
			</thead>
			<tbody>
				{ data.actions.map( ( action ) => (
					<tr>
						<td>
							{ action.message }
						</td>
						<Caller trace={ action.trace } />
						<QMComponent component={ action.trace.component } />
					</tr>
				) ) }
			</tbody>
			<PanelFooter
				cols={ 3 }
				count={ data.actions.length }
			/>
		</Tabular>
	);
};
