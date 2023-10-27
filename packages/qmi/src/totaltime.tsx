import * as React from 'react';

import { TimeCell } from './time';

interface TotalTimeProps {
	rows: {
		ltime: number;
	}[];
}

export const TotalTime = ( { rows }: TotalTimeProps ) => (
			<TimeCell value={ rows.reduce( ( a, b ) => a + b.ltime, 0 ) }/>
		);
