import {
	iPanelProps,
	PanelFooter,
	Tabular,
} from 'qmi';
import * as React from 'react';

import {
	__,
	_x,
} from '@wordpress/i18n';

interface iBlocksProps extends iPanelProps {
	data: {
		post_blocks: {
			attrs?: any[];
			blockName: string;
			innerHTML: string;
			dynamic: boolean;
			callback?: {
				name: string;
			};
			size: number;
			timing: number;
		}[];
	};
}
class BlockEditor extends React.Component<iBlocksProps, Record<string, unknown>> {

	render() {
		const { data } = this.props;

		if ( ! data.post_blocks || ! data.post_blocks.length ) {
			return null;
		}

		return (
			<Tabular id={ this.props.id }>
				<thead>
					<tr>
						<th role="columnheader" scope="col">
							#
						</th>
						<th scope="col">
							{ __( 'Block Name', 'query-monitor' ) }
						</th>
						<th scope="col">
							{ __( 'Attributes', 'query-monitor' ) }
						</th>
						<th scope="col">
							{ __( 'Render Callback', 'query-monitor' ) }
						</th>
						<th scope="col">
							{ __( 'Render Time', 'query-monitor' ) }
						</th>
						<th scope="col">
							{ __( 'Inner HTML', 'query-monitor' ) }
						</th>
					</tr>
				</thead>
				<tbody>
					{ data.post_blocks.map( function ( block, i ){
						const show_attrs = ( ! Array.isArray( block.attrs ) || block.attrs.length > 0 );
						return (
							<tr key={ i }>
								<th className="qm-row-num qm-num" scope="row">
									{ 1 + i }
								</th>
								<td className="qm-ltr qm-wrap">
									{ block.blockName }
								</td>
								<td>
									<pre className="qm-pre-wrap">
										<code>
											{ show_attrs && JSON.stringify( block.attrs, null, 2 ) }
										</code>
									</pre>
								</td>
								<td>
									{ block.dynamic && block.callback && block.callback.name }
								</td>
								<td>
									{ block.dynamic && block.timing }
								</td>
								<td>
									<pre className="qm-pre-wrap">
										<code>
											{ block.innerHTML }
										</code>
									</pre>
								</td>
							</tr>
						);
					} ) }
				</tbody>
				<PanelFooter
					cols={ 6 }
					count={ data.post_blocks.length }
					label={ _x( 'Total:', 'Content blocks used', 'query-monitor' ) }
				/>
			</Tabular>
		);
	}

}

export default BlockEditor;
