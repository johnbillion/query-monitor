import React, { Component } from 'react';

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
