import * as React from "react";

export class QMComponent extends React.Component {

	render() {
		return (
			<td className="qm-nowrap">{this.props.component.name}</td>
		);
	}

}
