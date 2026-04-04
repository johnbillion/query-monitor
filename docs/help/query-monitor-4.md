---
nav_exclude: true
publish: false
---

# Query Monitor 4

Query Monitor is the developer tools panel for WordPress and WooCommerce.

Version 4 of Query Monitor adds a few new features and switches from rendering its panels server-side in PHP to efficiently rendering them client-side in Preact. This new approach provides several benefits:

- Performance is greatly increased, particularly on sites where a large number of queries are performed, a large number of PHP errors are triggered, or a large amount of data is collected in one of the other panels.
- Further future enhancements are facilitated, such as displaying client-side metrics, lazy-loading data, showing data from different requests, and more remixing of data into different views.
- The raw data collected by Query Monitor has been reduced in total size and peak memory usage, and is now exposed to the page as a JSON blob. Take a look at the `QueryMonitorData` object in your browser console to play around with it.

## New timeline view

A new Timeline panel provides a visual overview of the events that occur during a page load. Database queries, HTTP API requests, PHP errors, timings, logs, and notable actions are all plotted on a horizontal timeline so you can see when they occurred and how long they took relative to the total page load time. You can filter by component and toggle individual categories on and off.

![The Timeline panel in Query Monitor](/timeline.png)

## Zero dependencies

Query Monitor now ships with zero external dependencies. No more jQuery, no reliance on `wp` globals, and no enqueuing of assets, just a self-contained 100KB Preact-powered bundle.

## Isn't Query Monitor redundant now we have AI?

Query Monitor is more useful than ever in our new world of AI-driven development. Query Monitor gives agentic developers the observability that they need to produce high performing WordPress websites and WooCommerce stores, and because Query Monitor has been around a while, all the AI tools know how to use it.

Query Monitor itself doesn't contain any AI-powered features yet, but perhaps it will in the future. I'll only ever add features that solve real problems for humans and agents.

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

If Query Monitor saves you time and energy debugging your WordPress website or WooCommerce store, [please leave a review on the WordPress.org plugin directory](https://wordpress.org/support/plugin/query-monitor/reviews/#new-post). I always appreciate receiving reviews.
