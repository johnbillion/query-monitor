import {
	Utils,
} from 'qmi';
import * as React from 'react';

import {
	sprintf,
} from '@wordpress/i18n';

interface Props {
	value: number;
}

export const ApproximateSize = ( { value }: Props ) => (
	<code>
		{ ( value < 1024 ) ? (
			sprintf(
				'~%s B',
				Utils.numberFormat( value )
			)
		) : (
			sprintf(
				'~%s kB',
				Utils.numberFormat( value / 1024 )
			)
		) }
	</code>
);
