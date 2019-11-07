import React, { Component } from 'react';
import NonTabular from './non-tabular.jsx';

class NotEnabled extends Component {

	render() {
		return (
			<Notice id={this.props.id}>
				{this.props.children}
			</Notice>
		);
	}

}

export default NotEnabled;
