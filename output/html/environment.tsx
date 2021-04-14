import * as React from 'react';
import { NonTabular, iPanelProps } from 'qmi';
import PHP from '../php';
import DB from '../db';
import WordPress from '../wordpress';
import Server from '../server';
import { __, _x, _n, sprintf } from '@wordpress/i18n';

class Environment extends React.Component<iPanelProps, Record<string, unknown>> {

	render() {
		const { data } = this.props;

		return (
			<NonTabular id={this.props.id}>
				<PHP php={data.php}/>
				{ data.db && (
					<>
						{Object.keys(data.db).map(key =>
							<DB key={ key } name={ key } db={data.db[key]}/>
						)}
					</>
				)}
				<WordPress wordpress={data.wp}/>
				<Server server={data.server}/>
			</NonTabular>
		);
	}

}

export default Environment;
