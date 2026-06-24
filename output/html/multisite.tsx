import { MainContext } from '../contexts/main-context';
import { EmptyPanel } from '../panels/empty-panel';
import { TabularPanel } from '../panels/tabular-panel';
import { getCallerCol, getComponentCol } from '../table';
import { DataTypes } from '../data-types';
import { PanelProps } from '../types';
import { useContext } from 'preact/hooks';
import {
	__,
	sprintf,
} from '@wordpress/i18n';

export const Multisite = ( { data }: PanelProps<DataTypes['multisite']> ) => {
	const { settings } = useContext( MainContext );

	if ( ! data.switches.length ) {
		return (
			<EmptyPanel>
				<p>
					{ __( 'No data logged.', 'query-monitor' ) }
				</p>
			</EmptyPanel>
		);
	}

	return <TabularPanel
		title={ __( 'Multisite', 'query-monitor' ) }
		cols={ {
			i: {
				className: 'qm-i',
				heading: '#',
				render: ( row, i ) => ( i + 1 ),
			},
			function: {
				heading: __( 'Function', 'query-monitor' ),
				render: ( row ) => (
					<code>
						{ row.to ? (
							sprintf(
								'switch_to_blog(%d)',
								row.new
							)
						) : (
							'restore_current_blog()'
						) }
					</code>
				),
			},
			site: {
				heading: __( 'Site Switch', 'query-monitor' ), // @todo improve this label
				render: ( row ) => (
					<code>
						{ row.prev } &rarr; { row.new }
					</code>
				),
			},
			caller: getCallerCol( data.switches, settings ),
			component: getComponentCol( data.switches ),
		}}
		data={ data.switches }
	/>
};
