import * as React from "react";

export interface TabularProps {
	id: string;
}

export class Tabular extends React.Component<TabularProps, {}> {

	render() {
		const caption = `qm-${this.props.id}-caption`;
		return (
			<div className="qm-panel-container" id={`qm-${this.props.id}-container`}>
				<div className="qm" id={`qm-${this.props.id}`} role="tabpanel" aria-labelledby={caption} tabIndex={-1}>
					<table className="qm-sortable">
						<caption className="qm-screen-reader-text">
							<h2 id={caption}>
								@TODO
							</h2>
						</caption>
						{this.props.children}
					</table>
				</div>
			</div>
		);
	}

}
