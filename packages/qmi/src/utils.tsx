import * as React from 'react';

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
