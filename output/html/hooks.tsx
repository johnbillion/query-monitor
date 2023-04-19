import {
	iPanelProps,
	Notice,
	QMComponent,
	Tabular,
	Warning,
} from 'qmi';
import {
	Hooks as HooksData,
} from 'qmi/data-types';
import * as React from 'react';

import {
	__,
	sprintf,
} from '@wordpress/i18n';

interface iHooksProps extends iPanelProps {
	data: HooksData;
}

class Hooks extends React.Component<iHooksProps, Record<string, unknown>> {

	render() {
		const { data } = this.props;

		if ( ! data.hooks?.length ) {
			return (
				<Notice id={ this.props.id }>
					<p>
						{ __( 'No hooks were recorded.', 'query-monitor' ) }
					</p>
				</Notice>
			);
		}

		return (
			<Tabular id={ this.props.id }>
				<thead>
					<tr>
						<th scope="col">
							{ __( 'Hook', 'query-monitor' ) }
						</th>
						<th scope="col">
							{ __( 'Priority', 'query-monitor' ) }
						</th>
						<th scope="col">
							{ __( 'Action', 'query-monitor' ) }
						</th>
						<th scope="col">
							{ __( 'Component', 'query-monitor' ) }
						</th>
					</tr>
				</thead>
				<tbody>
					{ data.hooks.map( hook => {
						if ( ! hook.actions.length ) {
							return (
								<tr key={ hook.name }>
									<th className="qm-ltr" scope="row">
										<code>{ hook.name }</code>
									</th>
									<td></td>
									<td></td>
									<td></td>
								</tr>
							);
						}

						return (
							<React.Fragment key={ hook.name }>
								{ hook.actions.map( ( action, i ) => (
									<tr key={ `${hook.name} ${action.callback.name} ${action.priority}` }>
										{ i === 0 && (
											<th className="qm-ltr qm-nowrap" rowSpan={ hook.actions.length }>
												<span className="qm-sticky">
													<code>
														{ hook.name }
													</code>
												</span>
												{ hook.name === 'all' && (
													<span className="qm-warn">
														<br/>
														<Warning/>
														{ sprintf(
															/* translators: %s: Action name */
															__( 'Warning: The %s action is extremely resource intensive. Try to avoid using it.', 'query-monitor' ),
															'all'
														) }
													</span>
												) }
											</th>
										) }
										<td className="qm-num">
											{ action.priority }
										</td>
										<td className="qm-nowrap">
											<code>
												{ action.callback.name }
											</code>
										</td>
										<QMComponent component={ action.callback.component } />
									</tr>
								) ) }
							</React.Fragment>
						);
					} ) }
				</tbody>
			</Tabular>
		);
	}

}

export default Hooks;
