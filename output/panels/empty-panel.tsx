import { NonTabularPanel } from './non-tabular-panel';
import * as React from 'react';

interface Props {
	children: React.ReactNode;
}

export const EmptyPanel = ( { children }: Props ) => (
	<NonTabularPanel>
		<section>
			<div className="qm-notice">
				{ children }
			</div>
		</section>
	</NonTabularPanel>
);
