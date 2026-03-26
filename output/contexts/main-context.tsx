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
	abspath: string;
	contentpath: string;
}

export type DurationUnit = 's' | 'ms';

export type MainContextType = {
	editor: string;
	setEditor: ( editor: string ) => void;
	theme: string;
	setTheme: ( theme: string ) => void;
	fabulous: boolean;
	setFabulous: ( fabulous: boolean ) => void;
	filters: FiltersType;
	setFilters: ( filters: FiltersType ) => void;
	switchToPanel: ( panelId: string, panelFilters?: PanelContextType['filters'], rowIndex?: number ) => void;
	jumpToRow: { panel: string; row: number } | null;
	timelineHiddenCategories: string[];
	setTimelineHiddenCategories: ( categories: string[] ) => void;
	settings: SettingsType;
	durationUnit: DurationUnit;
	setDurationUnit: ( unit: DurationUnit ) => void;
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
	switchToPanel: ( _panelId, _panelFilters, _rowIndex ) => {},
	jumpToRow: null,
	timelineHiddenCategories: [],
	setTimelineHiddenCategories: ( _categories ) => {},
	durationUnit: 's',
	setDurationUnit: ( _unit ) => {},
	settings: {
		extended_query_prompt_reason: null,
		file_path_map: {},
		file_link_format: false,
		abspath: '',
		contentpath: '',
	},
} );
