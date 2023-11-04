import { NonTabularPanel } from './non-tabular-panel';
import * as React from 'react';

interface iErrorProps {
	children: React.ReactNode;
}

export const ErrorPanel = ( { children }: iErrorProps ) => (
	<NonTabularPanel>
		<section>
			<div className="qm-error">
				{ children }
			</div>
		</section>
	</NonTabularPanel>
);
