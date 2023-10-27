import {
	Component,
} from 'qmi/data-types';
import * as React from 'react';

interface iComponentProps {
	component: Component;
}

export const QMComponent = ( { component }: iComponentProps ) => (
			<td className="qm-nowrap">{ component.name }</td>
		);
