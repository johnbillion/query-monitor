import { TabularPanel } from '../panels/tabular-panel';
import { EmptyPanel } from '../panels/empty-panel';
import { DataTypes } from '../data-types';
import { PanelProps } from '../types';
import { __ } from '@wordpress/i18n';

interface HeaderRow {
	name: string;
	value: string;
}

interface HeadersProps extends PanelProps<DataTypes['raw_request']> {
	type: 'request' | 'response';
}

export const Headers = ( { data, type }: HeadersProps ) => {
	const headers = data[type]?.headers;

	if ( ! headers || Object.keys( headers ).length === 0 ) {
		return (
			<EmptyPanel>
				<p>
					{ type === 'request'
						? __( 'No request headers found', 'query-monitor' )
						: __( 'No response headers found', 'query-monitor' )
					}
				</p>
			</EmptyPanel>
		);
	}

	// Convert headers object to array of row objects
	const headerRows: HeaderRow[] = Object.entries( headers ).map( ( [ name, value ] ) => ( {
		name,
		value,
	} ) );

	const title = type === 'request'
		? __( 'Request Headers', 'query-monitor' )
		: __( 'Response Headers', 'query-monitor' );

	const headerNameLabel = type === 'request'
		? __( 'Request Header Name', 'query-monitor' )
		: __( 'Response Header Name', 'query-monitor' );

	return (
		<TabularPanel
			title={ title }
			cols={ {
				name: {
					heading: headerNameLabel,
					render: ( row ) => {
						const formatted = row.name
							.split( /[-_]/ )
							.map( part => part.charAt( 0 ).toUpperCase() + part.slice( 1 ).toLowerCase() )
							.join( '-' );
						return <code>{ formatted }</code>;
					},
				},
				value: {
					heading: __( 'Value', 'query-monitor' ),
					render: ( row ) => (
						<pre className="qm-pre-wrap">
							<code>{ row.value }</code>
						</pre>
					),
				},
			} }
			data={ headerRows }
			footer={ () => (
				<tfoot>
					<tr>
						<td colSpan={ 2 }>
							{ __( 'Note that header names are not case-sensitive.', 'query-monitor' ) }
						</td>
					</tr>
				</tfoot>
			) }
		/>
	);
};
