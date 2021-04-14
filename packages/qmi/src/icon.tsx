import * as React from 'react';

export interface iIconProps {
	name: string;
}

export class Icon extends React.Component<iIconProps, Record<string, unknown>> {

	render() {
		return (
			<span aria-hidden="true" className={ `dashicons dashicons-${ this.props.name }` }></span>
		);
	}

}
