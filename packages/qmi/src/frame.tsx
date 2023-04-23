import {
	FrameItem,
} from 'qmi/data-types';
import * as React from 'react';

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
