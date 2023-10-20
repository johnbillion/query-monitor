import { NonTabular } from 'qmi';
import * as React from 'react';

interface iNoticeProps {
	id: string;
	children: React.ReactNode;
}

export class Notice extends React.Component<iNoticeProps, Record<string, unknown>> {

	render() {
		return (
			<NonTabular id={ this.props.id }>
				<section>
					<div className="qm-notice">
						{ this.props.children }
					</div>
				</section>
			</NonTabular>
		);
	}

}
