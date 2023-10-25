import * as React from 'react';

import { Icon } from './icon';

interface iWarningProps {
	children?: React.ReactNode;
}

export class Warning extends React.Component<iWarningProps, Record<string, unknown>> {

	render() {
		return (
			<span className="qm-warn">
				<Icon name="warning"/>
				{ this.props.children ?? null }
			</span>
		);
	}

}
