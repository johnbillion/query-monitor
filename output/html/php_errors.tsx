import * as classNames from 'classnames';
import {
	iPanelProps,
	QMComponent,
	Tabular,
	Warning,
} from 'qmi';
import * as React from 'react';

import { __ } from '@wordpress/i18n';

class PHPErrors extends React.Component<iPanelProps, Record<string, unknown>> {

	render() {
		const { data } = this.props;

		if ( ! data.errors ) {
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
						<th className="qm-num" scope="col">
							{ __( 'Count', 'query-monitor' ) }
						</th>
						<th scope="col">
							{ __( 'Location', 'query-monitor' ) }
						</th>
						<th scope="col">
							{ __( 'Component', 'query-monitor' ) }
						</th>
					</tr>
				</thead>
				<tbody>
					{ Object.keys( data.errors ).map( type => {
						const errors = data.errors[type];

						return Object.keys( errors ).map( id => {
							const error = errors[id];
							const warn = ( type === 'warning' );
							const classes = classNames( {
								'qm-warn': warn,
							} );

							return (
								<tr key={ `${ error.message }${ error.filename }${ error.line }` } className={ classes }>
									<td className="qm-nowrap">
										{ warn && ( <Warning /> ) }
										{ type }
									</td>
									<td className="qm-ltr">
										{ error.message }
									</td>
									<td className="qm-num">
										{ error.calls }
									</td>
									<td className="qm-row-caller qm-row-stack qm-nowrap qm-ltr">
										{ error.filename }:{ error.line }
									</td>
									{ error.component ? (
										<QMComponent component={ error.component } />
									) : (
										<td>
											{ __( 'Unknown', 'query-monitor' ) }
										</td>
									) }
								</tr>
							);
						} );
					} ) }
				</tbody>
			</Tabular>
		);
	}

}

export default PHPErrors;
