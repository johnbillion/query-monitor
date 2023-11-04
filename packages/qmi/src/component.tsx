import {
	Component as QM_Component,
} from 'qmi/data-types';
import * as React from 'react';

interface Props {
	component: QM_Component;
}

export const Component = ( { component }: Props ) => (
	<>
		{ component.name }
	</>
);
