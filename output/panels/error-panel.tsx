import { NonTabularPanel } from './non-tabular-panel';
import * as React from 'react';

interface Props {
	children: React.ReactNode;
}

export const ErrorPanel = ( { children }: Props ) => (
	<NonTabularPanel>
		<section>
			<div className="qm-error">
				{ children }
			</div>
		</section>
	</NonTabularPanel>
);
