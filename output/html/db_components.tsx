import {
	iPanelProps,
	Tabular,
	Time,
	TotalTime,
} from 'qmi';
import * as React from 'react';

import { __ } from '@wordpress/i18n';

interface iDBComponentTypeTimes {
	[key: string]: number;
}

interface iDBComponentsProps extends iPanelProps {
	data: {
		times: {
			component: string;
			ltime: number;
			types: iDBComponentTypeTimes;
		}[];
		types: iDBComponentTypeTimes;
	};
}

class DBComponents extends React.Component<iDBComponentsProps, Record<string, unknown>> {

	render() {
		const { data } = this.props;

		if ( ! data.times || ! data.times.length ) {
			return null;
		}

		return (
			<Tabular id={ this.props.id }>
				<thead>
					<tr>
						<th scope="col">
							{ __( 'Component', 'query-monitor' ) }
						</th>
						{ Object.keys( data.types ).map( key => (
							<th key={ key } className="qm-num" scope="col">
								{ key }
							</th>
						) ) }
						<th className="qm-num" scope="col">
							{ __( 'Time', 'query-monitor' ) }
						</th>
					</tr>
				</thead>
				<tbody>
					{ data.times.map( comp => (
						<tr key={ comp.component }>
							<td>{ comp.component }</td>
							{ Object.keys( data.types ).map( key =>
								( <td key={ key } className="qm-num">
									{ comp.types[key] || '' }
								</td> )
							) }
							<Time value={ comp.ltime }/>
						</tr>
					) ) }
				</tbody>
				<tfoot>
					<tr>
						<td></td>
						{ Object.keys( data.types ).map( key => (
							<td key={ key } className="qm-num">
								{ data.types[key] }
							</td>
						) ) }
						<TotalTime rows={ data.times }/>
					</tr>
				</tfoot>
			</Tabular>
		);
	}

}

export default DBComponents;
