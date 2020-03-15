import React, { Component } from 'react';

class QMComponent extends Component {

	render() {
		return (
			<td className="qm-nowrap">{this.props.component.name}</td>
		);
	}

}

export default QMComponent;
