import { NonTabular } from 'qmi';
import * as React from 'react';

interface iErrorProps {
	id: string;
	children: React.ReactNode;
}

export class ErrorMessage extends React.Component<iErrorProps, Record<string, unknown>> {

	render() {
		return (
			<NonTabular id={ this.props.id }>
				<section>
					<div className="qm-error">
						{ this.props.children }
					</div>
				</section>
			</NonTabular>
		);
	}

}
