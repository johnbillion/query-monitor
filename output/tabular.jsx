import React, { Component } from 'react';

class Tabular extends Component {

	render() {
		const caption = 'qm-' + this.props.id + '-caption';
		return (
			<div class="qm" id={'qm-' + this.props.id} role="tabpanel" aria-labelledby={caption} tabindex="-1">
				<table class="qm-sortable">
					<caption class="qm-screen-reader-text"><h2 id={caption}>%2$s</h2></caption>
					{this.props.children}
				</table>
			</div>
		);
	}

}

export default Tabular;
