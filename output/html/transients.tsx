import { EmptyPanel } from '../panels/empty-panel';
import { TabularPanel } from '../panels/tabular-panel';
import { getCallerCol, getComponentCol } from '../table';
import { ApproximateSize } from '../components/approximate-size';
import { DataTypes } from '../data-types';
import { PanelProps } from '../types';
import * as React from 'react';

import {
	__,
	_x,
} from '@wordpress/i18n';

export const Transients = ( { data }: PanelProps<DataTypes['transients']> ) => {
	if ( ! data.trans?.length ) {
		return (
			<EmptyPanel>
				<p>
					{ __( 'No transients set.', 'query-monitor' ) }
				</p>
			</EmptyPanel>
		);
	}

	return <TabularPanel
		title={ __( 'Transients', 'query-monitor' ) }
		cols={ {
			name: {
				heading: __( 'Updated Transient', 'query-monitor' ),
				render: ( row ) => (
					<code>
						{ row.name }
					</code>
				),
			},
			type: data.has_type && {
				heading: _x( 'Type', 'transient type', 'query-monitor' ),
				render: ( row ) => ( row.type ),
			},
			expiration: {
				heading: __( 'Expiration', 'query-monitor' ),
				render: ( row ) => (
					<>
						{ row.expiration ? (
							<>
								{ row.expiration }
								<span className="qm-info">
									&nbsp;(~{ row.exp_diff })
								</span>
							</>
						) : (
							<em>
								{ __( 'none', 'query-monitor' ) }
							</em>
						) }
					</>
				),
			},
			size: {
				className: 'qm-num',
				heading: _x( 'Size', 'size of transient value', 'query-monitor' ),
				render: ( row ) => <ApproximateSize value={ row.size } />,
			},
			caller: getCallerCol( data.trans ),
			component: getComponentCol( data.trans ),
		} }
		data={ data.trans }
	/>
};
