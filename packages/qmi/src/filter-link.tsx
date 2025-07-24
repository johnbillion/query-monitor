import * as React from 'react';
import { MainContext } from './main-context';
import { Icon } from './icon';

interface Props {
	targetPanel: string;
	filterName: string;
	filterValue: string;
	children: React.ReactNode;
}

export const FilterLink = ( { targetPanel, filterName, filterValue, children }: Props ) => {
	const { switchToPanel } = React.useContext( MainContext );

	return (
		<button
			className="qm-filter-trigger"
			onClick={ () => {
				switchToPanel( targetPanel, {
					[filterName]: filterValue
				} );
			} }
		>
			{ children }
			<Icon name="filter"/>
		</button>
	);
};
