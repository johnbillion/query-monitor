import React, { Component } from 'react';

export class NotEnabled extends Component {

	render() {
		return (
			<Notice id={this.props.id}>
				{this.props.children}
			</Notice>
		);
	}

}
