import { defineConfig } from 'vite';
import { v4wp } from '@kucrut/vite-for-wp';
import preact from '@preact/preset-vite';

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
		target: 'chrome112',
		rollupOptions: {
			output: {
				entryFileNames: 'query-monitor.js',
				assetFileNames: '[name][extname]',
			},
		},
	},
} );
