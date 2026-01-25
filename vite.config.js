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
			outDir: 'build',
		} ),
	],
	build: {
		target: 'chrome112',
		rollupOptions: {
			output: {
				entryFileNames: 'assets/query-monitor.js',
				assetFileNames: 'assets/[name][extname]',
			},
		},
	},
} );
