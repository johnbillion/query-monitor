import React, { Component } from 'react';

class Tabular extends Component {

	render() {
		const caption = `qm-${this.props.id}-caption`;
		return (
			<div className="qm" id={`qm-${this.props.id}`} role="tabpanel" aria-labelledby={caption} tabIndex="-1">
				<table className="qm-sortable">
					<caption className="qm-screen-reader-text">
						<h2 id={caption}>
							@TODO
						</h2>
					</caption>
					{this.props.children}
				</table>
			</div>
		);
	}

}

export default Tabular;
