import React, { Component } from 'react';
import Assets from '../assets.js';
import { Tabular } from '../utils';
import { __, _x, _n, sprintf } from '@wordpress/i18n';

class Scripts extends Component {

	render() {
		const { data } = this.props;

		return (
			<Tabular id={this.props.id}>
				<Assets data={data}/>
			</Tabular>
		);
	}

}

export default Scripts;
