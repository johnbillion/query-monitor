import React, { Component } from 'react';

import NonTabular from './non-tabular.jsx';

class Notice extends Component {

	render() {
		return (
			<NonTabular id={this.props.id}>
				<section>
					<div class="qm-notice">
						{this.props.children}
					</div>
				</section>
			</NonTabular>
		);
	}

}

export default Notice;
