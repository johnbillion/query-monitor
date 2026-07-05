import { __ } from '@wordpress/i18n';
import { NonTabularPanel } from '../panels/non-tabular-panel';
import { DataTypes } from '../data-types';
import { PanelProps } from '../types';
import { PanelMenuItem } from '../panels/panel-registry';
import DB from '../db';
import PHP from '../php';
import Server from '../server';
import WordPress from '../wordpress';

export const environmentMenu = (): PanelMenuItem[] => [ {
	id: 'environment',
	panel: 'environment',
	title: __( 'Environment', 'query-monitor' ),
} ];

export const Environment = ( { data }: PanelProps<DataTypes['environment']> ) => (
	<NonTabularPanel title={ __( 'Environment', 'query-monitor' ) }>
		<PHP php={ data.php }/>
		<DB db={ data.db }/>
		<WordPress wordpress={ data.wp }/>
		<Server server={ data.server }/>
	</NonTabularPanel>
);
