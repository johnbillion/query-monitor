import * as classNames from 'classnames';
import {
	Caller,
	iPanelProps,
	Notice,
	PanelFooter,
	Component,
	Tabular,
	TimeCell,
	TotalTime,
	Utils,
	Warning,
} from 'qmi';
import {
	DataTypes,
} from 'qmi/data-types';
import * as React from 'react';

import {
	__,
	sprintf,
} from '@wordpress/i18n';

export default ( { data, id }: iPanelProps<DataTypes['HTTP']> ) => {
	if ( ! data.http ) {
		return (
			<Notice id={ id }>
				<p>
					{ __( 'No HTTP API calls.', 'query-monitor' ) }
				</p>
			</Notice>
		);
	}

	return (
		<Tabular id={ id }>
			<thead>
				<tr>
					<th scope="col">
						{ __( 'Method', 'query-monitor' ) }
					</th>
					<th scope="col">
						{ __( 'URL', 'query-monitor' ) }
					</th>
					<th scope="col">
						{ __( 'Status', 'query-monitor' ) }
					</th>
					<th scope="col">
						{ __( 'Caller', 'query-monitor' ) }
					</th>
					<th scope="col">
						{ __( 'Component', 'query-monitor' ) }
					</th>
					<th className="qm-num" scope="col">
						{ __( 'Timeout', 'query-monitor' ) }
					</th>
					<th className="qm-num" scope="col">
						{ __( 'Time', 'query-monitor' ) }
					</th>
				</tr>
			</thead>
			<tbody>
				{ Object.keys( data.http ).map( key => {
					const row = data.http[key];

					return (
						<tr
							key={ key }
							className={ classNames(
								{
									'qm-warn': Utils.isWPError( row.response ),
								},
							) }
						>
							<td>
								{ row.args.method }
							</td>
							<td>
								{ Utils.formatURL( row.url ) }
							</td>
							<td>
								{ Utils.isWPError( row.response ) ? (
									<Warning>
										{ sprintf(
											__( 'Error: %s', 'query-monitor' ),
											Utils.getErrorMessage( row.response )
										) }
									</Warning>
								) : (
									`${row.response.response.code} ${row.response.response.message}`
								) }
							</td>
							<Caller trace={ row.trace } />
							<Component component={ row.trace.component } />
							<td className="qm-num">
								{ row.args.timeout }
							</td>
							<TimeCell value={ row.ltime }/>
						</tr>
					);
				} ) }
			</tbody>
			<PanelFooter
				cols={ 6 }
				count={ Object.keys( data.http ).length }
			>
				<TotalTime rows={ Object.values( data.http ) }/>
			</PanelFooter>
		</Tabular>
	);
};
