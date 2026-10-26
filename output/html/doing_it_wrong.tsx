import { MainContext } from '../contexts/main-context';
import { EmptyPanel } from '../panels/empty-panel';
import { TabularPanel } from '../panels/tabular-panel';
import { getCallerCol, getComponentCol } from '../table';
import { DataTypes } from '../data-types';
import { PanelProps } from '../types';
import { useContext } from 'preact/hooks';
import {
	__,
	_x,
} from '@wordpress/i18n';
import { PanelMenuItem } from '../panels/panel-registry';

export const doingItWrongMenu = ( data: DataTypes['doing_it_wrong'] ): PanelMenuItem[] => (
	data.actions?.length ? [ {
		id: 'doing_it_wrong',
		panel: 'doing_it_wrong',
		title: _x( 'Doing it Wrong', 'Doing it Wrong', 'query-monitor' ),
		notice_count: data.actions.length,
	} ] : []
);

export const DoingItWrong = ( { data }: PanelProps<DataTypes['doing_it_wrong']> ) => {
	const { settings } = useContext( MainContext );

	if ( ! data.actions?.length ) {
		return (
			<EmptyPanel>
				<p>
					{ __( 'No occurrences.', 'query-monitor' ) }
				</p>
			</EmptyPanel>
		);
	}

	return <TabularPanel
		title={ __( 'Doing it Wrong', 'query-monitor' ) }
		cols={ {
			message: {
				heading: __( 'Message', 'query-monitor' ),
				render: ( row ) => row.message,
			},
			caller: getCallerCol( data.actions, settings ),
			component: getComponentCol( data.actions ),
		} }
		data={ data.actions }
	/>
};
