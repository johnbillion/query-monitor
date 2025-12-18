import fs from 'fs';

// =============================================================================
// AST Node Types
// =============================================================================

/**
 * @typedef {Object} PHPFileNode
 * @property {'php_file'} kind
 * @property {string} sourceSchema
 * @property {PHPClassNode} class
 */

/**
 * @typedef {Object} PHPClassNode
 * @property {'class'} kind
 * @property {string} name
 * @property {string} extends
 * @property {string} description
 * @property {PHPTypeDefNode[]} typeDefs
 * @property {PHPPropertyNode[]} properties
 */

/**
 * @typedef {Object} PHPTypeDefNode
 * @property {'type_def'} kind
 * @property {string} name
 * @property {TypeNode} type
 */

/**
 * @typedef {Object} PHPPropertyNode
 * @property {'property'} kind
 * @property {string} name
 * @property {string|null} description
 * @property {TypeNode} type
 * @property {boolean} usePhpStanVar
 */

/**
 * @typedef {SimpleTypeNode|UnionTypeNode|ArrayTypeNode|MapTypeNode|ObjectTypeNode} TypeNode
 */

/**
 * @typedef {Object} SimpleTypeNode
 * @property {'simple'} kind
 * @property {string} name
 * @property {boolean} optional
 */

/**
 * @typedef {Object} UnionTypeNode
 * @property {'union'} kind
 * @property {TypeNode[]} types
 * @property {boolean} optional
 */

/**
 * @typedef {Object} ArrayTypeNode
 * @property {'array'} kind
 * @property {TypeNode} items
 * @property {boolean} optional
 */

/**
 * @typedef {Object} MapTypeNode
 * @property {'map'} kind
 * @property {TypeNode} values
 * @property {'array'|'object'} baseType
 * @property {boolean} optional
 */

/**
 * @typedef {Object} ObjectTypeNode
 * @property {'object'} kind
 * @property {ObjectPropertyNode[]} properties
 * @property {'array'|'object'} baseType
 * @property {boolean} optional
 */

/**
 * @typedef {Object} ObjectPropertyNode
 * @property {string} name
 * @property {TypeNode} type
 * @property {boolean} required
 */

// =============================================================================
// Schema to AST Transformation
// =============================================================================

/**
 * @param {object} schema
 * @returns {PHPFileNode}
 */
function schemaToAST( schema ) {
	const typeDefs = [];

	if ( schema.definitions ) {
		for ( const key in schema.definitions ) {
			const definition = schema.definitions[ key ];
			typeDefs.push( {
				kind: 'type_def',
				name: key,
				type: propToTypeNode( definition, true, schema ),
			} );
		}
	}

	const properties = [];

	for ( const key in schema.properties ) {
		const required = schema.required?.includes( key ) ?? false;
		const prop = resolveRef( schema.properties[ key ], schema );
		const type = propToTypeNode( prop, required, schema );

		properties.push( {
			kind: 'property',
			name: key,
			description: prop.description || null,
			type,
			usePhpStanVar: hasComplexType( type ) || !! prop.phpStanType,
		} );
	}

	return {
		kind: 'php_file',
		sourceSchema: schema.$id.replace( 'https://schemas.querymonitor.com/', '' ),
		class: {
			kind: 'class',
			name: `QM_Data_${ schema.title }`,
			extends: 'QM_Data',
			description: schema.description,
			typeDefs,
			properties,
		},
	};
}

/**
 * @param {object} prop
 * @param {boolean} required
 * @param {object} schema
 * @returns {TypeNode}
 */
function propToTypeNode( prop, required, schema ) {
	prop = resolveRef( prop, schema );
	const optional = ! required;

	if ( prop.phpStanType && prop.phpStanType !== 'object' ) {
		return { kind: 'simple', name: prop.phpStanType, optional };
	}

	if ( prop.phpType ) {
		return { kind: 'simple', name: prop.phpType, optional };
	}

	if ( prop.anyOf ) {
		return {
			kind: 'union',
			types: prop.anyOf.map( ( one ) => propToTypeNode( one, true, schema ) ),
			optional,
		};
	}

	if ( prop.oneOf ) {
		return {
			kind: 'union',
			types: prop.oneOf.map( ( one ) => propToTypeNode( one, true, schema ) ),
			optional,
		};
	}

	const type = prop.enum || prop.type;

	if ( typeof type === 'undefined' ) {
		return { kind: 'simple', name: 'mixed', optional };
	}

	if ( Array.isArray( type ) ) {
		return {
			kind: 'union',
			types: type.map( ( t ) => ( { kind: 'simple', name: getPHPType( t ), optional: false } ) ),
			optional,
		};
	}

	switch ( type ) {
		case 'array':
			if ( prop.items ) {
				return {
					kind: 'array',
					items: propToTypeNode( prop.items, true, schema ),
					optional,
				};
			}
			return {
				kind: 'array',
				items: { kind: 'simple', name: 'mixed', optional: false },
				optional,
			};

		case 'object': {
			const baseType = prop.phpStanType === 'object' ? 'object' : 'array';

			if ( prop.properties ) {
				const objProps = [];
				for ( const subKey in prop.properties ) {
					const subRequired = prop.required?.includes( subKey ) ?? false;
					const sub = prop.properties[ subKey ];
					objProps.push( {
						name: subKey,
						type: propToTypeNode( sub, true, schema ),
						required: subRequired,
					} );
				}
				return { kind: 'object', properties: objProps, baseType, optional };
			}

			if ( prop.additionalProperties?.type ) {
				return {
					kind: 'map',
					values: propToTypeNode( prop.additionalProperties, true, schema ),
					baseType,
					optional,
				};
			}

			return {
				kind: 'map',
				values: { kind: 'simple', name: 'mixed', optional: false },
				baseType,
				optional,
			};
		}

		default:
			return { kind: 'simple', name: getPHPType( type ), optional };
	}
}

