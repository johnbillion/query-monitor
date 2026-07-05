import { type ComponentChildren } from 'preact';

import { Icon } from './icon';

interface Props {
	children?: ComponentChildren;
}

export const Notice = ( { children }: Props ) => (
	<span className="qm-notice qm-warning-wrapper">
		<Icon name="info"/>
		<div>
			{ children ?? null }
		</div>
	</span>
);
