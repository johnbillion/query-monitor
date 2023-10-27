import * as React from 'react';

interface iIconProps {
	name: string;
}

export const Icon = ( { name }: iIconProps ) => {
	if ( name === 'blank' ) {
		return (
			<span className="qm-icon qm-icon-blank"></span>
		);
	}

	return (
		<svg
			aria-hidden="true"
			className={ `qm-icon qm-icon-${ name }` }
			height="20"
			viewBox="0 0 20 20"
			width="20"
		>
			<use href={ `#qm-icon-${ name }` } />
		</svg>
	);
};
