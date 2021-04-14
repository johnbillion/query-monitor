export { Caller } from './src/caller';
export {
	Frame,
	FrameItem,
} from './src/frame';
export { NonTabular } from './src/non-tabular';
export { NotEnabled } from './src/not-enabled';
export { Notice } from './src/notice';
export { PanelFooter } from './src/panel-footer';
export { QMComponent } from './src/component';
export { Tabular } from './src/tabular';
export { Time } from './src/time';
export { TotalTime } from './src/totaltime';
export { Toggler } from './src/toggler';
export { Warning } from './src/warning';

export interface iPanelProps {
	data: any;
	id: string;
	enabled: boolean;
}

export interface iQM_i18n {
	number_format: (
		number: number,
		decimals?: number,
	) => string;
}