/**
 * Check if a type node contains complex types (object shapes).
 * @param {TypeNode} node
 * @returns {boolean}
 */
function hasComplexType( node ) {
	switch ( node.kind ) {
		case 'simple':
			return false;
		case 'union':
			return node.types.some( hasComplexType );
		case 'array':
			return hasComplexType( node.items );
		case 'map':
			return hasComplexType( node.values );
		case 'object':
			return true;
	}
}

/**
 * @param {object} prop
 * @param {object} schema
 * @returns {object}
 */
function resolveRef( prop, schema ) {
	if ( ! prop.$ref ) {
		return prop;
	}

	if ( prop.$ref.startsWith( '#/definitions/' ) ) {
		const definition = prop.$ref.replace( '#/definitions/', '' );
		const refProp = schema.definitions[ definition ] || prop;
		refProp.phpStanType = refProp.phpStanType || definition;
		return refProp;
	}

	return prop;
}

/**
 * @param {string} type
 * @returns {string}
 */
function getPHPType( type ) {
	switch ( type ) {
		case 'string':
			return 'string';
		case 'null':
			return 'null';
		case 'number':
			return 'float';
		case 'integer':
			return 'int';
		case 'boolean':
			return 'bool';
		case 'array':
		case 'object':
			return 'array';
		default:
			return `'${ type }'`;
	}
}

// =============================================================================
// AST to PHP Code Printer
// =============================================================================

/**
 * @param {PHPFileNode} ast
 * @returns {string}
 */
function printPHP( ast ) {
	const lines = [];

	lines.push( '<?php declare(strict_types = 1);' );
	lines.push( '/**' );
	lines.push( ' * This file is generated by the generate.mjs script.' );
	lines.push( ' * Do not edit it manually.' );
	lines.push( ' *' );
	lines.push( ` * Source schema: ${ ast.sourceSchema }` );
	lines.push( ' */' );
	lines.push( '' );

	lines.push( ...printClass( ast.class ) );

	return lines.join( '\n' ) + '\n';
}

/**
 * @param {PHPClassNode} node
 * @returns {string[]}
 */
function printClass( node ) {
	const lines = [];

	// Class description docblock
	lines.push( '/**' );
	lines.push( ` * ${ node.description }` );
	lines.push( ' *' );
	lines.push( ' * @package query-monitor' );
	lines.push( ' */' );

	lines.push( '' );

	// Type definitions docblock (if any)
	if ( node.typeDefs.length > 0 ) {
		lines.push( '/**' );
		for ( const typeDef of node.typeDefs ) {
			lines.push( ` * @phpstan-type ${ typeDef.name } ${ printType( typeDef.type, 0, '' ) }` );
		}
		lines.push( ' */' );
	}

	lines.push( `class ${ node.name } extends ${ node.extends } {` );

	for ( let i = 0; i < node.properties.length; i++ ) {
		const isLast = i === node.properties.length - 1;
		lines.push( ...printProperty( node.properties[ i ], isLast ) );
	}

	lines.push( '}' );

	return lines;
}

/**
 * @param {PHPPropertyNode} node
 * @param {boolean} isLast
 * @returns {string[]}
 */
function printProperty( node, isLast = false ) {
	const lines = [];

	lines.push( '\t/**' );

	if ( node.description ) {
		lines.push( `\t * ${ node.description }` );
		lines.push( '\t *' );
	}

	const typeStr = printType( node.type, 0, '\t' );
	const varTag = node.usePhpStanVar ? '@phpstan-var' : '@var';

	lines.push( `\t * ${ varTag } ${ typeStr }` );
	lines.push( '\t */' );
	lines.push( `\tpublic $${ node.name };` );

	if ( ! isLast ) {
		lines.push( '' );
	}

	return lines;
}

/**
 * @param {TypeNode} node
 * @param {number} level
 * @param {string} prefix
 * @returns {string}
 */
function printType( node, level = 0, prefix = '\t' ) {
	const optionalMarker = node.optional ? '?' : '';

	switch ( node.kind ) {
		case 'simple':
			return `${ optionalMarker }${ node.name }`;

		case 'union': {
			const types = node.types.map( ( t ) => printType( t, level, prefix ) ).join( '|' );
			return `${ optionalMarker }${ types }`;
		}

		case 'array':
			return `${ optionalMarker }array<int, ${ printType( node.items, level, prefix ) }>`;

		case 'map':
			return `${ optionalMarker }${ node.baseType }<string, ${ printType( node.values, level, prefix ) }>`;

		case 'object': {
			const indentation = '  '.repeat( level );
			let result = `${ node.baseType }{`;

			for ( const prop of node.properties ) {
				const requiredMarker = prop.required ? '' : '?';
				const propType = printType( prop.type, level + 1, prefix );
				result += `\n${ prefix } *${ indentation }   ${ prop.name }${ requiredMarker }: ${ propType },`;
			}

			result += `\n${ prefix } *${ indentation } }`;
			return `${ optionalMarker }${ result }`;
		}
	}
}

// =============================================================================
// Main
// =============================================================================

const dir = './src/schemas/data';
const files = fs.readdirSync( dir );

for ( const file of files ) {
	const [ basename, ext ] = file.split( '.' );

	if ( ext !== 'json' ) {
		continue;
	}

	const path = `${ dir }/${ file }`;
	const schema = JSON.parse( fs.readFileSync( path, 'utf8' ) );
	const ast = schemaToAST( schema );
	const output = printPHP( ast );

	fs.writeFileSync( `./data/${ basename }.php`, output );
}
