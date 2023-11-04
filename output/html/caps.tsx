import {
	PanelProps,
	EmptyPanel,
	TabularPanel,
	getCallerCol,
	getComponentCol,
} from 'qmi';
import {
	DataTypes,
} from 'qmi/data-types';
import * as React from 'react';

import {
	__,
	sprintf,
} from '@wordpress/i18n';

export default ( { enabled, data }: PanelProps<DataTypes['Caps']> ) => {
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
				render: ( cap ) => ( cap.user ),
			},
			result: {
				heading: __( 'Result', 'query-monitor' ),
				render: ( cap ) => ( cap.result ? <span className="qm-true">true&nbsp;&#x2713;</span> : 'false' ),
			},
			...getCallerCol( data.caps ),
			...getComponentCol( data.caps, data.component_times ),
		} }
		data={ data.caps }
	/>
};
