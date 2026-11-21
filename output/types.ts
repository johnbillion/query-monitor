export type PanelProps<T> = {
	data: T;
	enabled: boolean;
}

/**
 * The type-erased, dynamically-keyed view of the panel data object: panel id →
 * slice, with each slice's `data` opaque. Used at boundaries where a panel is
 * resolved by a runtime key and its concrete data type can't be correlated
 * statically.
 */
export type PanelDataMap = Record<string, PanelProps<unknown> | undefined>;
