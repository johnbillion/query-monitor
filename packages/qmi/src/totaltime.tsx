import * as React from "react";
import { Time } from "./time";

export interface TotalTimeProps {
	rows: {
		ltime: number;
	}[];
}

export class TotalTime extends React.Component<TotalTimeProps, {}> {

	render() {
		const time = this.props.rows.reduce((a,b)=>a+b.ltime,0)
		return (
			<Time value={time}/>
		);
	}

}
