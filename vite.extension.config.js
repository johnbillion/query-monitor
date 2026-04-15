import { defineConfig } from 'vite';
import { resolve } from 'node:path';
import { cpSync } from 'node:fs';
import preact from '@preact/preset-vite';
import browserslistToEsbuild from 'browserslist-to-esbuild';

export default defineConfig( {
	plugins: [
		preact(),
		{
			name: 'copy-extension-static-files',
			writeBundle() {
				const extDir = resolve( __dirname, 'extension' );
				const outDir = resolve( __dirname, 'extension/build' );

				// Copy static extension files to the build output.
				const staticFiles = [
					'manifest.json',
					'devtools.html',
					'devtools.js',
					'background.js',
					'content-script.js',
					'panel.html',
				];

				for ( const file of staticFiles ) {
					cpSync( resolve( extDir, file ), resolve( outDir, file ) );
				}

				// Copy icons directory.
				cpSync( resolve( extDir, 'icons' ), resolve( outDir, 'icons' ), { recursive: true } );

				// Copy the built panel CSS from the main build output.
				cpSync(
					resolve( __dirname, 'assets/build/query-monitor.css' ),
					resolve( outDir, 'panel.css' )
				);
			},
		},
	],
	build: {
		outDir: 'extension/build',
		emptyOutDir: true,
		sourcemap: false,
		target: browserslistToEsbuild(),
		minify: 'terser',
		terserOptions: {
			mangle: {
				reserved: [ '__', '_x', '_n', '_nx', 'sprintf' ],
			},
			compress: {
				conditionals: false,
			},
		},
		rollupOptions: {
			input: {
				panel: resolve( __dirname, 'extension/src/panel.tsx' ),
			},
			output: {
				entryFileNames: '[name].js',
				chunkFileNames: '[name].js',
				assetFileNames: '[name][extname]',
			},
		},
	},
} );
