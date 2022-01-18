import * as React from 'react';

export interface TabularProps {
	id: string;
}

export class Tabular extends React.Component<TabularProps, Record<string, unknown>> {

	render() {
		const caption = `qm-${this.props.id}-caption`;
		return (
			<div className="qm-panel-container" id={ `qm-${this.props.id}-container` }>
				<div
					aria-labelledby={ caption }
					className="qm qm-panel-show"
					id={ `qm-${this.props.id}` }
					role="tabpanel"
					tabIndex={ -1 }
				>
					<table className="qm-sortable">
						<caption>
							<h2 id={ caption }>
								@TODO
							</h2>
						</caption>
						{ this.props.children }
					</table>
				</div>
			</div>
		);
	}

}
