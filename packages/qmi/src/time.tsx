import { iQM_i18n } from 'qmi';
import * as React from 'react';

interface Props {
	value: number;
}

declare const QM_i18n: iQM_i18n;

export const Time = ( { value }: Props ) => (
	<>
		{ QM_i18n.number_format( value, 4 ) }
	</>
);
