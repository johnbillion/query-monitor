import { defineConfig } from 'vite';
import { v4wp } from '@kucrut/vite-for-wp';
import preact from '@preact/preset-vite';
import browserslistToEsbuild from 'browserslist-to-esbuild';

export default defineConfig( {
	plugins: [
		preact(),
		v4wp( {
			input: {
				main: 'src/index.tsx',
				'query-monitor': 'assets/query-monitor.css',
			},
			outDir: 'assets/build',
		} ),
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
	build: {
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
			},
		},
		rollupOptions: {
			output: {
				entryFileNames: 'query-monitor.js',
				assetFileNames: '[name][extname]',
			},
		},
	},
} );
