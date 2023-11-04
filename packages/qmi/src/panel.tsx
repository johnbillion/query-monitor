import * as React from 'react';
import { PanelContext } from './panel-context';

interface Props {
	children: React.ReactNode;
}

export const Panel = ( { children }: Props ) => {
	const {
		id,
	} = React.useContext( PanelContext );

	return (
		<div
			aria-labelledby="qm-panel-title"
			className="qm qm-panel-show"
			id={ `qm-${id}` }
			role="tabpanel"
			tabIndex={ -1 }
		>
			{ children }
		</div>
	);
};
