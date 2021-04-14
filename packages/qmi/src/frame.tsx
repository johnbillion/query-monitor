import * as React from 'react';

export interface FrameItem {
	display: string;
}

export interface FrameProps {
	frame: FrameItem;
}

export class Frame extends React.Component<FrameProps, Record<string, unknown>> {

	render() {
		return (
			<code>{ this.props.frame.display }</code>
		);
	}

}
