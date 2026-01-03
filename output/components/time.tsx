import * as Utils from '../utils';
import * as React from 'react';

interface Props {
	value: number;
}

export const Time = ( { value }: Props ) => (
	<>
		{ Utils.numberFormat( value, 4 ) }
	</>
);
