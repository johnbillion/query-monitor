import { NonTabularPanel } from '../panels/non-tabular-panel';
import { DataTypes } from '../data-types';
import { PanelProps } from '../types';
import { __ } from '@wordpress/i18n';
import { PanelMenuItem } from '../panels/panel-registry';

export const conditionalsMenu = ( data: DataTypes['conditionals'] ): PanelMenuItem[] => {
	// One entry per true conditional in the admin toolbar, collapsed to a single
	// entry in the panel navigation menu.
	const items: PanelMenuItem[] = data.conds.true.map( ( cond ) => ( {
		id: `conditionals-${ cond }`,
		panel: 'conditionals',
		title: `${ cond }()`,
		nav: false,
	} ) );

	items.push( {
		id: 'conditionals',
		panel: 'conditionals',
		title: __( 'Conditionals', 'query-monitor' ),
		adminBar: false,
	} );

	return items;
};

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
