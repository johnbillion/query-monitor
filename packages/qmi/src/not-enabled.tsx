import * as React from "react";
import { Notice } from 'qmi';

export class NotEnabled extends React.Component {

	render() {
		const { id } = this.props as any;

		return (
			<Notice id={id}>
				{this.props.children}
			</Notice>
		);
	}

}
