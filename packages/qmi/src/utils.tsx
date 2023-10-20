import * as React from 'react';
import { WP_Error } from 'wp-types';

export function formatSQL( sql: string ) {
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

export function formatURL( url: string ) {
	const paramRegex = '(\\?|&)';
	const parts = url.split( new RegExp( paramRegex ) );
	const collection: JSX.Element[] = [
		<React.Fragment key="part-0">
			{ parts[0] }
		</React.Fragment>,
	];
	let index = 0;

	url.replace( new RegExp( paramRegex, 'g' ), ( match, keyword ) => {
		index += 2;

		collection.push(
			<React.Fragment key={ `part-${index}` }>
				<br />
				{ `${ keyword }${ parts[ index ] }` }
			</React.Fragment>
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
