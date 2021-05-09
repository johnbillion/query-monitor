import * as React from 'react';

interface iComponentProps {
	component: {
		context: string;
		name: string;
		type: string;
	};
}

export class QMComponent extends React.Component<iComponentProps, Record<string, unknown>> {

	render() {
		return (
			<td className="qm-nowrap">{ this.props.component.name }</td>
		);
	}

}
