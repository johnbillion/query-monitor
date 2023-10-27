import * as classNames from 'classnames';
import {
	Caller,
	iPanelProps,
	Component,
	Tabular,
	Warning,
	Notice,
} from 'qmi';
import {
	DataTypes,
} from 'qmi/data-types';
import * as React from 'react';

import { __ } from '@wordpress/i18n';

export default ( { data, id }: iPanelProps<DataTypes['Logger']> ) => {
	if ( ! data.logs || ! data.logs.length ) {
		return (
			<Notice id={ id }>
				<p>
					{ __( 'No data logged.', 'query-monitor' ) }
				</p>
				<p>
					<a href="https://querymonitor.com/blog/2018/07/profiling-and-logging/">
						{ __( 'Read about profiling and logging in Query Monitor.', 'query-monitor' ) }
					</a>
				</p>
			</Notice>
		);
	}

	return (
		<Tabular id={ id }>
			<thead>
				<tr>
					<th scope="col">
						{ __( 'Level', 'query-monitor' ) }
					</th>
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
				{ data.logs.map( ( row ) => {
					const warn = data.warning_levels.includes( row.level );
					const classes = {
						'qm-warn': warn,
					};

					return (
						<tr key={ `${ row.level }${ row.message }` } className={ classNames( classes ) }>
							<td>
								{ warn && ( <Warning /> ) }
								{ row.level }
							</td>
							<td>
								{ row.message }
							</td>
							<Caller trace={ row.trace } />
							<Component component={ row.trace.component } />
						</tr>
					);
				} ) }
			</tbody>
		</Tabular>
	);
};
