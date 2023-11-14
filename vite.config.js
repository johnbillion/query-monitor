import { defineConfig } from 'vite';
import { v4wp } from '@kucrut/vite-for-wp';
import react from '@vitejs/plugin-react';

export default defineConfig( {
	plugins: [
		react(),
		v4wp( {
			input: {
				main: 'src/index.tsx',
				css: 'assets/query-monitor.css',
			},
			outDir: 'build',
		} ),
	],
	build: {
		target: 'chrome112',
	},
} );
