import * as React from "react";

interface iComponentProps {
	component: any;
}

export class QMComponent extends React.Component<iComponentProps, {}> {

	render() {
		return (
			<td className="qm-nowrap">{this.props.component.name}</td>
		);
	}

}
