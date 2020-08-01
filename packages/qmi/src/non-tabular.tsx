import * as React from "react";

export interface NonTabularProps {
	id: string;
}

export class NonTabular extends React.Component<NonTabularProps, {}> {

	render() {
		const caption = `qm-${this.props.id}-caption`;
		return (
			<div className="qm-panel-container" id={`qm-${this.props.id}-container`}>
				<div className="qm qm-non-tabular" id={`qm-${this.props.id}`} role="tabpanel" aria-labelledby={caption} tabIndex={-1}>
					<div className="qm-boxed">
						<h2 className="qm-screen-reader-text" id={caption}>
							@TODO
						</h2>
						{this.props.children}
					</div>
				</div>
			</div>
		);
	}

}
