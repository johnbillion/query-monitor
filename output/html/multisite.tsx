import {
	Caller,
	iPanelProps,
	Notice,
	PanelFooter,
	Component,
	Tabular,
} from 'qmi';
import {
	DataTypes,
} from 'qmi/data-types';
import * as React from 'react';

import {
	__,
	sprintf,
} from '@wordpress/i18n';

export default ( { data, id }: iPanelProps<DataTypes['Multisite']> ) => {
	if ( ! data.switches.length ) {
		return (
			<Notice id={ id }>
				<p>
					{ __( 'No data logged.', 'query-monitor' ) }
				</p>
			</Notice>
		);
	}

	return (
		<Tabular id={ id }>
			<thead>
				<tr>
					<th scope="col">
						{ __( 'Function', 'query-monitor' ) }
					</th>
					<th scope="col">
						{ __( 'Site Switch', 'query-monitor' ) }
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
				{ data.switches.map( ( row ) => (
					<tr>
						<td className="qm-nowrap">
							<code>
								{ row.to ? (
									sprintf(
										'switch_to_blog(%d)',
										row.new
									)
								) : (
									'restore_current_blog()'
								) }
							</code>
						</td>
						<Caller trace={ row.trace } />
						<Component component={ row.trace.component } />
					</tr>
				) ) }
			</tbody>
			<PanelFooter
				cols={ 3 }
				count={ data.switches.length }
			/>
		</Tabular>
	);
};
