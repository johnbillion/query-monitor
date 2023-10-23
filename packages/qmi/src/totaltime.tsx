import * as React from 'react';

import { Time } from './time';

interface TotalTimeProps {
	rows: {
		ltime: number;
	}[];
}

export class TotalTime extends React.Component<TotalTimeProps, Record<string, unknown>> {

	render() {
		const time = this.props.rows.reduce( ( a, b ) => a + b.ltime, 0 );

		return (
			<Time value={ time }/>
		);
	}

}
