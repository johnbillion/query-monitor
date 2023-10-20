import * as React from 'react';

export interface TabularProps {
	id?: string;
	title?: string;
	children: React.ReactNode;
}

export class Tabular extends React.Component<TabularProps, Record<string, unknown>> {

	render() {
		return (
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
								{ this.props.title ?? '@TODO' }
							</h2>
						</caption>
						{ this.props.children }
					</table>
				</div>
			</div>
		);
	}

}
