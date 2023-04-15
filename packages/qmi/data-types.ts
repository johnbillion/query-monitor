/* eslint-disable */

import {
	WP_Screen,
} from 'wp-types';

export interface DataTypes {
	Admin?: Admin;
	Assets?: Assets;
	Block_Editor?: Block_Editor;
}
/**
 * Admin screen data transfer object.
 */
export interface Admin {
	current_screen?: WP_Screen;
	hook_suffix: string;
	list_table?: {
		columns_filter: string;
		sortables_filter: string;
		column_action: string;
		class_name?: string;
	};
	pagenow: string;
	taxnow: string;
	typenow: string;
}
/**
 * Asset data transfer object.
 */
export interface Assets {
	assets?: {
		missing: AssetList;
		broken: AssetList;
		header: AssetList;
		footer: AssetList;
	};
	counts: {
		missing: number;
		broken: number;
		header: number;
		footer: number;
		total: number;
	};
	default_version: string;
	dependencies: string[];
	dependents: string[];
	footer: string[];
	header: string[];
	host: string;
	is_ssl: boolean;
	missing_dependencies: string[];
	port: string;
}
/**
 * Block editor data transfer object.
 */
export interface Block_Editor {
	all_dynamic_blocks: string[];
	block_editor_enabled: boolean;
	has_block_context: boolean;
	has_block_timing: boolean;
	post_blocks: unknown[];
	post_has_blocks: boolean;
	total_blocks: number;
}
