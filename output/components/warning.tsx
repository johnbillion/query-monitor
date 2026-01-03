import * as React from 'react';

import { Icon } from './icon';

interface Props {
	children?: React.ReactNode;
}

export const Warning = ( { children }: Props ) => (
	<span className="qm-warn">
		<Icon name="warning"/>
		{ children ?? null }
	</span>
);
