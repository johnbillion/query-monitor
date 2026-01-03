import { EmptyPanel } from '../panels/empty-panel';
import { TabularPanel } from '../panels/tabular-panel';
import * as Utils from '../utils';
import { Warning } from '../components/warning';
import { getCallerCol, getComponentCol } from '../table';
import { Time } from '../components/time';
import { TotalTime } from '../components/total-time';
import { PanelFooter } from '../panels/panel-footer';
import { DataTypes } from '../data-types';
import { PanelProps } from '../types';
import * as React from 'react';

import {
	__,
	sprintf,
} from '@wordpress/i18n';

const hasHttpsWarning = ( row: DataTypes['http']['http'][0] ): boolean => {
	return ! row.url.startsWith( 'https://' );
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
						{ hasInterceptedWarning( row ) && (
							<div>
								<Warning>
									{ sprintf(
										__( 'This HTTP request was short-circuited by the %s filter and was not sent', 'query-monitor' ),
										'pre_http_request'
									) }
								</Warning>
							</div>
						) }
						{ Utils.formatURL( row.url ) }
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
						{ hasRedirectWarning( row ) && (
							<div>
								<Warning>
									{ sprintf( __( 'Redirected to: %s', 'query-monitor' ), row.redirected_to ) }
								</Warning>
							</div>
						) }
					</>
				),
				filters: {
					options: [
						...Array.from( new Set( data.http.map( row => row.host ) ) ).map( host => ({
							key: host,
							label: host,
						})),
					],
					callback: ( row, value ) => row.host === value,
				},
			},
			status: {
				heading: __( 'Status', 'query-monitor' ),
				render: ( row ) => {
					if ( Utils.isWPError( row.result ) ) {
						return (
							<Warning>
								{ sprintf(
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
						'size_download' in info ||
						'content_type' in info ||
						'primary_ip' in info
					);

					if ( ! hasInfo ) {
						return statusText;
					}

					const timeFields: { key: string; label: string }[] = [
						{ key: 'namelookup_time', label: __( 'DNS Resolution Time', 'query-monitor' ) },
						{ key: 'connect_time', label: __( 'Connection Time', 'query-monitor' ) },
						{ key: 'starttransfer_time', label: __( 'Transfer Start Time (TTFB)', 'query-monitor' ) },
					];

					const otherFields: { key: string; label: string }[] = [
						{ key: 'content_type', label: __( 'Response Content Type', 'query-monitor' ) },
					];

					const sizeDownload = 'size_download' in info ? info.size_download as number : null;

					return (
						<details>
							<summary>{ statusText }</summary>
							<ul className="qm-toggled">
								{ 'primary_ip' in info && (
									<li key="primary_ip">
										<span className="qm-info qm-supplemental">
											{ __( 'IP Address', 'query-monitor' ) }: { info.primary_ip as string }
										</span>
									</li>
								) }
								{ timeFields.map( ( { key, label } ) => {
									if ( ! ( key in info ) ) {
										return null;
									}
									return (
										<li key={ key }>
											<span className="qm-info qm-supplemental">
												{ label }: { Utils.numberFormat( info[key] as number, 4 ) }
											</span>
										</li>
									);
								} ) }
								{ sizeDownload !== null && (
									<li key="size_download">
										<span className="qm-info qm-supplemental">
											{ __( 'Response Size', 'query-monitor' ) }: { Utils.numberFormat( sizeDownload / 1024, 2 ) } KB
										</span>
									</li>
								) }
								{ otherFields.map( ( { key, label } ) => {
									if ( ! ( key in info ) ) {
										return null;
									}
									return (
										<li key={ key }>
											<span className="qm-info qm-supplemental">
												{ label }: { info[key] as string }
											</span>
										</li>
									);
								} ) }
							</ul>
						</details>
					);
				},
				filters: {
					options: Object.keys( data.types ).sort().map( ( type ) => ( {
						key: type,
						label: type,
					} ) ),
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
			caller: getCallerCol( data.http ),
			component: getComponentCol( data.http ),
			timeout: {
				heading: __( 'Timeout', 'query-monitor' ),
				className: 'qm-num',
				render: ( row ) => row.intercepted ? '' : row.args.timeout,
			},
			time: {
				heading: __( 'Time', 'query-monitor' ),
				className: 'qm-num',
				render: ( row ) => row.intercepted ? '' : <Time value={ row.ltime } />,
			},
		} }
		data={ data.http }
		rowHasError={ ( row ) =>
			Utils.isWPError( row.result ) ||
			( ! row.intercepted && row.result.code >= 400 )
		}
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
