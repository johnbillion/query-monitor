import * as classNames from 'classnames';
import {
	iPanelProps,
	Notice,
	PanelFooter,
	Tabular,
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
	_x,
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
			<Notice id={ id }>
				<p>
					{ labels.none }
				</p>
			</Notice>
		);
	}

	return (
		<Tabular id={ id }>
			<thead>
				<tr>
					<th scope="col">
						{ __( 'Position', 'query-monitor' ) }
					</th>
					<th scope="col">
						{ __( 'Handle', 'query-monitor' ) }
					</th>
					<th scope="col">
						{ __( 'Host', 'query-monitor' ) }
					</th>
					<th scope="col">
						{ __( 'Source', 'query-monitor' ) }
					</th>
					<th scope="col">
						{ __( 'Dependencies', 'query-monitor' ) }
					</th>
					<th scope="col">
						{ __( 'Dependents', 'query-monitor' ) }
					</th>
					<th scope="col">
						{ __( 'Version', 'query-monitor' ) }
					</th>
				</tr>
			</thead>
			<tbody>
				{ Object.keys( position_labels ).map( ( key: keyof typeof position_labels ) => (
					<React.Fragment key={ key }>
						{ data.assets[ key ] && Object.keys( data.assets[ key ] ).map( handle => {
							const asset = data.assets[ key ][ handle ];
							const classes = {
								'qm-warn': asset.warning,
							};

							return (
								<tr
									key={ handle }
									className={ classNames( classes ) }
								>
									<td>
										{ asset.warning && ( <Warning/> ) }
										{ position_labels[ key ] }
									</td>
									<td>
										{ handle }
									</td>
									<td>
										{ asset.port ? `${ asset.host }:${ asset.port }` : asset.host }
									</td>
									<td>
										<AssetSource
											asset={ asset }
										/>
									</td>
									<td>
										{ asset.dependencies.map( ( dep, i ) => [
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
									</td>
									<td>
										{ asset.dependents.join( ', ' ) }
									</td>
									<td>
										{ asset.ver }
									</td>
								</tr>
							);
						} ) }
					</React.Fragment>
				) ) }
			</tbody>
			<PanelFooter
				cols={ 7 }
				count={ data.counts.total }
				label={ _x( 'Total:', 'Total assets', 'query-monitor' ) }
			/>
		</Tabular>
	);
};
