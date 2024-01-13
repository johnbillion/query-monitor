import * as React from 'react';

import {
	DataTypes,
} from '../data-types';

interface Panels<TDataKey extends keyof DataTypes> {
	[ id: string ]: Panel<TDataKey>;
}

interface Panel<TDataKey extends keyof DataTypes> {
	render: ( data: DataTypes[ TDataKey ], enabled: boolean ) => React.ReactNode;
	data: TDataKey;
}

const panels: Panels<keyof DataTypes> = {};

export const registerPanel = <
	TDataKey extends keyof DataTypes,
>(
	id: string,
	args: Panel<TDataKey>,
) => {
	panels[ id ] = {
		...args,
	};
}

export const getPanel = ( id: string ) => {
	return panels[ id ] ?? null;
}
