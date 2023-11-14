import {
	createContext,
} from 'react';

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
	setFilter: ( filterName, filterValue ) => {},
} );
