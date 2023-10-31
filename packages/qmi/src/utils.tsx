import * as React from 'react';
import { WP_Error } from 'wp-types';
import {
	Context,
} from './context';

export function formatSQL( sql: string ): JSX.Element[] {
	const formatted = ' ' + sql.replace( /[\r\n\t]+/g, ' ' ).trim();
	const lineRegex = ' (ADD|AFTER|ALTER|AND|BEGIN|COMMIT|CREATE|DELETE|DESCRIBE|DO|DROP|ELSE|END|EXCEPT|EXPLAIN|FROM|GROUP|HAVING|INNER|INSERT|INTERSECT|LEFT|LIMIT|ON|OR|ORDER|OUTER|RENAME|REPLACE|RIGHT|ROLLBACK|SELECT|SET|SHOW|START|THEN|TRUNCATE|UNION|UPDATE|USE|USING|VALUES|WHEN|WHERE|XOR) ';
	const lines = formatted.split( new RegExp( lineRegex ) );
	const collection: JSX.Element[] = [];
	let index = 0;

	formatted.replace( new RegExp( lineRegex, 'g' ), ( match, keyword ) => {
		index += 2;

		collection.push(
			<>
				{ index > 2 && (
					<br />
				) }
				<b>{ keyword }</b>
				{ ` ${ lines[ index ] }` }
			</>
		);

		return '';
	} );

	return collection;
}

export function formatURL( url: string ): JSX.Element[] {
	const paramRegex = '(\\?|&)';
	const parts = url.split( new RegExp( paramRegex ) );
	const collection: JSX.Element[] = [
		<>
			{ parts[0] }
		</>,
	];
	let index = 0;

	url.replace( new RegExp( paramRegex, 'g' ), ( match, keyword ) => {
		index += 2;

		collection.push(
			<>
				<br />
				{ `${ keyword }${ parts[ index ] }` }
			</>
		);

		return '';
	} );

	return collection;
}

export function isWPError( data: any ): data is WP_Error {
	return ( ( typeof data === 'object' ) && 'errors' in data );
}

export function getErrorData( data: any ): any {
	if ( ! ( ( typeof data === 'object' ) && 'error_data' in data ) ) {
		return null;
	}

	if ( Array.isArray( data.error_data ) ) {
		return null;
	}

	for ( const key in data.error_data ) {
		return data.error_data[key];
	}

	return null;
}

export function getErrorMessage( data: any ): string|null {
	if ( ! ( ( typeof data === 'object' ) && 'errors' in data ) ) {
		return null;
	}

	if ( Array.isArray( data.errors ) ) {
		return null;
	}

	for ( const key in data.errors ) {
		for ( const message_key in data.errors[key] ) {
			return data.errors[key][message_key];
		}
	}

	return null;
}

export function getEditors(): { label: string, name: string; format: string; }[] {
	return [
		{
			label: 'None',
			name: '',
			format: '',
		},
		{
			label: 'Atom',
			name: 'atom',
			format: 'atom://open/?url=file://%1$s&line=%2$s',
		},
		{
			label: 'Netbeans',
			name: 'netbeans',
			format: 'nbopen://%1$s:%2$s',
		},
		{
			label: 'Nova',
			name: 'nova',
			format: 'nova://open?path=%1$s&line=%2$s',
		},
		{
			label: 'PhpStorm',
			name: 'phpstorm',
			format: 'phpstorm://open?file=%1$s&line=%2$s',
		},
		{
			label: 'Sublime Text',
			name: 'sublime',
			format: 'subl://open/?url=file://%1$s&line=%2$s',
		},
		{
			label: 'TextMate',
			name: 'textmate',
			format: 'txmt://open/?url=file://%1$s&line=%2$s',
		},
		{
			label: 'Visual Studio Code',
			name: 'vscode',
			format: 'vscode://file/%1$s:%2$s',
		},
	];
}

export function getEditorFormat( name: string ): string {
	const editors = getEditors();

	for ( const editor of editors ) {
		if ( editor.name === name ) {
			return editor.format;
		}
	}

	return '';
}
