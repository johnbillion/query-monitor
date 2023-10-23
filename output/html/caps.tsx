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
	_x,
	sprintf,
} from '@wordpress/i18n';

export default class Caps extends React.Component<iPanelProps<DataTypes['Caps']>, Record<string, unknown>> {

	render() {
		if ( ! this.props.enabled ) {
			return (
				<Notice id={ this.props.id }>
					<p>
						{ sprintf(
						/* translators: %s: Configuration file name. */
							__( 'For performance reasons, this panel is not enabled by default. To enable it, add the following code to your %s file:', 'query-monitor' ),
							'wp-config.php'
						) }
					</p>
					<p>
						<code>
							define( 'QM_ENABLE_CAPS_PANEL', true );
						</code>
					</p>
				</Notice>
			);
		}

		const { data } = this.props;

		if ( ! data.caps?.length ) {
			return (
				<Notice id={ this.props.id }>
					<p>
						{ __( 'No capability checks were recorded.', 'query-monitor' ) }
					</p>
				</Notice>
			);
		}

		return (
			<Tabular id={ this.props.id }>
				<thead>
					<tr>
						<th scope="col">
							{ __( 'Capability Check', 'query-monitor' ) }
						</th>
						<th className="qm-num" scope="col">
							{ __( 'User', 'query-monitor' ) }
						</th>
						<th scope="col">
							{ __( 'Result', 'query-monitor' ) }
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
					{ data.caps.map( cap => (
						<tr>
							<td className="qm-ltr qm-nowrap">
								<code>
									{ cap.name }
									{ cap.args.map( ( arg ) => (
										`,&nbsp;${ arg }`
									) ) }
								</code>
							</td>
							<td className="qm-num">
								{ cap.user }
							</td>
							<td className="qm-nowrap">
								{ cap.result ? <span className="qm-true">true&nbsp;&#x2713;</span> : 'false' }
							</td>
							<Caller toggleLabel={ __( 'View call stack', 'query-monitor' ) } trace={ cap.trace } />
							<QMComponent component={ cap.trace.component } />
						</tr>
					) ) }
				</tbody>
				<PanelFooter
					cols={ 5 }
					count={ data.caps.length }
					label={ _x( 'Total:', 'User capability checks', 'query-monitor' ) }
				/>
			</Tabular>
		);
	}

}
