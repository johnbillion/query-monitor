import {
	createContext,
} from 'preact';
import {
	PanelContextType,
} from './panel-context';

interface FiltersType {
	[ panelName: string ]: PanelContextType['filters'];
}

export interface SettingsType {
	extended_query_prompt_reason: 'conflict' | 'disabled' | 'failed' | null;
	file_path_map: Record<string, string>;
}

export type MainContextType = {
	editor: string;
	setEditor: ( editor: string ) => void;
	theme: string;
	setTheme: ( theme: string ) => void;
	filters: FiltersType;
	setFilters: ( filters: FiltersType ) => void;
	switchToPanel: ( panelId: string, panelFilters?: PanelContextType['filters'] ) => void;
	settings: SettingsType;
	queryDiffEnabled: boolean;
	setQueryDiffEnabled: ( enabled: boolean ) => void;
}

export const MainContext = createContext<MainContextType>( {
	editor: '',
	setEditor: ( _editor ) => {},
	theme: 'auto',
	setTheme: ( _theme ) => {},
	filters: {},
	setFilters: ( _filters ) => {},
	switchToPanel: ( _panelId, _panelFilters ) => {},
	settings: {
		extended_query_prompt_reason: null,
		file_path_map: {},
	},
	queryDiffEnabled: false,
	setQueryDiffEnabled: ( _enabled ) => {},
} );
