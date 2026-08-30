import {
	AbstractData,
	Asset,
	Backtrace,
	DataTypes,
	PHP_Error,
	QueryRow,
	URL,
} from '../../output/data-types';

/**
 * The properties every collector's data object carries. Panels and menu
 * builders rarely read these, so tests can spread this in and forget about it.
 */
export const abstractData: AbstractData = {
	types: {},
	concerned_filters: null,
	concerned_actions: null,
};

export const backtrace = ( component: string = 'plugin: foo' ): Backtrace => ( {
	component: {
		type: 'plugin',
		name: component,
		context: 'foo',
	},
	callsite: null,
	frames: [],
	time: 0,
} );

export const url = ( absolute: string, local: boolean = true ): URL => ( {
	host: 'example.org',
	local,
	absolute,
} );

export const phpError = ( error: Partial<PHP_Error> = {} ): PHP_Error => ( {
	errno: 512,
	level: 'warning',
	suppressed: false,
	message: 'This is a test warning',
	count: 1,
	...error,
} );

export const phpErrorsData = ( errors: Record<string, PHP_Error> ): DataTypes['php_errors'] => ( {
	...abstractData,
	errors,
} );

export const asset = ( overrides: Partial<Asset> = {} ): Asset => ( {
	handle: 'qm-test',
	position: 'footer',
	url: url( 'https://example.org/qm-test.js' ),
	source: 'https://example.org/qm-test.js',
	ver: '1.0',
	warning: false,
	dependents: [],
	dependencies: [],
	...overrides,
} );

export const assetsData = (
	assets: Asset[],
	missing_dependencies: Record<string, true> = {}
): DataTypes['assets_scripts'] => ( {
	...abstractData,
	assets,
	url: url( 'https://example.org' ),
	missing_dependencies,
} );

export const queryRow = ( overrides: Partial<QueryRow> = {} ): QueryRow => ( {
	sql: 'SELECT 123 FROM wp_posts',
	ltime: 0.001,
	stack: [ 'do_thing()' ],
	...overrides,
} );

export const dbQueriesData = ( rows: QueryRow[] ): DataTypes['db_queries'] => ( {
	...abstractData,
	total_qs: rows.length,
	errors: [],
	rows,
	has_result: false,
	has_trace: false,
} );

export const doingItWrongData = (
	actions: DataTypes['doing_it_wrong']['actions']
): DataTypes['doing_it_wrong'] => ( {
	...abstractData,
	actions,
} );
