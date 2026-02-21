import { type ComponentChildren } from 'preact';

import { Icon } from './icon';

interface Props {
	children?: ComponentChildren;
}

export const Warning = ( { children }: Props ) => (
	<span className="qm-warn">
		<Icon name="warning"/>
		{ children ?? null }
	</span>
);
