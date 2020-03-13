import React, { Component } from 'react';

class Frame extends Component {

	render() {
		return (
			<code>{this.props.frame.display}</code>
		);
	}

}

export default Frame;
