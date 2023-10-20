import { Notice } from 'qmi';
import * as React from 'react';

interface iNotEnabledProps {
	children: React.ReactNode;
}

export class NotEnabled extends React.Component<iNotEnabledProps, Record<string, unknown>> {

	render() {
		const { id } = this.props as any;

		return (
			<Notice id={ id }>
				{ this.props.children }
			</Notice>
		);
	}

}
