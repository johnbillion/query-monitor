import {
	iPanelProps,
	Notice,
	Component,
	Tabular,
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

export default ( { data, id }: iPanelProps<DataTypes['Hooks']> ) => {
	if ( ! data.hooks?.length ) {
		return (
			<Notice id={ id }>
				<p>
					{ __( 'No hooks were recorded.', 'query-monitor' ) }
				</p>
			</Notice>
		);
	}

	return (
		<Tabular id={ id }>
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
												<>
													<br/>
													<Warning>
														{ sprintf(
															/* translators: %s: Action name */
															__( 'Warning: The %s action is extremely resource intensive. Try to avoid using it.', 'query-monitor' ),
															'all'
														) }
													</Warning>
												</>
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
									<Component component={ action.callback.component } />
								</tr>
							) ) }
						</React.Fragment>
					);
				} ) }
			</tbody>
		</Tabular>
	);
};
