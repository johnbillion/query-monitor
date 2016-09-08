[![WordPress Plugin Version](https://img.shields.io/wordpress/plugin/v/query-monitor.svg?style=flat-square)](https://wordpress.org/plugins/query-monitor/)
[![License](https://img.shields.io/badge/license-GPL_v2%2B-blue.svg?style=flat-square)](http://opensource.org/licenses/GPL-2.0)
[![WordPress Tested](https://img.shields.io/wordpress/v/query-monitor.svg?style=flat-square)](https://wordpress.org/plugins/query-monitor/)
[![Build Status](https://img.shields.io/travis/johnbillion/query-monitor/master.svg?style=flat-square)](https://travis-ci.org/johnbillion/query-monitor)

# Query Monitor #

Query Monitor is a debugging plugin for anyone developing with WordPress. It has some advanced features not available in other debugging plugins, including automatic AJAX debugging, REST API debugging, and the ability to narrow down its output by plugin or theme.

Query Monitor adds a toolbar menu showing an overview of the current page. Complete data is shown in the footer once you select a menu item.

Here's an example of Query Monitor's output. This is the panel showing aggregate database queries grouped by component, allowing you to see which plugins are spending the most time on database queries.

![Aggregate Database Queries by Component](https://raw.github.com/johnbillion/query-monitor/master/assets-wp-repo/screenshot-2.png)

---

 * [Features](#features)
    * [Database Queries](#database-queries)
    * [Hooks](#hooks)
    * [Theme](#theme)
    * [PHP Errors](#php-errors)
    * [Request](#request)
    * [Rewrite Rules](#rewrite-rules)
    * [Scripts & Styles](#scripts--styles)
    * [Languages](#languages)
    * [HTTP Requests](#http-requests)
    * [Redirects](#redirects)
    * [AJAX](#ajax)
    * [REST API](#rest-api)
    * [Admin Screen](#admin-screen)
    * [Environment Information](#environment-information)
    * [Everything Else](#everything-else)
 * [Notes](#notes)
    * [Profiling](#a-note-on-profiling)
    * [Implementation](#a-note-on-query-monitors-implementation)
 * [Screenshots](#screenshots)
 * [FAQ](#frequently-asked-questions)
 * [Related Tools](#related-tools)
 * [Contributing](#contributing)
 * [License](#license-gplv2)

---

# Features #

## Database Queries ##

 * Shows all database queries performed on the current request
 * Shows affected rows and time for all queries
 * Shows notifications for slow queries, duplicate queries, and queries with errors
 * Filter queries by query type (`SELECT`, `UPDATE`, `DELETE`, etc)
 * Filter queries by component (WordPress core, Plugin X, Plugin Y, theme)
 * Filter queries by calling function
 * View aggregate query information grouped by component, calling function, and type
 * Super advanced: Supports multiple instances of wpdb (more info in the FAQ)

Filtering queries by component or calling function makes it easy to see which plugins, themes, or functions on your site are making the most (or the slowest) database queries.

## Hooks ##

 * Shows all hooks fired on the current request, along with hooked actions, their priorities, and their components
 * Filter hooks by part of their name
 * Filter actions by component (WordPress core, Plugin X, Plugin Y, theme)

## Theme ##

 * Shows the template filename for the current request
 * Shows the complete template hierarchy for the current request (WordPress 4.7+)
 * Shows all template parts used on the current request
 * Shows the available body classes for the current request
 * Shows the active theme name

## PHP Errors ##

 * PHP errors (warnings, notices, stricts, and deprecated) are presented nicely along with their component and call stack
 * Shows an easily visible warning in the admin toolbar

## Request ##

 * Shows matched rewrite rules and associated query strings
 * Shows query vars for the current request, and highlights custom query vars
 * Shows the queried object details
 * Shows details of the current blog (multisite only) and current site (multi-network only)

## Rewrite Rules ##

 * Shows all matching rewrite rules for a given request

## Scripts & Styles ##

 * Shows all enqueued scripts and styles on the current request, along with their URL and version
 * Shows their dependencies and dependents, and alerts you to any broken dependencies

## Languages ##

 * Shows you language settings and text domains
 * Shows you the MO files for each text domain and which ones were loaded or not

## HTTP Requests ##

 * Shows all HTTP requests performed on the current request (as long as they use WordPress' HTTP API)
 * Shows the response code, call stack, component, timeout, and time taken
 * Highlights erroneous responses, such as failed requests and anything without a `200` response code

## Redirects ##

 * Whenever a redirect occurs, Query Monitor adds an `X-QM-Redirect` HTTP header containing the call stack, so you can use your favourite HTTP inspector or browser developer tools to easily trace where a redirect has come from

## AJAX ##

The response from any jQuery AJAX request on the page will contain various debugging information in its headers. Any errors also get output to the developer console. No hooking required.

Currently this includes PHP errors and some overview information such as memory usage, but this will be built upon in future versions.

## REST API ##

The response from an authenticated WordPress REST API (v2 or later) request will contain various debugging information in its headers, as long as the authenticated user has permission to view Query Monitor's output.

Currently this includes PHP errors and some overview information such as memory usage, but this will be built upon in future versions.

## Admin Screen ##

 * Shows the correct names for custom column filters and actions on all admin screens that have a listing table
 * Shows the state of `get_current_screen()` and a few variables

## Environment Information ##

 * Shows various PHP information such as memory limit and error reporting levels
 * Highlights the fact when any of these are overridden at runtime
 * Shows various MySQL information, including caching and performance related configuration
 * Highlights the fact when any performance related configurations are not optimal
 * Shows various details about WordPress and the web server
 * Shows version numbers for all the things

## Everything Else ##

 * Shows any transients that were set, along with their timeout, component, and call stack
 * Shows all WordPress conditionals on the current request, highlighted nicely
 * Shows an overview at the top, including page generation time and memory limit as absolute values and as % of their respective limits

## Authentication ##

By default, Query Monitor's output is only shown to Administrators on single-site installs, and Super Admins on Multisite installs.

In addition to this, you can set an authentication cookie which allows you to view Query Monitor output when you're not logged in (or if you're logged in as a non-administrator). See the bottom of Query Monitor's output for details.

# Notes #

## A Note on Profiling ##

Query Monitor does not currently contain a profiling mechanism. The main reason for this is that profiling is best done at a lower level using tools such as [XHProf](https://github.com/facebook/xhprof).

However, it is likely that I will add some form of profiling functionality at some point. It'll probably be similar to how Joe Hoyle's [TimeStack](https://github.com/joehoyle/Time-Stack) does it, because that works nicely. Suggestions welcome.

## A Note on Query Monitor's Implementation ##

In order to do a few clever things, Query Monitor symlinks a custom `db.php` into your `WP_CONTENT_DIR` which means it loads very early. This file gets included before the database driver is loaded, meaning this portion of Query Monitor loads before WordPress even engages its brain.

In this file is Query Monitor's extension to the `wpdb` class which:

 * Allows us to log details about **all** database queries (including ones that happen before plugins are loaded)
 * Logs the full stack trace for each query, which allows us to determine the component that's responsible for the query
 * Logs the query result, which allows us to display the affected rows or error message if applicable
 * Logs various PHP configurations before anything has loaded, which allows us to display a message if these get altered at runtime by a plugin or theme

If your `WP_CONTENT_DIR` isn't writable and therefore the symlink for `db.php` can't be put in place, Query Monitor still functions, but this extended functionality won't be available. You can [manually create the db.php symlink](https://github.com/johnbillion/query-monitor/wiki/db.php-Symlink) if you have permission.

# Screenshots #

### Admin Toolbar Menu ###

![Admin Menu](https://raw.github.com/johnbillion/query-monitor/master/assets-wp-repo/screenshot-1.png)

### Database Queries ###

Database listing panel showing all queries, and the controls for filtering by query type, caller, and component

![Database Queries](https://raw.github.com/johnbillion/query-monitor/master/assets-wp-repo/screenshot-4.png)

A slow database query (over 0.05s by default) that has been highlighted in a separate panel

![Slow Database Queries](https://raw.github.com/johnbillion/query-monitor/master/assets-wp-repo/screenshot-3.png)

### Aggregate Database Queries by Component ###

Ordered by most time spent

![Aggregate Database Queries by Component](https://raw.github.com/johnbillion/query-monitor/master/assets-wp-repo/screenshot-2.png)

### Aggregate Database Queries by Calling Function ###

Ordered by most time spent

![Aggregate Database Queries by Calling Function](https://raw.github.com/johnbillion/query-monitor/master/assets-wp-repo/screenshot-7.png)

### Hooks ###

Hook listing panel showing all hooks, and the controls for filtering by name and component

![Hooks](https://raw.github.com/johnbillion/query-monitor/master/assets-wp-repo/screenshot-5.png)

### HTTP Requests ###

Showing an HTTP request with an error

![HTTP](https://raw.github.com/johnbillion/query-monitor/master/assets-wp-repo/screenshot-6.png)

# Frequently Asked Questions #

## Who can see Query Monitor's output? ##

By default, Query Monitor's output is only shown to Administrators on single-site installs, and Super Admins on Multisite installs.

In addition to this, you can set an authentication cookie which allows you to view Query Monitor output when you're not logged in (or if you're logged in as a non-administrator). See the bottom of Query Monitor's output for details.

## Does Query Monitor itself impact the page generation time or memory usage? ##

Short answer: Yes, but only a little.

Long answer: Query Monitor has a small impact on page generation time because it hooks into a few places in WordPress in the same way that other plugins do. The impact is negligible.

On pages that have an especially high number of database queries (in the hundreds), Query Monitor currently uses more memory than I would like it to. This is due to the amount of data that is captured in the stack trace for each query. I have been and will be working to continually reduce this.

## Are there any add-on plugins for Query Monitor? ##

[A list of add-on plugins for Query Monitor can be found here.](https://github.com/johnbillion/query-monitor/wiki/Query-Monitor-Add-on-Plugins)

In addition, Query Monitor transparently supports add-ons for the Debug Bar plugin. If you have any Debug Bar add-ons installed, just deactivate Debug Bar and the add-ons will show up in Query Monitor's menu.

## Where can I suggest a new feature or report a bug? ##

Please use [the issue tracker on Query Monitor's GitHub repo](https://github.com/johnbillion/query-monitor/issues) as it's easier to keep track of issues there, rather than on the wordpress.org support forums.

## Is Query Monitor available on WordPress.com VIP Go? ##

Yep! You just need to add `define( 'WPCOM_VIP_QM_ENABLE', true );` to your `vip-config/vip-config.php` file.

(It's not available on standard WordPress.com VIP though.)

## I'm using multiple instances of `wpdb`. How do I get my additional instances to show up in Query Monitor? ##

You'll need to hook into the `qm/collect/db_objects` filter and add an item to the array with your connection name as the key and the `wpdb` instance as the value. Your `wpdb` instance will then show up as a separate panel, and the query time and query count will show up separately in the admin toolbar menu. Aggregate information (queries by caller and component) will not be separated.

## Do you accept donations? ##

No, I do not accept donations. If you like the plugin, I'd love for you to [leave a review](https://wordpress.org/support/view/plugin-reviews/query-monitor). Tell all your friends about the plugin too!

# Related Tools #

Debugging is rarely done with just one tool. Along with Query Monitor, you should be aware of other plugins and tools which aid in debugging and profiling your website. Here are some examples:

 * [XHProf](https://github.com/facebook/xhprof) for low level profiling of PHP.
 * [Xdebug](https://xdebug.org/) for a host of PHP debugging tools.
 * [P3 Profiler](https://wordpress.org/plugins/p3-profiler/) for performance trend analysis of the plugins in use on your site.
 * [Time Stack](https://github.com/joehoyle/Time-Stack) for WordPress-specific operation profiling.
 * [Laps](https://github.com/Rarst/laps) for lightweight WordPress profiling.
 * [Clockwork](https://github.com/itsgoingd/clockwork) for debugging and profiling PHP applications.
 * [Blackfire](https://blackfire.io/) for PHP performance testing.
 * [New Relic](https://newrelic.com/) for complete software performance analytics.

Query Monitor also has [several add-on plugins](https://github.com/johnbillion/query-monitor/wiki/Query-Monitor-Add-on-Plugins) which extend its functionality, and transparently supports add-ons for the Debug Bar plugin (see the FAQ for more info).

See also my list of [WordPress Developer Plugins](https://johnblackbourn.com/wordpress-developer-plugins).

# Contributing #

Code contributions are very welcome, as are bug reports in the form of GitHub issues. Development happens in the `develop` branch, and any pull requests should be made to that branch please.

# License: GPLv2 #

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.
