import React, { Component } from 'react';

class QMComponent extends Component {

	render() {
		return (
			<td class="qm-nowrap">{this.props.component.name}</td>
		);
	}

}

export default QMComponent;
