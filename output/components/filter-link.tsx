import { type ComponentChildren } from 'preact';
import { useContext } from 'preact/hooks';
import { MainContext } from '../contexts/main-context';
import { Icon } from './icon';

import { sprintf, __ } from '@wordpress/i18n';

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
			/* translators: %s: Value to filter by */
			aria-label={ sprintf( __( 'Filter by %s', 'query-monitor' ), filterValue ) }
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
