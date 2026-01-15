import * as React from 'react';
import { WP_Error } from 'wp-types';

declare const QueryMonitorData: {
	number_format: {
		thousands_sep: string;
		decimal_point: string;
	};
	l10n: {
		admin_url: string;
		ajaxurl: string;
		auth_nonce: {
			on: string;
			off: string;
		};
	};
};

export const qm_l10n = QueryMonitorData.l10n;

export function formatSQL( sql: string ): React.JSX.Element[] {
	const formatted = ' ' + sql.replace( /[\r\n\t]+/g, ' ' ).trim();
	const lineRegex = ' (ADD|AFTER|ALTER|AND|BEGIN|COMMIT|CREATE|DELETE|DESCRIBE|DO|DROP|ELSE|END|EXCEPT|EXPLAIN|FROM|GROUP|HAVING|INNER|INSERT|INTERSECT|LEFT|LIMIT|ON|OR|ORDER|OUTER|RENAME|REPLACE|RIGHT|ROLLBACK|SELECT|SET|SHOW|START|THEN|TRUNCATE|UNION|UPDATE|USE|USING|VALUES|WHEN|WHERE|XOR) ';
	const lines = formatted.split( new RegExp( lineRegex ) );
	const collection: React.JSX.Element[] = [];
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

export function formatURL( url: string ): React.JSX.Element[] {
	const paramRegex = '(\\?|&)';
	const parts = url.split( new RegExp( paramRegex ) );
	const collection: React.JSX.Element[] = [
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

/**
 * Shortens a fully qualified name to reduce the length of long namespaced symbols.
 *
 * This initialises portions that do not form the first or last portion of the name. For example:
 *
 *     Inpsyde\Wonolog\HookListener\HookListenersRegistry->hook_callback()
 *
 * becomes:
 *
 *     Inpsyde\W\H\HookListenersRegistry->hook_callback()
 *
 * @param fqn A fully qualified name.
 * @return A shortened version of the name.
 */
export function shortenFqn( fqn: string ): string {
	const backslashCount = ( fqn.match( /\\/g ) || [] ).length;

	if ( backslashCount < 3 ) {
		return fqn;
	}

	return fqn.replace( /\\[a-zA-Z0-9_\\]{4,}\\/g, ( match ) => {
		const initials = match.match( /\\([a-zA-Z0-9_])/g ) || [];
		return initials.join( '' ) + '\\';
	} );
}

export function numberFormat( number: number, decimals: number = 0 ): string {
	if ( isNaN( number ) ) {
		return '';
	}

	if ( ! decimals ) {
		decimals = 0;
	}

	const num_float = number.toFixed( decimals );
	const num_int = Math.floor( number );
	const num_str = num_int.toString();
	const fraction = num_float.substring( num_float.indexOf( '.' ) + 1, num_float.length );
	let o = '';

	if ( num_str.length > 3 ) {
		let i = 0;
		for ( i = num_str.length; i > 3; i -= 3 ) {
			o = QueryMonitorData.number_format.thousands_sep + num_str.slice( i - 3, i ) + o;
		}
		o = num_str.slice( 0, i ) + o;
	} else {
		o = num_str;
	}

	if ( decimals ) {
		o = o + QueryMonitorData.number_format.decimal_point + fraction;
	}

	return o;
}

/**
 * Generates a URL to the site editor for a given template or template part.
 *
 * @param template The template ID.
 * @param type     The post type, either 'wp_template' or 'wp_template_part'.
 * @return The site editor URL.
 */
export function getSiteEditorUrl( template: string, type: string = 'wp_template_part' ): string {
	const params = new URLSearchParams( {
		postType: type,
		postId: template,
		canvas: 'edit',
	} );

	return `${ qm_l10n.admin_url }site-editor.php?${ params.toString() }`;
}
