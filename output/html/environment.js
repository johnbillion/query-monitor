import React, { Component } from 'react';
import NonTabular from '../non-tabular.js';
import DB from '../db.js';
import { __, _x, _n, sprintf } from '@wordpress/i18n';

class Environment extends Component {

	render() {
		const { data } = this.props;

		return (
			<NonTabular id={this.props.id}>
				<PHP php={data.php}/>
				{ data.db && (
					<>
						{Object.keys(data.db).map(key =>
							<DB key={key} name={key} db={data.db[key]}/>
						)}
					</>
				)}
			</NonTabular>
		);
	}

}

export default Environment;
