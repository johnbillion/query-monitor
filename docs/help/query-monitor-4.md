# Query Monitor 4

Query Monitor is the developer tools panel for WordPress and WooCommerce.

Version 4 of Query Monitor switches from rendering its panels server side in PHP to efficiently rendering them client side in Preact. This provides several benefits:

- Rendering performance is greatly increased, particularly on sites where a large number of queries are performed, a large number of PHP errors are triggered, or a large amount of data is collected in one of the other panels. Your browser no longer has to process all the HTML that made up the tables in the Query Monitor panels, and it only renders the currently active panel at any one time.
- Future enhancements are facilitated, such as displaying client-side metrics, lazy-loading debugging data, showing data from different requests, and remixing data into different views. A timeline view is in the works.
- The raw data collected by Query Monitor is exposed to the page in a JSON blob. If you want to play around with it, take a look at the `QueryMonitorData` object in your browser console. You might be surprised at the size of the data, but don't worry, it's still much more performant than generating a huge number of HTML table rows server side and rendering them in the browser.

Very little has changed visually in Query Monitor 4 beyond a lightly refreshed appearance.

## Installation

Install and activate Query Monitor as you would any other plugin for WordPress.

<VPButton href="https://wordpress.org/plugins/query-monitor/" text="Download Query Monitor from WordPress.org" theme="brand" />

Alternatively, [download from GitHub](https://github.com/johnbillion/query-monitor/releases) or [install via Composer](https://packagist.org/packages/johnbillion/query-monitor).

## Backwards compatibility

If you're using a plugin that adds its own panels to Query Monitor, these panels will continue to work. You shouldn't notice any difference.

## Custom panels

It's not yet possible for a third-party plugin to register its own client-side rendered panel in Query Monitor. This is a future enhancement, and server-side rendered panels will remain supported for as long as feasible.

## Thanks

The time that I spend maintaining this plugin and others is in part sponsored by:

<p align="center">
	<a href="https://automattic.com">
		<img src="https://cdn.jsdelivr.net/gh/johnbillion/johnbillion@latest/assets/sponsors/automattic.svg" alt="Automattic" width="50%">
	</a>
</p>

<p align="center">
	<a href="https://servmask.com">
		<img src="https://cdn.jsdelivr.net/gh/johnbillion/johnbillion@latest/assets/sponsors/servmask.svg" alt="ServMask" width="25%">
	</a>
</p>

<p align="center">
	<a href="https://wp-staging.com">
		<img src="https://cdn.jsdelivr.net/gh/johnbillion/johnbillion@latest/assets/sponsors/wp-staging.png" alt="WP Staging" width="25%">
	</a>
</p>

Plus all my kind sponsors on GitHub:

<p align="center">
	<a href="https://github.com/sponsors/johnbillion">
		<img src="https://cdn.jsdelivr.net/gh/johnbillion/johnbillion@latest/sponsors.svg" alt="Sponsors" />
	</a>
</p>

<a href="https://github.com/sponsors/johnbillion">Click here to find out about supporting my open source tools and plugins</a>.

Without the support of my sponsors, it's unlikely that I would be able to continue dedicating the time needed to maintain Query Monitor and my other plugins.

## Bugs

Have you found a bug in Query Monitor 4? [You can report bugs via GitHub](https://github.com/johnbillion/query-monitor/issues) or [the WordPress.org support forums](https://wordpress.org/support/plugin/query-monitor/).

## Reviews

If Query Monitor saves you time and energy debugging your WordPress or WooCommerce site, [please leave a review on the WordPress.org plugin directory](https://wordpress.org/support/plugin/query-monitor/reviews/#new-post). I always appreciate receiving reviews.
