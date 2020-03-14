import React, { Component } from 'react';

class NonTabular extends Component {

	render() {
		const caption = `qm-${this.props.id}-caption`;
		return (
			<div class="qm qm-non-tabular" id={`qm-${this.props.id}`} role="tabpanel" aria-labelledby={caption} tabindex="-1">
				<h2 class="qm-screen-reader-text" id={caption}>
					@TODO
				</h2>
				{this.props.children}
			</div>
		);
	}

}

export default NonTabular;
