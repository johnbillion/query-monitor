import * as React from 'react';

interface TabularProps {
	id?: string;
	title?: string;
	children: React.ReactNode;
}

export const Tabular = ( { children, title }: TabularProps ) => (
			<div className="qm-panel-container">
				<div
					aria-labelledby="qm-panel-title"
					className="qm qm-panel-show"
					role="tabpanel"
					tabIndex={ -1 }
				>
					<table className="qm-sortable">
						<caption>
							<h2 id="qm-panel-title">
								{ title ?? '@TODO' }
							</h2>
						</caption>
						{ children }
					</table>
				</div>
			</div>
		);
