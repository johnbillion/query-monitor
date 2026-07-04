import { MainContext } from '../contexts/main-context';
import { EmptyPanel } from '../panels/empty-panel';
import { TabularPanel } from '../panels/tabular-panel';
import * as Utils from '../utils';
import { Toggler } from '../components/toggler';
import { Warning } from '../components/warning';
import { ApproximateSize } from '../components/approximate-size';
import { derivePrimitiveFilters, getCallerCol, getComponentCol } from '../table';
import type { FilterOption } from '../table';
import { Duration } from '../components/duration';
import { TotalTime } from '../components/total-time';
import { PanelFooter } from '../panels/panel-footer';
import { DataTypes } from '../data-types';
import { PanelProps } from '../types';
import { useContext } from 'preact/hooks';
import {
	__,
	_x,
	sprintf,
} from '@wordpress/i18n';
import { PanelMenuItem } from '../panels/panel-registry';

export const httpMenu = ( data: DataTypes['http'] ): PanelMenuItem[] => {
	const count = data.http?.length ?? 0;
	const errorCount = ( data.errors?.alert?.length ?? 0 ) + ( data.errors?.warning?.length ?? 0 );

	return [ {
		id: 'http',
		panel: 'http',
		title: __( 'HTTP API Calls', 'query-monitor' ),
		count: count || null,
		warning_count: errorCount || null,
		...( errorCount ? { classname: 'qm-warning' } : {} ),
	} ];
};

export const httpMenuClass = ( data: DataTypes['http'] ): string[] => (
	Object.keys( data.errors ?? {} ).length ? [ 'qm-warning' ] : []
);

const hasHttpsWarning = ( row: DataTypes['http']['http'][0] ): boolean => {
	return ( ! row.url.startsWith( 'https://' ) ) && ( 'localhost' !== row.host );
};

const hasSslVerifyWarning = ( row: DataTypes['http']['http'][0] ): boolean => {
	return row.url.startsWith( 'https://' ) && row.args.sslverify === false;
};

const hasRedirectWarning = ( row: DataTypes['http']['http'][0] ): boolean => {
	return row.redirected_to !== null;
};

const hasInterceptedWarning = ( row: DataTypes['http']['http'][0] ): boolean => {
	return row.intercepted === true;
};

