import * as React from "react";
import { NonTabular } from 'qmi';

interface iNoticeProps {
	id: string;
}

export class Notice extends React.Component<iNoticeProps, {}> {

	render() {
		return (
			<NonTabular id={this.props.id}>
				<section>
					<div className="qm-notice">
						{this.props.children}
					</div>
				</section>
			</NonTabular>
		);
	}

}
