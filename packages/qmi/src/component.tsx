import {
	Component as QM_Component,
} from 'qmi/data-types';
import * as React from 'react';
import { FilterLink } from './filter-link';

interface Props {
	component: QM_Component;
	targetPanel?: string;
}

export const Component = ( { component, targetPanel }: Props ) => {
	if ( targetPanel ) {
		return (
			<FilterLink
				targetPanel={ targetPanel }
				filterName="component"
				filterValue={ `${component.type}-${component.context}` }
			>
				{ component.name }
			</FilterLink>
		);
	}

	return (
		<>
			{ component.name }
		</>
	);
};
