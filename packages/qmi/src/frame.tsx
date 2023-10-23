import {
	FrameItem,
} from 'qmi/data-types';
import * as React from 'react';

interface FrameProps {
	frame: FrameItem;
	expanded?: boolean;
	isFileName?: boolean;
}

export class Frame extends React.Component<FrameProps, Record<string, unknown>> {

	render() {
		return (
			<>
				<code>{ this.props.frame.display }</code>
				{ this.props.expanded && (
					<>
						<br/>
						<span className="qm-info qm-supplemental">
							@TODO
						</span>
					</>
				) }
			</>
		);
	}

}
