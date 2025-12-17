import {
	PanelProps,
	EmptyPanel,
	TabularPanel,
	Utils,
	Warning,
	getComponentCol,
	getCallerCol,
	ApproximateSize,
	Time,
	TotalTime,
	PanelFooter,
} from 'qmi';
import {
	DataTypes,
} from 'qmi/data-types';
import * as React from 'react';

import {
	__,
	sprintf,
} from '@wordpress/i18n';

const getResponseSize = ( row: DataTypes['http']['http'][0] ): number => {
	// If request was intercepted, return 0 (no response)
	if ( row.intercepted ) {
		return 0;
	}

	// If response is an error, return 0
	if ( Utils.isWPError( row.response ) ) {
		return 0;
	}

	// For downloaded files, use size_download from info
	if ( row.info && typeof row.info === 'object' && 'size_download' in row.info && typeof row.info.size_download === 'number' ) {
		return row.info.size_download;
	}

	// For response bodies, use body length
	if ( row.response && typeof row.response === 'object' && 'body' in row.response && typeof row.response.body === 'string' ) {
		return row.response.body.length;
	}

	return 0;
};

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
					if ( row.intercepted ) {
						return '';
					}
					if ( Utils.isWPError( row.response ) ) {
						return (
							<Warning>
								{ sprintf(
									__( 'Error: %s', 'query-monitor' ),
									Utils.getErrorMessage( row.response )
								) }
							</Warning>
						);
					}

					const statusText = row.args.blocking === false
						? __( 'Non-blocking', 'query-monitor' )
						: `${row.response.response.code} ${row.response.response.message}`;

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

					const timeFields: { key: string; label: string }[] = [
						{ key: 'namelookup_time', label: __( 'DNS Resolution Time', 'query-monitor' ) },
						{ key: 'connect_time', label: __( 'Connection Time', 'query-monitor' ) },
						{ key: 'starttransfer_time', label: __( 'Transfer Start Time (TTFB)', 'query-monitor' ) },
					];

					const otherFields: { key: string; label: string }[] = [
						{ key: 'content_type', label: __( 'Response Content Type', 'query-monitor' ) },
						{ key: 'primary_ip', label: __( 'IP Address', 'query-monitor' ) },
					];

					return (
						<details>
							<summary>{ statusText }</summary>
							<ul className="qm-toggled">
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
					options: Object.keys( data.types ).map( ( type ) => ( {
						key: type,
						label: type,
					} ) ),
					callback: ( row, value ) => {
						switch ( value ) {
							case 'non-blocking':
								return row.args.blocking === false;
							case 'error':
								return Utils.isWPError( row.response );
							default:
								if ( Utils.isWPError( row.response ) ) {
									return false;
								}
								return `HTTP ${row.response.response.code}` === value;
						}
					},
				},
			},
			caller: getCallerCol( data.http ),
			component: getComponentCol( data.http ),
			size: {
				heading: __( 'Response Size', 'query-monitor' ),
				className: 'qm-num',
				render: ( row ) => {
					if ( row.args.blocking === false ) {
						return '';
					}
					const size = getResponseSize( row );
					return size > 0 ? <ApproximateSize value={ size } /> : '';
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
				render: ( row ) => row.intercepted ? '' : <Time value={ row.ltime } />,
			},
		} }
		data={ data.http }
		rowHasError={ ( row ) =>
			Utils.isWPError( row.response ) ||
			( ! row.intercepted && row.response.response.code >= 400 )
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
