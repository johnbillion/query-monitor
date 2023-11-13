import * as esbuild from 'esbuild'
import fs from 'node:fs'

const result = await esbuild.build( {
	bundle: true,
	charset: 'utf8',
	entryPoints: [
		{
			in: 'src/index.tsx',
			out: 'build/main',
		},
		{
			in: 'assets/query-monitor.css',
			out: 'assets/query-monitor.min',
		},
	],
	minify: true,
	outdir: '.',
} );

fs.writeFileSync('meta.json', JSON.stringify(result.metafile))
