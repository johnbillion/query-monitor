import { EmptyPanel } from '../panels/empty-panel';
import { TabularPanel } from '../panels/tabular-panel';
import { Time } from '../components/time';
import { JsonOutput } from '../components/json-output';
import { SourceLocation } from '../components/source-location';
import { DataTypes, PostBlock } from '../data-types';
import { PanelProps } from '../types';
import { Cols } from '../table';
import {
	__,
} from '@wordpress/i18n';

interface iBlock extends PostBlock {
	index?: string;
	innerBlocks: iBlock[];
}

type iBlockData = Omit<DataTypes['block_editor'], 'post_blocks'> & {
	post_blocks: iBlock[];
}

// Flatten nested blocks with proper indexing
const flattenBlocks = (blocks: iBlock[], prefix = ''): iBlock[] => {
	const result: iBlock[] = [];

	blocks.forEach((block, i) => {
		const index = prefix ? `${prefix}.${i + 1}` : (i + 1).toString();
		result.push({ ...block, index });

		if (block.innerBlocks && block.innerBlocks.length > 0) {
			result.push(...flattenBlocks(block.innerBlocks, index));
		}
	});

	return result;
};

export const BlockEditor = ( { data }: PanelProps<iBlockData> ) => {
	if ( ! data.post_blocks ) {
		return null;
	}

	if ( ! data.post_has_blocks ) {
		return (
			<EmptyPanel>
				<p>{ __( 'This post contains no blocks.', 'query-monitor' ) }</p>
			</EmptyPanel>
		);
	}

	const flatBlocks = flattenBlocks(data.post_blocks);
	const show_attrs = (block: iBlock) => (!Array.isArray(block.attrs) || block.attrs.length > 0);

	const cols: Cols<iBlock> = {
		index: {
			heading: '#',
			render: (row: iBlock) => row.index,
		},
		blockName: {
			heading: __('Block Name', 'query-monitor'),
			className: 'qm-ltr qm-wrap',
			render: (row: iBlock) => row.blockName,
			wrap: true,
		},
		attrs: {
			heading: __('Attributes', 'query-monitor'),
			className: 'qm-cell-block-attrs',
			render: (row: iBlock) => show_attrs(row) && (
				<JsonOutput data={ row.attrs } />
			),
		},
	};

	cols.context = {
		heading: __('Context', 'query-monitor'),
		className: 'qm-cell-block-context',
		render: (row: iBlock) => row.context && show_attrs(row) && (
			<JsonOutput data={ row.context } />
		),
	};

	cols.callback = {
		heading: __('Render Callback', 'query-monitor'),
		render: (row: iBlock) => row.dynamic && row.callback?.name && (
			<SourceLocation
				text={ row.callback.name }
				file={ row.callback.file || null }
				line={ row.callback.line || null }
			/>
		),
	};

	cols.timing = {
		heading: __('Render Time', 'query-monitor'),
		className: 'qm-cell-num qm-num',
		render: (row: iBlock) => row.dynamic ? <Time value={row.timing} /> : null,
	};

	cols.innerHTML = {
		heading: __('Inner HTML', 'query-monitor'),
		className: 'qm-cell-block-html',
		render: (row: iBlock) => (
			<pre className="qm-pre-wrap">
				<code>
					{row.innerHTML}
				</code>
			</pre>
		),
	};

	return (
		<TabularPanel
			title={__('Blocks', 'query-monitor')}
			cols={cols}
			data={flatBlocks}
		/>
	);
}

