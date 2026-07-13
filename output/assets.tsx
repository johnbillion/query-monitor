import { useContext, useMemo } from 'preact/hooks';
import { MainContext } from './contexts/main-context';
import { EmptyPanel } from './panels/empty-panel';
import { TabularPanel } from './panels/tabular-panel';
import { PanelFooter } from './panels/panel-footer';
import * as Utils from './utils';
import { Warning } from './components/warning';
import { Asset as AssetDataType, DataTypes } from './data-types';
import { PanelProps } from './types';
import {
	__,
	sprintf,
} from '@wordpress/i18n';
import { PanelMenuItem } from './panels/panel-registry';

type AssetsPanelId = 'assets_scripts' | 'assets_styles';

type AssetsData = DataTypes[AssetsPanelId];

export const assetsMenu = ( id: AssetsPanelId, label: string, data: AssetsData ): PanelMenuItem[] => {
	const warningCount = ( data.assets ?? [] ).filter( ( asset ) => asset.warning ).length;
	const totalCount = Object.values( data.types ?? {} ).reduce( ( sum, value ) => sum + value, 0 );

	return [ {
		id,
		panel: id,
		title: label,
		ok_count: ( totalCount - warningCount ),
		warning_count: warningCount || null,
	} ];
};

type myProps = PanelProps<DataTypes['assets_scripts'] | DataTypes['assets_styles']> & {
	labels: {
		none: string;
	};
};

type AssetRow = AssetDataType & {
	size: number;
};

/**
 * Builds a map of asset URL to its transferred body size, using the Performance
 * Resource Timing API. Cross-origin resources report 0 unless the server sends
 * a Timing-Allow-Origin header, so those are recorded as 0 (unknown).
 */
const getAssetSizes = (): Map<string, number> => {
	const sizes = new Map<string, number>();

	if ( typeof performance === 'undefined' || ! performance.getEntriesByType ) {
		return sizes;
	}

	for ( const entry of performance.getEntriesByType( 'resource' ) as PerformanceResourceTiming[] ) {
		if ( entry.encodedBodySize > 0 ) {
			sizes.set( entry.name, entry.encodedBodySize );
		}
	}

	return sizes;
};

type iPositionLabels = {
	[ key in AssetDataType['position'] ]: string;
}

interface iAssetSourceProps {
	asset: AssetDataType;
}

const AssetSource = ( { asset }: iAssetSourceProps ) => {
	const errorData = Utils.getErrorData( asset.source );
	const errorMessage = Utils.getErrorMessage( asset.source );

	if ( typeof errorData === 'object' && errorData !== null && 'src' in errorData ) {
		const href = ( errorData as Record<string, unknown> ).src as string;
		return (
			<>
				<Warning>
					{ errorMessage }
				</Warning>
				<a href={ href } target="_blank" rel="noreferrer" className="qm-external-link">
					{ href }
				</a>
			</>
		);
	}

	if ( errorMessage ) {
		return (
			<Warning>
				{ errorMessage }
			</Warning>
		);
	}

	const display = Utils.getAssetDisplay( asset.url );

	return (
		display ? (
			<a href={ asset.url.absolute } target="_blank" rel="noreferrer" className="qm-external-link">
				{ display }
			</a>
		) : (
			''
		)
	);
};

const Assets = ( { data, labels }: myProps ) => {
	// Asset sizes come from the browser's Resource Timing API, which is only relevant for the main page load.
	const { isMainPageLoad } = useContext( MainContext );

	const position_labels: iPositionLabels = {
		missing: __( 'Missing', 'query-monitor' ),
		broken: __( 'Missing Dependencies', 'query-monitor' ),
		modules: __( 'Module', 'query-monitor' ),
		header: __( 'Header', 'query-monitor' ),
		footer: __( 'Footer', 'query-monitor' ),
	};

	const assets: AssetRow[] = useMemo( () => {
		const sizes = isMainPageLoad ? getAssetSizes() : new Map<string, number>();

		return ( data.assets ?? [] ).map( ( asset ) => ( {
			...asset,
			size: sizes.get( asset.url.absolute ) ?? 0,
		} ) );
	}, [ data.assets, isMainPageLoad ] );

	if ( ! data.assets ) {
		return (
			<EmptyPanel>
				<p>
					{ labels.none }
				</p>
			</EmptyPanel>
		);
	}

	return (
		<TabularPanel
			title={ __( 'Assets', 'query-monitor' ) }
			cols={ {
				position: {
					heading: __( 'Position', 'query-monitor' ),
					className: 'qm-nowrap',
					render: ( row ) => position_labels[ row.position ],
				},
				handle: {
					heading: __( 'Handle', 'query-monitor' ),
					render: ( row ) => row.handle,
					className: 'qm-nowrap',
				},
				hostname: {
					heading: __( 'Host', 'query-monitor' ),
					render: ( row ) => ( row.url.host ),
					filters: {
						options: [
							[
								{
									key: 'local',
									label: data.url.host,
								},
								{
									key: 'other',
									label: __( 'Other', 'query-monitor' ),
								},
							],
						],
						callback: ( row, value ) => value === 'local' ? row.url.local : ! row.url.local,
					},
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
									className="qm-nowrap"
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
					render: ( row ) => (
						<>
							{ row.dependents.map( ( dep, i ) => [
								i > 0 && ', ',
								<span
									key={ dep }
									className="qm-nowrap"
								>
									{ dep }
								</span>,
							] ) }
						</>
					),
				},
				version: {
					heading: __( 'Version', 'query-monitor' ),
					render: ( row ) => row.ver,
				},
				size: isMainPageLoad ? {
					heading: __( 'Size', 'query-monitor' ),
					className: 'qm-num',
					render: ( row ) => {
						if ( row.size > 0 ) {
							return (
								<code>
									{ sprintf(
										/* translators: %s: Size in kilobytes */
										__( '%s kB', 'query-monitor' ),
										Utils.numberFormat( row.size / 1024, 1 )
									) }
								</code>
							);
						}

						return row.url.absolute ? __( 'Unknown', 'query-monitor' ) : '';
					},
					sorting: {
						field: 'size',
					},
				} : false,
			}}
			data={ assets }
			footer={ isMainPageLoad ? ( { cols, count, total, data: filteredData } ) => {
				const totalSize = filteredData.reduce( ( sum, row ) => sum + row.size, 0 );

				return (
					<PanelFooter
						cols={ cols - 1 }
						count={ count }
						total={ total }
					>
						<td className="qm-num">
							{ totalSize > 0 ? (
								<code>
									{ sprintf(
										/* translators: %s: Size in kilobytes */
										__( '%s kB', 'query-monitor' ),
										Utils.numberFormat( totalSize / 1024, 1 )
									) }
								</code>
							) : '—' }
						</td>
					</PanelFooter>
				);
			} : undefined }
			rowHasError={ ( row ) => {
				return row.warning;
			} }
		/>
	);
};

export default Assets;
