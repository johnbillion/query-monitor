import {
	Component as QM_Component,
} from 'qmi/data-types';
import * as React from 'react';

interface iComponentProps {
	component: QM_Component;
}

export const Component = ( { component }: iComponentProps ) => (
	<td className="qm-nowrap">{ component.name }</td>
);
