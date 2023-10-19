import {
	iPanelProps,
	Notice,
	PanelFooter,
	Tabular,
} from 'qmi';
import {
	DataTypes,
} from 'qmi/data-types';
import * as React from 'react';

import {
	__,
	_x,
} from '@wordpress/i18n';

interface iBlock {
	attrs?: object;
	context?: object;
	blockName: string;
	innerHTML: string;
	innerBlocks: iBlock[];
	dynamic: boolean;
	callback?: {
		name: string;
	};
	size: number;
	timing: number;
}

type iBlockData = Omit<DataTypes['Block_Editor'], 'post_blocks'> & {
	post_blocks: iBlock[];
}

class BlockEditor extends React.Component<iPanelProps<iBlockData>, Record<string, unknown>> {

	render() {
		const { data } = this.props;

		if ( ! data.block_editor_enabled || ! data.post_blocks ) {
			return null;
		}

		if ( ! data.post_has_blocks ) {
			return (
				<Notice id={ this.props.id }>
					<p>{ __( 'This post contains no blocks.', 'query-monitor' ) }</p>
				</Notice>
			);
		}

		let colspan = 5;

		data.has_block_context && colspan++;
		data.has_block_timing && colspan++;

		return (
			<Tabular id={ this.props.id }>
				<thead>
					<tr>
						<th scope="col">
							#
						</th>
						<th scope="col">
							{ __( 'Block Name', 'query-monitor' ) }
						</th>
						<th scope="col">
							{ __( 'Attributes', 'query-monitor' ) }
						</th>
						{ data.has_block_context && (
							<th scope="col">
								{ __( 'Context', 'query-monitor' ) }
							</th>
						) }
						<th scope="col">
							{ __( 'Render Callback', 'query-monitor' ) }
						</th>
						{ data.has_block_timing && (
							<th scope="col">
								{ __( 'Render Time', 'query-monitor' ) }
							</th>
						) }
						<th scope="col">
							{ __( 'Inner HTML', 'query-monitor' ) }
						</th>
					</tr>
				</thead>
				<tbody>
					{ data.post_blocks.map( ( block, i ) => this.renderBlock( block, `${i + 1}` ) ) }
				</tbody>
				<PanelFooter
					cols={ colspan }
					count={ data.post_blocks.length }
					label={ _x( 'Total:', 'Content blocks used', 'query-monitor' ) }
				/>
			</Tabular>
		);
	}

	renderBlock( block: iBlock, i: string ) {
		const { data } = this.props;
		const show_attrs = ( ! Array.isArray( block.attrs ) || block.attrs.length > 0 );

		return (
			<React.Fragment key={ i }>
				<tr>
					<th className="qm-row-num qm-num" scope="row">
						{ i }
					</th>
					<td className="qm-ltr qm-wrap">
						{ block.blockName }
					</td>
					<td className="qm-row-block-attrs">
						<pre className="qm-pre-wrap">
							<code>
								{ show_attrs && JSON.stringify( block.attrs, null, 2 ) }
							</code>
						</pre>
					</td>
					{ data.has_block_context && (
						<td className="qm-row-block-context">
							{ block.context && (
								<pre className="qm-pre-wrap">
									<code>
										{ show_attrs && JSON.stringify( block.context, null, 2 ) }
									</code>
								</pre>
							) }
						</td>
					) }
					<td>
						{ block.dynamic && block.callback?.name }
					</td>
					{ data.has_block_timing && (
						<td>
							{ block.dynamic && block.timing }
						</td>
					) }
					<td className="qm-row-block-html">
						<pre className="qm-pre-wrap">
							<code>
								{ block.innerHTML }
							</code>
						</pre>
					</td>
				</tr>
				{ block.innerBlocks.map( ( innerBlock, j ) => (
					this.renderBlock( innerBlock, `${i}.${j + 1}` )
				) ) }
			</React.Fragment>
		);
	}

}

export default BlockEditor;
