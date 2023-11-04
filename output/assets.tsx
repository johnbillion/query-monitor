import {
	iPanelProps,
	EmptyPanel,
	TabularPanel,
	Utils,
	Warning,
} from 'qmi';
import {
	Asset as AssetDataType,
	DataTypes,
} from 'qmi/data-types';
import * as React from 'react';

import {
	__,
	sprintf,
} from '@wordpress/i18n';

type myProps = iPanelProps<DataTypes['Assets']> & {
	labels: {
		none: string;
	};
};

interface iPositionLabels {
	missing : string;
	broken : string;
	header : string;
	footer : string;
}

interface iAssetSourceProps {
	asset: AssetDataType;
}

const AssetSource = ( { asset }: iAssetSourceProps ) => {
	const errorData = Utils.getErrorData( asset.source );
	const errorMessage = Utils.getErrorMessage( asset.source );

	if ( errorData?.src ) {
		return (
			<Warning>
				{ errorMessage }
				<br/>
				{ errorData.src }
			</Warning>
		);
	}

	if ( errorMessage ) {
		return (
			<Warning>
				{ errorMessage }
			</Warning>
		);
	}

	return (
		<>
			{ asset.display }
		</>
	);
};

export default ( { data, id, labels }: myProps ) => {
	const position_labels: iPositionLabels = {
		missing: __( 'Missing', 'query-monitor' ),
		broken: __( 'Missing Dependencies', 'query-monitor' ),
		header: __( 'Header', 'query-monitor' ),
		footer: __( 'Footer', 'query-monitor' ),
	};

	if ( ! data.assets ) {
		return (
			<EmptyPanel>
				<p>
					{ labels.none }
				</p>
			</EmptyPanel>
		);
	}

	const rows = [
		...data.assets.broken,
		...data.assets.missing,
		...data.assets.header,
		...data.assets.footer,
	];

	return (
		<TabularPanel
			title={ __( 'Assets', 'query-monitor' ) }
			cols={ {
				position: {
					heading: __( 'Position', 'query-monitor' ),
					render: ( row ) => (
						<>
							{ row.warning && ( <Warning/> ) }
							{ row.position }
						</>
					),
				},
				handle: {
					heading: __( 'Handle', 'query-monitor' ),
					render: ( row ) => row.handle,
				},
				host: {
					heading: __( 'Host', 'query-monitor' ),
					render: ( row ) => ( row.port ? `${ row.host }:${ row.port }` : row.host ),
				},
				source: {
					heading: __( 'Source', 'query-monitor' ),
					render: ( row ) => <AssetSource asset={ row } />,
				},
				dependencies: {
					heading: __( 'Dependencies', 'query-monitor' ),
					render: ( row ) => (
						<>
							{ row.dependencies.map( ( dep, i ) => [
								i > 0 && ', ',
								<span
									key={ dep }
									style={ {
										whiteSpace: 'nowrap',
									} }
								>
									{ data.missing_dependencies[ dep ] ? (
										<Warning>
											&nbsp;
											{ sprintf(
												/* translators: %s: Name of missing script or style dependency */
												__( '%s (missing)', 'query-monitor' ),
												dep
											) }
										</Warning>
									) : dep }
								</span>,
							] ) }
						</>
					),
				},
				dependents: {
					heading: __( 'Dependents', 'query-monitor' ),
					render: ( row ) => row.dependents.join( ', ' ),
				},
				version: {
					heading: __( 'Version', 'query-monitor' ),
					render: ( row ) => row.ver,
				},
			}}
			data={ rows }
		/>
	);
};
