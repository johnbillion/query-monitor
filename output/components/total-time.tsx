import * as React from 'react';

import { Time } from './time';

interface Props {
	rows: {
		ltime: number;
	}[];
}

export const TotalTime = ( { rows }: Props ) => (
	<Time value={ rows.reduce( ( a, b ) => a + b.ltime, 0 ) }/>
);
