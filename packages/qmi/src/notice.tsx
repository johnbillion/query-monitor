import { NonTabular } from 'qmi';
import * as React from 'react';

interface iNoticeProps {
	id: string;
	children: React.ReactNode;
}

export const Notice = ( { children, id }: iNoticeProps ) => (
			<NonTabular id={ id }>
				<section>
					<div className="qm-notice">
						{ children }
					</div>
				</section>
			</NonTabular>
		);
