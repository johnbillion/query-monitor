---
title: Browser extension help
---

# Browser extension help

See the [browser extension page](/wordpress-debugging/browser-extension/) for general information about installing and using the Query Monitor browser dev tools extension.

## The Query Monitor panel says "Waiting for Query Monitor data"

The browser extension shows this message when it cannot find any Query Monitor data on the page you are inspecting. This can happen for a few reasons:

### The Query Monitor plugin is not active

Make sure the [Query Monitor plugin](https://wordpress.org/plugins/query-monitor/) is installed and active on the WordPress site you are inspecting.

### The Query Monitor plugin is not up to date

The browser extension requires version 4 or higher of the Query Monitor plugin to be active on the site. It does not work with earlier versions.

### The current user does not have permission to view Query Monitor's output

By default, Query Monitor's output is only displayed to logged in users with the `view_query_monitor` capability, which Administrators have by default on a single site install, and Super Admins have on a Multisite install.

See the [How to use Query Monitor](/wordpress-debugging/how-to-use/) page for more information, and the [Configuration constants](/help/configuration-constants/) page for information about settings.

### The page you are inspecting is not a WordPress page

The browser extension only works on pages served by a WordPress site that has Query Monitor active. It will not show any data on other pages.

## Something else is wrong

If you're experiencing another problem with the browser extension, please [open an issue on GitHub](https://github.com/johnbillion/query-monitor/issues).
