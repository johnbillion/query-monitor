import * as React from 'react';

export interface FrameItem {
	display: string;
	args: string[];
	calling_file: string;
	calling_line: number;
	file: string;
	function: string;
	id: string;
	line: number;
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
