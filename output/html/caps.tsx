import { MainContext } from '../contexts/main-context';
import { EmptyPanel } from '../panels/empty-panel';
import { TabularPanel } from '../panels/tabular-panel';
import { Warning } from '../components/warning';
import { derivePrimitiveFilters, getCallerCol, getComponentCol } from '../table';
import { DataTypes } from '../data-types';
import { PanelProps } from '../types';
import { useContext } from 'preact/hooks';
import {
	__,
	sprintf,
} from '@wordpress/i18n';

export const Caps = ( { enabled, data }: PanelProps<DataTypes['caps']> ) => {
	const { settings } = useContext( MainContext );

	if ( ! enabled ) {
		return (
			<EmptyPanel>
				<p>
					{ sprintf(
						/* translators: %s: Configuration file name. */
						__( 'For performance reasons, this panel is not enabled by default. To enable it, add the following code to your %s file:', 'query-monitor' ),
						'wp-config.php'
					) }
				</p>
				<p>
					<code>
						define( 'QM_ENABLE_CAPS_PANEL', true );
					</code>
				</p>
			</EmptyPanel>
		);
	}

	if ( ! data.caps.length ) {
		return (
			<EmptyPanel>
				<p>
					{ __( 'No capability checks were recorded.', 'query-monitor' ) }
				</p>
			</EmptyPanel>
		);
	}

	return <TabularPanel
		title={ __( 'Capability Checks', 'query-monitor' ) }
		cols={ {
			cap: {
				heading: __( 'Capability Check', 'query-monitor' ),
				render: ( cap ) => (
					<code>
						{ cap.name }
						{ cap.args.map( ( arg ) => (
							<>
								,&nbsp;{ arg }
							</>
						) ) }
					</code>
				),
			},
			user: {
				heading: __( 'User', 'query-monitor' ),
				render: ( cap ) => (
					<>
						{ cap.user }
						{ cap.user === 0 && (
							<>
								<br/>
								<br/>
								<Warning>
									{ __( 'Invalid user ID. WordPress silently converts this to 0 before the capability check runs.', 'query-monitor' ) }
								</Warning>
							</>
						) }
					</>
				),
				filters: {
					options: [ derivePrimitiveFilters( data.caps, ( cap ) => cap.user ) ],
					callback: ( row, value ) => row.user === Number( value ),
				},
			},
			result: {
				heading: __( 'Result', 'query-monitor' ),
				render: ( cap ) => ( cap.result ? <span className="qm-true">true&nbsp;&#x2713;</span> : 'false' ),
			},
			caller: getCallerCol( data.caps, settings ),
			component: getComponentCol( data.caps ),
		} }
		data={ data.caps }
	/>
};
