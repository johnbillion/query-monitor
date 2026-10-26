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
 * @property {string} fileName
 * @property {string|null} extends
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
 * @typedef {SimpleTypeNode|UnionTypeNode|ArrayTypeNode|MapTypeNode|ObjectTypeNode|ClassRefTypeNode} TypeNode
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
 * @typedef {Object} ClassRefTypeNode
 * @property {'class_ref'} kind
 * @property {string} className
 * @property {string} fileName
 * @property {PHPClassNode} classNode
 * @property {boolean} optional
 */

/**
 * @typedef {Object} ObjectPropertyNode
 * @property {string} name
 * @property {TypeNode} type
 * @property {boolean} required
 */

/**
 * @typedef {Object} GeneratorOutput
 * @property {PHPFileNode} main
 * @property {Map<string, PHPClassNode>} extractedClasses
 */

// =============================================================================
// Schema to AST Transformation
// =============================================================================

/**
 * @param {object} schema
 * @returns {GeneratorOutput}
 */
function schemaToAST( schema ) {
	/** @type {Map<string, PHPClassNode>} */
	const extractedClasses = new Map();

	// First pass: extract classes from definitions that have phpFile
	if ( schema.definitions ) {
		for ( const key in schema.definitions ) {
			const definition = schema.definitions[ key ];
			if ( definition.phpFile ) {
				extractDefinitionAsClass( definition, schema, extractedClasses );
			}
		}
	}

	const typeDefs = [];

	// Second pass: build typeDefs for definitions that are NOT extracted as classes
	if ( schema.definitions ) {
		for ( const key in schema.definitions ) {
			const definition = schema.definitions[ key ];
			// Skip definitions that are extracted as classes
			if ( definition.phpFile ) {
				continue;
			}
			typeDefs.push( {
				kind: 'type_def',
				name: key,
				type: propToTypeNode( definition, true, schema, extractedClasses ),
			} );
		}
	}

	const properties = [];

	for ( const key in schema.properties ) {
		const required = schema.required?.includes( key ) ?? false;
		const prop = resolveRef( schema.properties[ key ], schema );
		const type = propToTypeNode( prop, required, schema, extractedClasses );

		properties.push( {
			kind: 'property',
			name: key,
			description: prop.description || null,
			type,
			usePhpStanVar: hasComplexType( type ) || !! prop.phpStanType,
		} );
	}

	return {
		main: {
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
		},
		extractedClasses,
	};
}

/**
 * @param {object} prop
 * @param {boolean} required
 * @param {object} schema
 * @param {Map<string, PHPClassNode>} extractedClasses
 * @returns {TypeNode}
 */
