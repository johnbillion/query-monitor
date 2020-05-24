import React, { Component } from 'react';
import { NonTabular } from 'qmi';
import PHP from '../php';
import DB from '../db';
import WordPress from '../wordpress';
import Server from '../server';
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
				<WordPress wordpress={data.wp}/>
				<Server server={data.server}/>
			</NonTabular>
		);
	}

}

export default Environment;
