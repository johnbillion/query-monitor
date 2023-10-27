import {
	iPanelProps,
	NonTabular,
} from 'qmi';
import {
	DataTypes,
} from 'qmi/data-types';
import * as React from 'react';

import { __ } from '@wordpress/i18n';

export default ( { data, id }: iPanelProps<DataTypes['Conditionals']> ) => {
		const trueConds = data.conds['true'];
		const falseConds = data.conds['false'];

		return (
			<NonTabular id={ id }>
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
