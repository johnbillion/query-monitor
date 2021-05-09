import {
	iPanelProps,
	NonTabular,
} from 'qmi';
import * as React from 'react';

import DB from '../db';
import PHP from '../php';
import Server from '../server';
import WordPress from '../wordpress';

class Environment extends React.Component<iPanelProps, Record<string, unknown>> {

	render() {
		const { data } = this.props;

		return (
			<NonTabular id={ this.props.id }>
				<PHP php={ data.php }/>
				{ data.db && (
					<>
						{ Object.keys( data.db ).map( key =>
							<DB key={ key } db={ data.db[key] } name={ key }/>
						) }
					</>
				) }
				<WordPress wordpress={ data.wp }/>
				<Server server={ data.server }/>
			</NonTabular>
		);
	}

}

export default Environment;
