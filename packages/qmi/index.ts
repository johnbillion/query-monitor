export { ApproximateSize } from './src/approximate-size';
export { Caller } from './src/caller';
export { Context } from './src/context';
export { PanelTable } from './src/panel-table';
export { Frame } from './src/frame';
export { FileName } from './src/file-name';
export { Icon } from './src/icon';
export { NonTabular } from './src/non-tabular';
export { Notice } from './src/notice';
export { PanelFooter } from './src/panel-footer';
export { Component } from './src/component';
export { Tabular } from './src/tabular';
export { TabularPanel } from './src/tabular-panel';
export { TimeCell } from './src/time';
export { TotalTime } from './src/total-time';
export { Toggler } from './src/toggler';
export { Warning } from './src/warning';
export * as Utils from './src/utils';
export * as Data from './data-types';

export interface iPanelProps<T> {
	data: T;
	id: string;
	enabled: boolean;
}

export interface iQM_i18n {
	number_format: (
		number: number,
		decimals?: number,
	) => string;
}
