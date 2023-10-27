import * as React from 'react';

interface NonTabularProps {
	id?: string;
	title?: string;
	children: React.ReactNode;
}

export const NonTabular = ( { title, children }: NonTabularProps ) => (
			<div className="qm-panel-container">
				<div
					aria-labelledby="qm-panel-title"
					className="qm qm-panel-show qm-non-tabular"
					role="tabpanel"
					tabIndex={ -1 }
				>
					<div className="qm-boxed">
						<h2 id="qm-panel-title">
							{ title ?? '@TODO' }
						</h2>
						{ children }
					</div>
				</div>
			</div>
		);
