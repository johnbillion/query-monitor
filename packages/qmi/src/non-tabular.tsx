import * as React from 'react';

export interface NonTabularProps {
	id?: string;
	title?: string;
	children: React.ReactNode;
}

export class NonTabular extends React.Component<NonTabularProps, Record<string, unknown>> {

	render() {
		return (
			<div className="qm-panel-container">
				<div
					aria-labelledby="qm-panel-title"
					className="qm qm-panel-show qm-non-tabular"
					role="tabpanel"
					tabIndex={ -1 }
				>
					<div className="qm-boxed">
						<h2 id="qm-panel-title">
							{ this.props.title ?? '@TODO' }
						</h2>
						{ this.props.children }
					</div>
				</div>
			</div>
		);
	}

}
