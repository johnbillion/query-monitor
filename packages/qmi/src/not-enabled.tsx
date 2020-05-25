import * as React from "react";
import { Notice } from 'qmi';

export class NotEnabled extends React.Component {

	render() {
		return (
			<Notice id={this.props.id}>
				{this.props.children}
			</Notice>
		);
	}

}
