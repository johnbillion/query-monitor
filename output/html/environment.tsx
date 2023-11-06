import {
	PanelProps,
	NonTabularPanel,
} from 'qmi';
import {
	DataTypes,
} from 'qmi/data-types';
import * as React from 'react';

import DB from '../db';
import PHP from '../php';
import Server from '../server';
import WordPress from '../wordpress';

export const Environment = ( { data }: PanelProps<DataTypes['Environment']> ) => (
	<NonTabularPanel>
		<PHP php={ data.php }/>
		<DB db={ data.db }/>
		<WordPress wordpress={ data.wp }/>
		<Server server={ data.server }/>
	</NonTabularPanel>
);
