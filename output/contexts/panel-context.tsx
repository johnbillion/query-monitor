import {
	createContext,
} from 'preact';

export type PanelContextType = {
	id: string;
	filters: {
		[ filterName: string ]: string;
	};
	setFilter: ( filterName: string, filterValue: string ) => void;
}

export const PanelContext = createContext<PanelContextType>( {
	id: '',
	filters: {},
	setFilter: ( _filterName, _filterValue ) => {},
} );
