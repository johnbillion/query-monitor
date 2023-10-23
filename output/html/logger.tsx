import * as classNames from 'classnames';
import {
	Caller,
	iPanelProps,
	QMComponent,
	Tabular,
	Warning,
} from 'qmi';
import {
	DataTypes,
} from 'qmi/data-types';
import * as React from 'react';

import { __ } from '@wordpress/i18n';

class Logger extends React.Component<iPanelProps<DataTypes['Logger']>, Record<string, unknown>> {

	render() {
		const { data } = this.props;

		if ( ! data.logs || ! data.logs.length ) {
			return null;
		}

		return (
			<Tabular id={ this.props.id }>
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
								<Caller toggleLabel={ __( 'View call stack', 'query-monitor' ) } trace={ row.trace } />
								<QMComponent component={ row.trace.component } />
							</tr>
						);
					} ) }
				</tbody>
			</Tabular>
		);
	}

}

export default Logger;