function propToTypeNode( prop, required, schema, extractedClasses ) {
	prop = resolveRef( prop, schema, extractedClasses );
	const optional = ! required;

	// Handle $ref to a definition with phpFile
	if ( prop._isClassRef && prop.phpFile ) {
		const className = prop.phpClass;
		const fileName = prop.phpFile;

		// Ensure the class is extracted (it should already be from first pass, but handle nested refs)
		if ( ! extractedClasses.has( className ) ) {
			extractDefinitionAsClass( prop, schema, extractedClasses );
		}

		return {
			kind: 'class_ref',
			className,
			fileName,
			classNode: extractedClasses.get( className ),
			optional,
		};
	}

	if ( prop.phpStanType && prop.phpStanType !== 'object' ) {
		return { kind: 'simple', name: prop.phpStanType, optional };
	}

	if ( prop.phpType ) {
		return { kind: 'simple', name: prop.phpType, optional };
	}

	if ( prop.anyOf ) {
		return {
			kind: 'union',
			types: prop.anyOf.map( ( one ) => propToTypeNode( one, true, schema, extractedClasses ) ),
			optional,
		};
	}

	if ( prop.oneOf ) {
		return {
			kind: 'union',
			types: prop.oneOf.map( ( one ) => propToTypeNode( one, true, schema, extractedClasses ) ),
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
					items: propToTypeNode( prop.items, true, schema, extractedClasses ),
					optional,
				};
			}
			return {
				kind: 'array',
				items: { kind: 'simple', name: 'mixed', optional: false },
				optional,
			};

		case 'object': {
			// Check if this object should be extracted as a separate class
			if ( prop.phpFile ) {
				const className = prop.phpClass;
				const fileName = prop.phpFile;

				// Build the class node if not already extracted
				if ( ! extractedClasses.has( className ) ) {
					const classProps = [];

					if ( prop.properties ) {
						for ( const subKey in prop.properties ) {
							const subRequired = prop.required?.includes( subKey ) ?? false;
							const sub = prop.properties[ subKey ];
							const subType = propToTypeNode( sub, subRequired, schema, extractedClasses );

							classProps.push( {
								kind: 'property',
								name: subKey,
								description: sub.description || null,
								type: subType,
								usePhpStanVar: hasComplexType( subType ) || !! sub.phpStanType,
							} );
						}
					}

					extractedClasses.set( className, {
						kind: 'class',
						name: className,
						fileName,
						extends: null,
						description: prop.description || `${ className } data object.`,
						typeDefs: [],
						properties: classProps,
					} );
				}

				return {
					kind: 'class_ref',
					className,
					fileName,
					classNode: extractedClasses.get( className ),
					optional,
				};
			}

			const baseType = prop.phpStanType === 'object' ? 'object' : 'array';

			if ( prop.properties ) {
				const objProps = [];
				for ( const subKey in prop.properties ) {
					const subRequired = prop.required?.includes( subKey ) ?? false;
					const sub = prop.properties[ subKey ];
					objProps.push( {
						name: subKey,
						type: propToTypeNode( sub, true, schema, extractedClasses ),
						required: subRequired,
					} );
				}
				return { kind: 'object', properties: objProps, baseType, optional };
			}

			if ( prop.additionalProperties?.type ) {
				return {
					kind: 'map',
					values: propToTypeNode( prop.additionalProperties, true, schema, extractedClasses ),
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
		case 'class_ref':
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
 * Extract a definition as a class and add it to extractedClasses.
 * @param {object} definition
 * @param {object} schema
 * @param {Map<string, PHPClassNode>} extractedClasses
 */
function extractDefinitionAsClass( definition, schema, extractedClasses ) {
	const className = definition.phpClass;
	const fileName = definition.phpFile;

	if ( extractedClasses.has( className ) ) {
		return;
	}

	// Create a placeholder entry first to handle self-referential types
	const classNode = {
		kind: 'class',
		name: className,
		fileName,
		extends: null,
		description: definition.description || `${ className } data object.`,
		typeDefs: [],
		properties: [],
	};
	extractedClasses.set( className, classNode );

	// Now process properties (self-references will find the placeholder)
	if ( definition.properties ) {
		for ( const subKey in definition.properties ) {
			const subRequired = definition.required?.includes( subKey ) ?? false;
			const sub = definition.properties[ subKey ];
			const subType = propToTypeNode( sub, subRequired, schema, extractedClasses );

			classNode.properties.push( {
				kind: 'property',
				name: subKey,
				description: sub.description || null,
				type: subType,
				usePhpStanVar: hasComplexType( subType ) || !! sub.phpStanType,
			} );
		}
	}
}

/**
 * @param {object} prop
 * @param {object} schema
 * @param {Map<string, PHPClassNode>} extractedClasses
 * @returns {object|null} Returns null if this ref should become a class_ref
 */
function resolveRef( prop, schema, extractedClasses = null ) {
	if ( ! prop.$ref ) {
		return prop;
	}

	if ( prop.$ref.startsWith( '#/definitions/' ) ) {
		const definitionName = prop.$ref.replace( '#/definitions/', '' );
		const refProp = schema.definitions[ definitionName ] || prop;

		// If the definition has phpFile and we have extractedClasses, mark for class_ref handling
		if ( refProp.phpFile && extractedClasses ) {
			// Return the definition with a marker that propToTypeNode can detect
			return { ...refProp, _isClassRef: true };
		}

		refProp.phpStanType = refProp.phpStanType || definitionName;
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
 * Print an extracted class (no extends, simpler header).
 * @param {PHPClassNode} node
 * @returns {string}
 */
function printExtractedClass( node ) {
	const lines = [];

	lines.push( '<?php declare(strict_types = 1);' );
	lines.push( '/**' );
	lines.push( ' * This file is generated by the generate.mjs script.' );
	lines.push( ' * Do not edit it manually.' );
	lines.push( ' */' );
	lines.push( '' );

	lines.push( '/**' );
	lines.push( ` * ${ node.description }` );
	lines.push( ' *' );
	lines.push( ' * @package query-monitor' );
	lines.push( ' */' );

	lines.push( '' );

	lines.push( '/**' );
	lines.push( ' * @implements \\ArrayAccess<string,mixed>' );
	lines.push( ' */' );
	lines.push( `class ${ node.name } implements \\ArrayAccess {` );
	lines.push( '\tuse QM_ArrayAccess;' );

	if ( node.properties.length > 0 ) {
		lines.push( '' );
	}

	for ( let i = 0; i < node.properties.length; i++ ) {
		const isLast = i === node.properties.length - 1;
		lines.push( ...printProperty( node.properties[ i ], isLast ) );
	}

	lines.push( '}' );

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

	const extendsClause = node.extends ? ` extends ${ node.extends }` : '';
	lines.push( `class ${ node.name }${ extendsClause } {` );

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

		case 'class_ref':
			return `${ optionalMarker }${ node.className }`;

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
// Standalone Schema to AST Transformation
// =============================================================================

/**
 * Process a standalone schema (not a data schema) that has phpFile/phpClass.
 * These generate classes without extending QM_Data.
 *
 * @param {object} schema
 * @returns {PHPClassNode}
 */
function standaloneSchemaToClassNode( schema ) {
	const classNode = {
		kind: 'class',
		name: schema.phpClass,
		fileName: schema.phpFile,
		extends: null,
		description: schema.description || `${ schema.phpClass } data object.`,
		typeDefs: [],
		properties: [],
	};

	if ( schema.properties ) {
		for ( const key in schema.properties ) {
			const required = schema.required?.includes( key ) ?? false;
			const prop = schema.properties[ key ];
			const type = propToTypeNode( prop, required, schema, new Map() );

			classNode.properties.push( {
				kind: 'property',
				name: key,
				description: prop.description || null,
				type,
				usePhpStanVar: hasComplexType( type ) || !! prop.phpStanType,
			} );
		}
	}

	return classNode;
}

// =============================================================================
// Main
// =============================================================================

// Process data schemas (generate QM_Data_* classes that extend QM_Data)
const dataDir = './src/schemas/data';
const dataFiles = fs.readdirSync( dataDir );

for ( const file of dataFiles ) {
	const [ basename, ext ] = file.split( '.' );

	if ( ext !== 'json' ) {
		continue;
	}

	const path = `${ dataDir }/${ file }`;
	const schema = JSON.parse( fs.readFileSync( path, 'utf8' ) );
	const { main, extractedClasses } = schemaToAST( schema );
	const output = printPHP( main );

	fs.writeFileSync( `./data/${ basename }.php`, output );

	// Write extracted classes to subdirectory
	if ( extractedClasses.size > 0 ) {
		const subdir = `./data/${ basename }`;

		if ( ! fs.existsSync( subdir ) ) {
			fs.mkdirSync( subdir, { recursive: true } );
		}

		for ( const [ , classNode ] of extractedClasses ) {
			const classOutput = printExtractedClass( classNode );
			fs.writeFileSync( `${ subdir }/${ classNode.fileName }.php`, classOutput );
		}
	}
}

// Process standalone schemas (generate classes without extending QM_Data)
const standaloneDir = './src/schemas';
const standaloneFiles = fs.readdirSync( standaloneDir );

for ( const file of standaloneFiles ) {
	const [ , ext ] = file.split( '.' );

	if ( ext !== 'json' ) {
		continue;
	}

	const path = `${ standaloneDir }/${ file }`;
	const schema = JSON.parse( fs.readFileSync( path, 'utf8' ) );

	if ( ! schema.phpFile || ! schema.phpClass ) {
		continue;
	}

	const classNode = standaloneSchemaToClassNode( schema );
	const classOutput = printExtractedClass( classNode );
	fs.writeFileSync( `./data/${ classNode.fileName }.php`, classOutput );
}
