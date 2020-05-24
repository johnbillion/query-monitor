import * as React from "react";

export interface FrameItem {
	display: string;
}

export interface FrameProps {
	frame: FrameItem;
}

export class Frame extends React.Component<FrameProps, {}> {

	render() {
		return (
			<code>{this.props.frame.display}</code>
		);
	}

}
