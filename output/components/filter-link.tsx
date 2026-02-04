import { type ComponentChildren } from 'preact';
import { useContext } from 'preact/hooks';
import { MainContext } from '../contexts/main-context';
import { Icon } from './icon';

interface Props {
	targetPanel: string;
	filterName: string;
	filterValue: string;
	children: ComponentChildren;
}

export const FilterLink = ( { targetPanel, filterName, filterValue, children }: Props ) => {
	const { switchToPanel } = useContext( MainContext );

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
