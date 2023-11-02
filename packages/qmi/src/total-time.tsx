import * as React from 'react';

import { Time } from './time';

interface TotalTimeProps {
	rows: {
		ltime: number;
	}[];
}

export const TotalTime = ( { rows }: TotalTimeProps ) => (
	<Time value={ rows.reduce( ( a, b ) => a + b.ltime, 0 ) }/>
);
