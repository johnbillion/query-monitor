import * as React from 'react';

import { __ } from '@wordpress/i18n';

interface Props {
	children: React.ReactNode;
}

export const Toggler = ( { children }: Props ) => (
	<>
		<button
			aria-expanded="false"
			aria-label={ __( 'Toggle more information', 'query-monitor' ) }
			className="qm-toggle"
			data-off="-"
			data-on="+"
		>
			<span aria-hidden="true">+</span>
		</button>
		<div className="qm-toggled">
			{ children }
		</div>
	</>
);
