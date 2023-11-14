import {
	createContext,
} from 'react';
import {
	PanelContextType,
} from './panel-context';

interface FiltersType {
	[ panelName: string ]: PanelContextType['filters'];
}

export type MainContextType = {
	editor: string;
	setEditor: ( editor: string ) => void;
	theme: string;
	setTheme: ( theme: string ) => void;
	filters: FiltersType;
	setFilters: ( filters: FiltersType ) => void;
}

export const MainContext = createContext<MainContextType>( {
	editor: '',
	setEditor: ( editor ) => {},
	theme: 'auto',
	setTheme: ( theme ) => {},
	filters: {},
	setFilters: ( filters ) => {},
} );
