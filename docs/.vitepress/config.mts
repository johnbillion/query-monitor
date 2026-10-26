import { defineConfig } from 'vitepress'
import { RSSOptions, RssPlugin } from 'vitepress-plugin-rss'
import { wpURL, ghURL, siteURL } from './urls'

const year = new Date().getFullYear();

const RSS: RSSOptions = {
	title: 'Query Monitor',
	baseUrl: siteURL,
	copyright: `Copyright (c) 2009-${year}, John Blackbourn`,
	description: 'The developer tools panel for WordPress and WooCommerce',
	filename: 'feed',
}

export default defineConfig({
	title: 'Query Monitor',
	description: 'The developer tools panel for WordPress and WooCommerce',
	rewrites: {
		'help/:page.md': 'help/:page/index.md',
		'wordpress-debugging/:page.md': 'wordpress-debugging/:page/index.md',
		'about.md': 'about/index.md',
		'accessibility.md': 'accessibility/index.md',
		'privacy.md': 'privacy/index.md',
		'security.md': 'security/index.md',
	},
	head: [
		[
			'link',
			{
				rel: 'icon',
				href: '/icon.svg',
			},
		],
		[
			'link',
			{
				rel: 'alternate',
				type: 'application/rss+xml',
				title: 'Query Monitor',
				href: `${siteURL}/feed`,
			},
		]
	],
	themeConfig: {
		logo: '/icon.svg',

		nav: [
			{
				text: 'Home',
				link: '/',
			},
			{
				text: 'Download WordPress plugin',
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
						text: 'Timeline',
						link: '/wordpress-debugging/timeline/',
					},
					{
						text: 'Database queries',
						link: '/wordpress-debugging/database-queries/',
					},
					{
						text: 'Hooks',
						link: '/wordpress-debugging/hooks/',
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
						text: 'Request',
						link: '/wordpress-debugging/request/',
					},
					{
						text: 'Scripts and styles',
						link: '/wordpress-debugging/scripts-and-styles/',
					},
					{
						text: 'Translation files',
						link: '/wordpress-debugging/javascript-translation-files/',
					},
					{
						text: 'PHP errors',
						link: '/wordpress-debugging/php-errors/',
					},
					{
						text: 'Doing it Wrong',
						link: '/wordpress-debugging/doing-it-wrong/',
					},
					{
						text: 'HTTP headers',
						link: '/wordpress-debugging/headers/',
					},
					{
						text: 'User capabilities',
						link: '/wordpress-debugging/user-capabilities/',
					},
					{
						text: 'Conditionals',
						link: '/wordpress-debugging/conditionals/',
					},
					{
						text: 'Environment',
						link: '/wordpress-debugging/environment/',
					},
					{
						text: 'Transients',
						link: '/wordpress-debugging/transients/',
					},
					{
						text: 'Admin screen',
						link: '/wordpress-debugging/admin-screen/',
					},
					{
						text: 'Multisite',
						link: '/wordpress-debugging/multisite/',
					},
					{
						text: 'REST API requests',
						link: '/wordpress-debugging/rest-api-requests/',
					},
					{
						text: 'Guzzle HTTP requests',
						link: '/wordpress-debugging/guzzle-http-requests/',
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
				collapsed: false,
				items: [
					{
						text: 'Query Monitor 4',
						link: '/help/query-monitor-4/',
					},
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
				text: 'Download WordPress plugin',
				link: wpURL,
			},
			{
				text: 'GitHub Project',
				link: ghURL,
			},
			{
				text: 'About the author',
				link: '/about/',
			},
			{
				text: 'Privacy statement',
				link: '/privacy/',
			},
			{
				text: 'Accessibility statement',
				link: '/accessibility/',
			},
			{
				text: 'Security policy',
				link: '/security/',
			},
		],

		socialLinks: [
			{
				icon: 'github',
				link: ghURL,
				ariaLabel: 'Query Monitor on GitHub',
			},
		],

		search: {
			provider: 'local',
		},

		footer: {
			copyright: `© 2009-${year}, <a href="/about/">John Blackbourn</a>.<br/><br/>WordPress® is a registered trademark of the WordPress Foundation.<br/>WooCommerce® is a registered trademark of WooCommerce, Inc.<br/>Query Monitor is not affiliated with the WordPress Foundation or WooCommerce, Inc.`,
		},
	},
	lastUpdated: false,
	sitemap: {
		hostname: siteURL,
	},
	vite: {
		plugins: [
			RssPlugin(RSS),
		],
	},
})
