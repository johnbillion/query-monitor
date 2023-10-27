import {
	iPanelProps,
	NonTabular,
} from 'qmi';
import {
	DataTypes,
} from 'qmi/data-types';
import * as React from 'react';

import DB from '../db';
import PHP from '../php';
import Server from '../server';
import WordPress from '../wordpress';

export default ( { data, id }: iPanelProps<DataTypes['Environment']> ) => (
	<NonTabular id={ id }>
		<PHP php={ data.php }/>
		<DB db={ data.db }/>
		<WordPress wordpress={ data.wp }/>
		<Server server={ data.server }/>
	</NonTabular>
);
