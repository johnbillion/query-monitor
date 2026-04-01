import {
	StackFrame,
} from './data-types';

/**
 * A frame lookup entry: a StackFrame without the line number.
 */
export type FrameLookupEntry = Omit<StackFrame, 'line'>;

/**
 * A compact frame reference as sent over the wire: [frameIndex, lineNumber].
 */
export type CompactFrame = [ number, number | null ];

let frameLookup: FrameLookupEntry[] = [];
let fileLookup: string[] = [];

/**
 * Initialise the frame lookup table. Call once at startup before any
 * component renders.
 */
export function setFrameLookup( frames: FrameLookupEntry[] ): void {
	frameLookup = frames;
}

/**
 * Initialise the file path lookup table. Call once at startup before any
 * component renders.
 */
export function setFileLookup( files: string[] ): void {
	fileLookup = files;
}

/**
 * Resolve a file index to its full path, or return null.
 */
export function resolveFile( index: number | null | undefined ): string | null {
	if ( index == null ) {
		return null;
	}

	return fileLookup[ index ] ?? null;
}

/**
 * Resolve a single compact frame reference into a full StackFrame
 * with the file index resolved to a path string.
 */
export function resolveFrame( frame: CompactFrame ): ResolvedFrame {
	const [ index, line ] = frame;
	const entry = frameLookup[ index ];

	return {
		id: entry.id,
		args: entry.args,
		file: entry.file,
		line,
	};
}

/**
 * A resolved frame with file as a string path instead of an index.
 */
export interface ResolvedFrame {
	id: string;
	args?: string | null;
	file: string | null;
	line?: number | null;
}

/**
 * Resolve an array of compact frame references into full resolved frames.
 */
export function resolveFrames( frames: CompactFrame[] ): ResolvedFrame[] {
	return frames.map( resolveFrame );
}
