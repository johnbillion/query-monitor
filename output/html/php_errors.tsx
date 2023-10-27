import * as classNames from 'classnames';
import {
	iPanelProps,
	Component,
	Tabular,
	Warning,
} from 'qmi';
import {
	DataTypes,
} from 'qmi/data-types';
import * as React from 'react';

import { __ } from '@wordpress/i18n';

export default ( { data, id }: iPanelProps<DataTypes['PHP_Errors']> ) => {
	if ( ! data.errors ) {
		return null;
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
						{ __( 'Location', 'query-monitor' ) }
					</th>
					<th className="qm-num" scope="col">
						{ __( 'Count', 'query-monitor' ) }
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
								<td className="qm-row-caller qm-row-stack qm-nowrap qm-ltr">
									{ error.filename }:{ error.line }
								</td>
								<td className="qm-num">
									{ error.calls }
								</td>
								{ error.trace ? (
									<Component component={ error.trace.component } />
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
};
