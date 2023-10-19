import {
	iPanelProps,
	NonTabular,
} from 'qmi';
import {
	DataTypes,
} from 'qmi/data-types';
import * as React from 'react';

import { __ } from '@wordpress/i18n';

class Conditionals extends React.Component<iPanelProps<DataTypes['Conditionals']>, Record<string, unknown>> {

	render() {
		const trueConds = this.props.data.conds['true'];
		const falseConds = this.props.data.conds['false'];

		return (
			<NonTabular id={ this.props.id }>
				<div className="qm-boxed">
					<section>
						<h3>
							{ __( 'True Conditionals', 'query-monitor' ) }
						</h3>
						<ul>
							{ trueConds.map( cond => (
								<li key={ cond } className="qm-ltr qm-true">
									<code>
										{ cond }()
									</code>
								</li>
							) ) }
						</ul>
					</section>
				</div>
				<div className="qm-boxed">
					<section>
						<h3>
							{ __( 'False Conditionals', 'query-monitor' ) }
						</h3>
						<ul>
							{ falseConds.map( cond => (
								<li key={ cond } className="qm-ltr qm-false">
									<code>
										{ cond }()
									</code>
								</li>
							) ) }
						</ul>
					</section>
				</div>
			</NonTabular>
		);
	}

}

export default Conditionals;
