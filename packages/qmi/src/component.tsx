import {
	Component,
} from 'qmi/data-types';
import * as React from 'react';

interface iComponentProps {
	component: Component;
}

export class QMComponent extends React.Component<iComponentProps, Record<string, unknown>> {

	render() {
		return (
			<td className="qm-nowrap">{ this.props.component.name }</td>
		);
	}

}
