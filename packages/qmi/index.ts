export { ApproximateSize } from './src/approximate-size';
export { Caller } from './src/caller';
export {
	MainContext,
} from './src/main-context';

export type { MainContextType } from './src/main-context';
export {
	PanelContext,
} from './src/panel-context';

export type { PanelContextType } from './src/panel-context';

export { Table, getComponentCol, getTimeCol, getCallerCol } from './src/table';
export { Frame } from './src/frame';
export { FileName } from './src/file-name';
export { Icon } from './src/icon';
export { NonTabularPanel } from './src/non-tabular-panel';
export { EmptyPanel } from './src/empty-panel';
export { Panel } from './src/panel';
export { PanelFooter } from './src/panel-footer';
export { Component } from './src/component';
export { TabularPanel } from './src/tabular-panel';
export { Time } from './src/time';
export { TotalTime } from './src/total-time';
export { Toggler } from './src/toggler';
export { Warning } from './src/warning';
export * as Utils from './src/utils';
export * as Data from './data-types';

export type PanelProps<T> = {
	data: T;
	enabled: boolean;
}

export type iQM_i18n = {
	number_format: (
		number: number,
		decimals?: number,
	) => string;
}
