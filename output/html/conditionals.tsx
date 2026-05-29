import { NonTabularPanel } from '../panels/non-tabular-panel';
import { DataTypes } from '../data-types';
import { PanelProps } from '../types';
import { __ } from '@wordpress/i18n';

export const Conditionals = ( { data }: PanelProps<DataTypes['conditionals']> ) => {
	const trueConds = data.conds['true'];
	const falseConds = data.conds['false'];

	return (
		<NonTabularPanel title={ __( 'Conditionals', 'query-monitor' ) }>
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
		</NonTabularPanel>
	);
};
