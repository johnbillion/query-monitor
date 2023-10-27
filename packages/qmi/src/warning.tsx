import * as React from 'react';

import { Icon } from './icon';

interface iWarningProps {
	children?: React.ReactNode;
}

export const Warning = ( { children }: iWarningProps ) => (
			<span className="qm-warn">
				<Icon name="warning"/>
				{ children ?? null }
			</span>
		);
