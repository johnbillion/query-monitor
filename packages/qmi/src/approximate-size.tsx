import {
	iQM_i18n,
} from 'qmi';
import * as React from 'react';

import {
	sprintf,
} from '@wordpress/i18n';

interface Props {
	value: number;
}

declare const QM_i18n: iQM_i18n;

export const ApproximateSize = ( { value }: Props ) => (
	<>
		{ ( value < 1024 ) ? (
			sprintf(
				'~%s B',
				QM_i18n.number_format( value )
			)
		) : (
			sprintf(
				'~%s kB',
				QM_i18n.number_format( value / 1024 )
			)
		) }
	</>
);
