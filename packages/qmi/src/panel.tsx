import * as React from 'react';

interface Props {
	children: React.ReactNode;
}

export const Panel = ( { children }: Props ) => (
	<div
		aria-labelledby="qm-panel-title"
		className="qm qm-panel-show"
		role="tabpanel"
		tabIndex={ -1 }
	>
		{ children }
	</div>
);