export const HTTP = ( { data }: PanelProps<DataTypes['http']> ) => {
	const { settings } = useContext( MainContext );

	if ( ! data.http ) {
		return (
			<EmptyPanel>
				<p>
					{ __( 'No HTTP API calls.', 'query-monitor' ) }
				</p>
			</EmptyPanel>
		);
	}

	return <TabularPanel
		title={ __( 'HTTP API', 'query-monitor' ) }
		cols={ {
			method: {
				heading: __( 'Method', 'query-monitor' ),
				render: ( row ) => row.args.method,
			},
			url: {
				heading: __( 'URL', 'query-monitor' ),
				render: ( row ) => (
					<>
						{ hasHttpsWarning( row ) && (
							<div>
								<Warning>
									{ __( 'Request to a non-HTTPS URL', 'query-monitor' ) }
								</Warning>
							</div>
						) }
						{ hasSslVerifyWarning( row ) && (
							<div>
								<Warning>
									{ sprintf(
										/* translators: An HTTP API request has disabled certificate verification. 1: Relevant argument name */
										__( 'Certificate verification disabled (%s)', 'query-monitor' ),
										'sslverify=false'
									) }
								</Warning>
							</div>
						) }
						{ hasInterceptedWarning( row ) && (
							<div>
								<Warning>
									{ sprintf(
										/* translators: %s: WordPress filter name */
										__( 'Request was short-circuited by the %s filter and was not sent', 'query-monitor' ),
										'pre_http_request'
									) }
								</Warning>
							</div>
						) }
						{ row.args.method === 'GET' ? (
							<a href={ row.url } target="_blank" rel="noreferrer">
								{ Utils.formatURL( row.url ) }
							</a>
						) : (
							Utils.formatURL( row.url )
						) }
						{ hasRedirectWarning( row ) && (
							<div>
								<Warning>
									{ sprintf(
										/* translators: %s: Redirect target URL */
										__( 'Redirected to: %s', 'query-monitor' ),
										row.redirected_to
									) }
								</Warning>
							</div>
						) }
					</>
				),
				filters: {
					options: [ derivePrimitiveFilters( data.http, ( row ) => row.host ) ],
					callback: ( row, value ) => row.host === value,
				},
			},
			status: {
				heading: __( 'Status', 'query-monitor' ),
				className: 'qm-has-toggle',
				render: ( row ) => {
					if ( Utils.isWPError( row.result ) ) {
						return (
							<Warning>
								{ sprintf(
									/* translators: %s: Error message text */
									__( 'Error: %s', 'query-monitor' ),
									Utils.getErrorMessage( row.result )
								) }
							</Warning>
						);
					}

					if ( row.intercepted ) {
						return '';
					}

					const statusText = row.args.blocking === false
						? __( 'Non-blocking', 'query-monitor' )
						: `${row.result.code} ${row.result.message}`;

					const info = row.info && typeof row.info === 'object' ? row.info : null;
					const hasInfo = info && (
						'namelookup_time' in info ||
						'connect_time' in info ||
						'starttransfer_time' in info ||
						'content_type' in info ||
						'primary_ip' in info
					);

					if ( ! hasInfo ) {
						return statusText;
					}

					const timeFields: FilterOption[] = [
						{ key: 'namelookup_time', label: __( 'DNS Resolution Time', 'query-monitor' ) },
						{ key: 'connect_time', label: __( 'Connection Time', 'query-monitor' ) },
						{ key: 'starttransfer_time', label: __( 'Transfer Start Time (TTFB)', 'query-monitor' ) },
					];

					const otherFields: FilterOption[] = [
						{ key: 'content_type', label: __( 'Response Content Type', 'query-monitor' ) },
					];

					return (
						<Toggler summary={ statusText }>
							<ul>
								{ 'primary_ip' in info && (
									<li key="primary_ip" className="qm-info qm-supplemental">
										{ __( 'IP Address', 'query-monitor' ) }: { info.primary_ip as string }
									</li>
								) }
								{ timeFields.map( ( { key, label } ) => {
									if ( ! ( key in info ) ) {
										return null;
									}
									return (
										<li key={ key } className="qm-info qm-supplemental">
											{ label }: <Duration value={ info[key] as number } />
										</li>
									);
								} ) }
								{ otherFields.map( ( { key, label } ) => {
									if ( ! ( key in info ) ) {
										return null;
									}
									return (
										<li key={ key } className="qm-info qm-supplemental">
											{ label }: { info[key] as string }
										</li>
									);
								} ) }
							</ul>
						</Toggler>
					);
				},
				filters: {
					options: [ Object.keys( data.types ).sort().map( ( type ) => ( {
						key: type,
						label: type,
					} ) ) ],
					callback: ( row, value ) => {
						switch ( value ) {
							case 'non-blocking':
								return row.args.blocking === false;
							case 'error':
								return Utils.isWPError( row.result );
							default:
								if ( Utils.isWPError( row.result ) ) {
									return false;
								}
								return `HTTP ${row.result.code}` === value;
						}
					},
				},
			},
			caller: getCallerCol( data.http, settings ),
			component: getComponentCol( data.http ),
			size: {
				heading: _x( 'Size', 'size of HTTP response', 'query-monitor' ),
				className: 'qm-num',
				render: ( row ) => {
					const info = row.info && typeof row.info === 'object' ? row.info : null;

					if ( ! info || ! ( 'size_download' in info ) ) {
						return '';
					}

					return <ApproximateSize value={ info.size_download as number } />;
				},
			},
			timeout: {
				heading: __( 'Timeout', 'query-monitor' ),
				className: 'qm-num',
				render: ( row ) => row.intercepted ? '' : row.args.timeout,
			},
			time: {
				heading: __( 'Time', 'query-monitor' ),
				className: 'qm-num',
				render: ( row ) => row.intercepted ? '' : <Duration value={ row.ltime } />,
			},
		} }
		data={ data.http }
		rowHasError={ Utils.httpRowHasError }
		footer={ ( { cols, count, total, data: filteredData } ) => (
			<PanelFooter
				cols={ cols - 1 }
				count={ count }
				total={ total }
			>
				<td className="qm-num">
					<TotalTime rows={ filteredData.filter( row => !row.intercepted ) }/>
				</td>
			</PanelFooter>
		) }
	/>
};
