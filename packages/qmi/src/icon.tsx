import * as React from 'react';

interface iIconProps {
	name: string;
}

export class Icon extends React.Component<iIconProps, Record<string, unknown>> {
	render() {
		if ( this.props.name === 'blank' ) {
			return (
				<span className="qm-icon qm-icon-blank"></span>
			);
		}

		return (
			<svg
				aria-hidden="true"
				className={ `qm-icon qm-icon-${ this.props.name }` }
				height="20"
				viewBox="0 0 20 20"
				width="20"
			>
				<use href={ `#qm-icon-${ this.props.name }` } />
			</svg>
		);
	}

}
