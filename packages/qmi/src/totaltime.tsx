import * as React from 'react';

import { TimeCell } from './time';

interface TotalTimeProps {
	rows: {
		ltime: number;
	}[];
}

export class TotalTime extends React.Component<TotalTimeProps, Record<string, unknown>> {

	render() {
		const time = this.props.rows.reduce( ( a, b ) => a + b.ltime, 0 );

		return (
			<TimeCell value={ time }/>
		);
	}

}
