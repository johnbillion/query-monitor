import { Tabular, iPanelProps } from 'qmi';
import * as React from 'react';

import Assets from '../assets';

class Styles extends React.Component<iPanelProps, Record<string, unknown>> {

	render() {
		const { data } = this.props;

		return (
			<Tabular id={ this.props.id }>
				<Assets data={ data }/>
			</Tabular>
		);
	}

}

export default Styles;
