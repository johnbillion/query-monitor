import { defineConfig } from 'vitepress'

const wpURL = 'https://wordpress.org/plugins/query-monitor/';
const ghURL = 'https://github.com/johnbillion/query-monitor';

export default defineConfig({
	title: 'Query Monitor',
	description: 'The developer tools panel for WordPress',
	rewrites: {
		'help/:page.md': 'help/:page/index.md',
		'wordpress-debugging/:page.md': 'wordpress-debugging/:page/index.md',
		'privacy.md': 'privacy/index.md',
	},
	head: [
		[
			'link',
			{
				rel: 'icon',
				href: '/icon.svg',
			},
		],
	],
	themeConfig: {
		logo: '/icon.svg',

		nav: [
			{
				text: 'Home',
				link: '/',
			},
			{
				text: 'Download',
				link: wpURL,
			},
		],

		sidebar: [
			{
				text: 'WordPress Debugging',
				collapsed: false,
				items: [
					{
						text: 'How to use Query Monitor',
						link: '/wordpress-debugging/how-to-use/',
					},
					{
						text: 'Template parts',
						link: '/wordpress-debugging/template-part-loading/',
					},
					{
						text: 'Blocks',
						link: '/wordpress-debugging/blocks/',
					},
					{
						text: 'Translation files',
						link: '/wordpress-debugging/javascript-translation-files/',
					},
					{
						text: 'REST API requests',
						link: '/wordpress-debugging/rest-api-requests/',
					},
					{
						text: 'Related hooks',
						link: '/wordpress-debugging/related-hooks/',
					},
					{
						text: 'wp_die()',
						link: '/wordpress-debugging/wp-die/',
					},
					{
						text: 'Profiling and logging',
						link: '/wordpress-debugging/profiling-and-logging/',
					},
					{
						text: 'Assertions',
						link: '/wordpress-debugging/assertions/',
					},
				],
			},
			{
				text: 'Help',
				collapsed: true,
				items: [
					{
						text: 'Clickable stack traces',
						link: '/help/clickable-stack-traces-and-function-names/',
					},
					{
						text: 'Silencing errors',
						link: '/help/silencing-errors/',
					},
					{
						text: 'Add-on plugins',
						link: '/help/add-on-plugins/',
					},
					{
						text: 'Configuration constants',
						link: '/help/configuration-constants/',
					},
					{
						text: 'db.php symlink',
						link: '/help/db-php-symlink/',
					},
					{
						text: 'Cache hit rate',
						link: '/help/cache-hit-rate/',
					},
				],
			},
			{
				text: 'GitHub Project',
				link: ghURL,
			},
			{
				text: 'Download on WordPress.org',
				link: wpURL,
			},
			{
				text: 'Privacy statement',
				link: '/privacy/',
			},
		],

		socialLinks: [
			{
				icon: 'github',
				link: ghURL,
				ariaLabel: 'Query Monitor on GitHub',
			},
			{
				icon: 'twitter',
				link: 'https://twitter.com/johnbillion',
				ariaLabel: 'Query Monitor\'s author on Twitter',
			},
		],

		editLink: {
			pattern: 'https://github.com/johnbillion/query-monitor/edit/develop/docs/:path',
			text: 'Edit this page on GitHub',
		},

		search: {
			provider: 'local',
		},
	},
	lastUpdated: true,
	sitemap: {
		hostname: 'https://querymonitor.com',
	},
})
