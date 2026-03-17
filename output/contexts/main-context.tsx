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
	file_link_format: string | false;
}

export type MainContextType = {
	editor: string;
	setEditor: ( editor: string ) => void;
	theme: string;
	setTheme: ( theme: string ) => void;
	fabulous: boolean;
	setFabulous: ( fabulous: boolean ) => void;
	filters: FiltersType;
	setFilters: ( filters: FiltersType ) => void;
	switchToPanel: ( panelId: string, panelFilters?: PanelContextType['filters'] ) => void;
	settings: SettingsType;
}

export const MainContext = createContext<MainContextType>( {
	editor: '',
	setEditor: ( _editor ) => {},
	theme: 'auto',
	setTheme: ( _theme ) => {},
	fabulous: false,
	setFabulous: ( _fabulous ) => {},
	filters: {},
	setFilters: ( _filters ) => {},
	switchToPanel: ( _panelId, _panelFilters ) => {},
	settings: {
		extended_query_prompt_reason: null,
		file_path_map: {},
		file_link_format: false,
	},
} );
