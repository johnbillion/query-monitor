import { NonTabular } from 'qmi';
import * as React from 'react';

interface iErrorProps {
	id: string;
	children: React.ReactNode;
}

export const ErrorMessage = ( { id, children }: iErrorProps ) => (
	<NonTabular id={ id }>
		<section>
			<div className="qm-error">
				{ children }
			</div>
		</section>
	</NonTabular>
);
