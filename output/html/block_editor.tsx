import * as React from 'react';
import { Tabular, PanelFooter, iPanelProps } from 'qmi';
import { __, _x, _n, sprintf } from '@wordpress/i18n';

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
			<Tabular id={this.props.id}>
				<thead>
					<tr>
						<th scope="col" role="columnheader">
							#
						</th>
						<th scope="col">
							{__( 'Block Name', 'query-monitor' )}
						</th>
						<th scope="col">
							{__( 'Attributes', 'query-monitor' )}
						</th>
						<th scope="col">
							{__( 'Render Callback', 'query-monitor' )}
						</th>
						<th scope="col">
							{__( 'Render Time', 'query-monitor' )}
						</th>
						<th scope="col">
							{__( 'Inner HTML', 'query-monitor' )}
						</th>
					</tr>
				</thead>
				<tbody>
					{data.post_blocks.map(function(block,i){
						const show_attrs = ( ! Array.isArray( block.attrs ) || block.attrs.length > 0 );
						return (
							<tr key={i}>
								<th scope="row" className="qm-row-num qm-num">{1+i}</th>
								<td className="qm-ltr qm-wrap">{block.blockName}</td>
								<td><pre className="qm-pre-wrap"><code>{show_attrs && JSON.stringify(block.attrs,null,2)}</code></pre></td>
								<td>{block.dynamic && block.callback && block.callback.name}</td>
								<td>{block.dynamic && block.timing}</td>
								<td><pre className="qm-pre-wrap"><code>{block.innerHTML}</code></pre></td>
							</tr>
						)
					})}
				</tbody>
				<PanelFooter cols={6} label={_x( 'Total:', 'Content blocks used', 'query-monitor' )} count={data.post_blocks.length}>
				</PanelFooter>
			</Tabular>
		)
	}

}

export default BlockEditor;
