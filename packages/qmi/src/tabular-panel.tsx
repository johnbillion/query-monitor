import * as React from 'react';

interface TabularProps {
	id?: string;
	title: string;
	children: React.ReactNode;
}

export const TabularPanel = ( { children, title }: TabularProps ) => (
	<div
		aria-labelledby="qm-panel-title"
		className="qm qm-panel-show"
		role="tabpanel"
		tabIndex={ -1 }
	>
		<table>
			<caption>
				<h2 id="qm-panel-title">
					{ title }
				</h2>
			</caption>
			{ children }
		</table>
	</div>
);
