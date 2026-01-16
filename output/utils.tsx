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
			<React.Fragment key={ index }>
				{ index > 2 && (
					<br />
				) }
				<b>{ keyword }</b>
				{ ` ${ lines[ index ] }` }
			</React.Fragment>
		);

		return '';
	} );

	return collection;
}

export function formatURL( url: string ): React.JSX.Element[] {
	const paramRegex = '(\\?|&)';
	const parts = url.split( new RegExp( paramRegex ) );
	const collection: React.JSX.Element[] = [
		<React.Fragment key={ 0 }>
			{ parts[0] }
		</React.Fragment>,
	];
	let index = 0;

	url.replace( new RegExp( paramRegex, 'g' ), ( match, keyword ) => {
		index += 2;

		collection.push(
			<React.Fragment key={ index }>
				<br />
				{ `${ keyword }${ parts[ index ] }` }
			</React.Fragment>
		);

		return '';
	} );

	return collection;
}

export function isWPError( data: unknown ): data is WP_Error {
	return ( ( typeof data === 'object' ) && data !== null && 'errors' in data );
}

interface ErrorDataContainer {
	error_data?: Record<string, unknown>;
}

export function getErrorData( data: unknown ): unknown {
	if ( ! ( ( typeof data === 'object' ) && data !== null && 'error_data' in data ) ) {
		return null;
	}

	const container = data as ErrorDataContainer;

	if ( Array.isArray( container.error_data ) ) {
		return null;
	}

	if ( container.error_data ) {
		for ( const key in container.error_data ) {
			return container.error_data[key];
		}
	}

	return null;
}

interface ErrorMessageContainer {
	errors?: Record<string, string[]>;
}

export function getErrorMessage( data: unknown ): string|null {
	if ( ! ( ( typeof data === 'object' ) && data !== null && 'errors' in data ) ) {
		return null;
	}

	const container = data as ErrorMessageContainer;

	if ( Array.isArray( container.errors ) ) {
		return null;
	}

	if ( container.errors ) {
		for ( const key in container.errors ) {
			for ( const message_key in container.errors[key] ) {
				return container.errors[key][message_key];
			}
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
 * Returns a filter label with an optional count suffix.
 *
 * @param label The base label.
 * @param count The count to append, or undefined/0 to omit.
 * @return The label, optionally with count in parentheses.
 */
export function getFilterLabel( label: string, count?: number ): string {
	return count ? `${ label } (${ count })` : label;
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
