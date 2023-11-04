import * as React from 'react';
import { PanelContext } from './panel-context';

interface NonTabularProps {
	title?: string;
	children: React.ReactNode;
}

export const NonTabularPanel = ( { title, children }: NonTabularProps ) => {
	const {
		id,
	} = React.useContext( PanelContext );

	return (
		<div
			aria-labelledby="qm-panel-title"
			className="qm qm-panel-show qm-non-tabular"
			id={ `qm-${id}` }
			role="tabpanel"
			tabIndex={ -1 }
		>
			<div className="qm-boxed">
				{ title && (
					<h2 id="qm-panel-title">
						{ title }
					</h2>
				) }
				{ children }
			</div>
		</div>
	);
};
