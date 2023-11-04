import { NonTabularPanel } from 'qmi';
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
