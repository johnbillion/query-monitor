import { defineConfig } from 'vite';
import { dirname } from 'node:path';
import { fileURLToPath } from 'node:url';
import { mkdirSync, rmSync, writeFileSync } from 'node:fs';
import preact from '@preact/preset-vite';
import browserslistToEsbuild from 'browserslist-to-esbuild';

const projectDir = dirname( fileURLToPath( import.meta.url ) );

function viteDevServerManifest() {
	let manifestFile = '';
	let origin = '';

	return {
		name: 'vite-dev-server-manifest',
		apply: 'serve',
		configResolved( config ) {
			manifestFile = `${ projectDir }/${ config.build.outDir }/vite-dev-server.json`;
			origin = config.server.origin;
		},
		buildStart() {
			mkdirSync( dirname( manifestFile ), { recursive: true } );
			writeFileSync( manifestFile, JSON.stringify( { origin } ), 'utf8' );
		},
		buildEnd() {
			rmSync( manifestFile, { force: true } );
		},
	};
}

export default defineConfig( {
	base: './',
	plugins: [
		preact(),
		viteDevServerManifest(),
		{
			name: 'clean-build-dir',
			configureServer() {
				rmSync( 'assets/build', { recursive: true, force: true } );
			},
		},
		{
			name: 'shadow-dom-css-hmr',
			handleHotUpdate( { file, server } ) {
				if ( file.endsWith( '/assets/query-monitor.css' ) ) {
					server.ws.send( {
						type: 'custom',
						event: 'qm:css-update',
					} );
				}
			},
		},
		{
			name: 'no-manifest',
			config() {
				return {
					build: {
						manifest: false,
					},
				};
			},
		},
	],
	server: {
		host: 'localhost',
		port: 5173,
		strictPort: true,
		origin: 'http://localhost:5173',
		cors: true,
	},
	css: {
		devSourcemap: true,
	},
	build: {
		outDir: 'assets/build',
		emptyOutDir: true,
		modulePreload: false,
		sourcemap: false,
		target: browserslistToEsbuild(),
		// Terser is used instead of esbuild for minification in order to preserve
		// wp i18n function names so translate.wordpress.org can extract them.
		minify: 'terser',
		terserOptions: {
			mangle: {
				// Prevent i18n function names from being shortened.
				reserved: [ '__', '_x', '_n', '_nx', 'sprintf' ],
			},
			compress: {
				// Prevent ternary expressions from being moved inside i18n
				// function arguments, which makes them unextractable.
				conditionals: false,
			},
			format: {
				// Preserve translators comments for wp i18n string extraction.
				comments: /translators:/i,
				preamble: '/* This is the built version of the Preact app for Query Monitor. The source code is available here: https://github.com/johnbillion/query-monitor */',
			},
		},
		rolldownOptions: {
			input: {
				main: 'src/index.tsx',
				'query-monitor': 'assets/query-monitor.css',
				'toolbar': 'assets/toolbar.css',
			},
			output: {
				entryFileNames: 'query-monitor.js',
				assetFileNames: '[name][extname]',
			},
		},
	},
} );
