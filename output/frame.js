import React, { Component } from 'react';

export class Frame extends Component {

	render() {
		return (
			<code>{this.props.frame.display}</code>
		);
	}

}
