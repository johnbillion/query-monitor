import { Notice } from 'qmi';
import * as React from 'react';

export class NotEnabled extends React.Component {

	render() {
		const { id } = this.props as any;

		return (
			<Notice id={ id }>
				{ this.props.children }
			</Notice>
		);
	}

}
