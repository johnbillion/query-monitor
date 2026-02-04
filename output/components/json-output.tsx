interface Props {
	data: unknown;
}

export const JsonOutput = ( { data }: Props ) => (
	<pre className="qm-pre-wrap">
		<code>
			{ JSON.stringify( data, null, 2 ) }
		</code>
	</pre>
);
