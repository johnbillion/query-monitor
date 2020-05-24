import React, { Component } from 'react';

export class QMComponent extends Component {

	render() {
		return (
			<td className="qm-nowrap">{this.props.component.name}</td>
		);
	}

}
