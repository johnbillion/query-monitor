import {
	iPanelProps,
	EmptyPanel,
	PanelFooter,
	Panel,
	Time,
} from 'qmi';
import {
	DataTypes,
} from 'qmi/data-types';
import * as React from 'react';

import {
	__,
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

export default ( { data }: iPanelProps<iBlockData> ) => {
		if ( ! data.block_editor_enabled || ! data.post_blocks ) {
			return null;
		}

		if ( ! data.post_has_blocks ) {
			return (
				<EmptyPanel>
					<p>{ __( 'This post contains no blocks.', 'query-monitor' ) }</p>
				</EmptyPanel>
			);
		}

		let colspan = 5;

		data.has_block_context && colspan++;
		data.has_block_timing && colspan++;

		return (
			<Panel>
				<table>
					<caption>
						<h2 id="qm-panel-title">
							{ __( 'Blocks', 'query-monitor' ) }
						</h2>
					</caption>
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
						{ data.post_blocks.map( ( block, i ) => (
							<RenderBlock
								key={ i }
								block={ block }
								data={ data }
								i={ ( i + 1 ).toString() }
							/>
						) ) }
					</tbody>
					<PanelFooter
						cols={ colspan }
						count={ data.post_blocks.length }
					/>
				</table>
			</Panel>
		);
}

interface iBlockProps {
	block: iBlock;
	data: iBlockData;
	i: string;
}

const RenderBlock = ( { block, data, i }: iBlockProps ) => {
	const show_attrs = ( ! Array.isArray( block.attrs ) || block.attrs.length > 0 );

	return (
		<React.Fragment key={ i }>
			<tr>
				<th className="qm-row-num qm-num" scope="row">
					{ i }
				</th>
				<td className="qm-ltr qm-wrap">
					{/* @todo sticky */}
					{ block.blockName }
				</td>
				<td className="qm-row-block-attrs">
					{ show_attrs && (
						<pre className="qm-pre-wrap">
							<code>
								{ JSON.stringify( block.attrs, null, 2 ) }
							</code>
						</pre>
					) }
				</td>
				{ data.has_block_context && (
					<td className="qm-row-block-context">
						{ block.context && show_attrs && (
							<pre className="qm-pre-wrap">
								<code>
									{ JSON.stringify( block.context, null, 2 ) }
								</code>
							</pre>
						) }
					</td>
				) }
				<td>
					{ block.dynamic && block.callback?.name }
				</td>
				{ data.has_block_timing && (
					block.dynamic ? (
						<td>
							<Time value={ block.timing } />
						</td>
					) : (
						<td></td>
					)
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
				<RenderBlock
					key={ j }
					block={ innerBlock }
					data={ data }
					i={ `${i}.${j + 1}` }
				/>
			) ) }
		</React.Fragment>
	);
};
