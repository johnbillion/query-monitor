[![WordPress Plugin Version](https://img.shields.io/wordpress/plugin/v/query-monitor.svg?style=flat-square)](https://wordpress.org/plugins/query-monitor/)
[![License](https://img.shields.io/badge/license-GPL_v2%2B-blue.svg?style=flat-square)](http://opensource.org/licenses/GPL-2.0)
[![Documentation](https://img.shields.io/badge/docs-stable-blue.svg?style=flat-square)](https://docs.querymonitor.com/en/stable/)
[![WordPress Tested](https://img.shields.io/wordpress/v/query-monitor.svg?style=flat-square)](https://wordpress.org/plugins/query-monitor/)
[![Build Status](https://img.shields.io/travis/johnbillion/query-monitor.svg?style=flat-square)](https://travis-ci.org/johnbillion/query-monitor)

# Query Monitor #

Query Monitor is a debugging plugin for anyone developing with WordPress. It has some advanced features not available in other debugging plugins, including automatic AJAX debugging and the ability to narrow down things by plugin or theme.

Query Monitor adds a toolbar menu showing an overview of the current page. Complete data is shown in the footer once you select a menu item.

Here's an example of Query Monitor's output. This is the panel showing aggregate database queries grouped by component, allowing you to see which plugins are spending the most time on database queries.

![Aggregate Database Queries by Component](https://raw.github.com/johnbillion/QueryMonitor/master/assets-wp-repo/screenshot-2.png)

---

 * [Features](#features)
    * [Database Queries](#database-queries)
    * [Hooks](#hooks)
    * [Theme](#theme)
    * [PHP Errors](#php-errors)
    * [Request](#request)
    * [Scripts & Styles](#scripts--styles)
    * [HTTP Requests](#http-requests)
    * [Redirects](#redirects)
    * [AJAX](#ajax)
    * [Admin Screen](#admin-screen)
    * [Environment Information](#environment-information)
    * [Everything Else](#everything-else)
 * [Notes](#notes)
    * [Profiling](#a-note-on-profiling)
    * [Implementation](#a-note-on-query-monitors-implementation)
 * [Screenshots](#screenshots)
 * [Contributing](#contributing)
 * [License](#license-gplv2)

---

# Features #

## Database Queries ##

 * Shows all database queries performed on the current page
 * Shows **affected rows** and time for all queries
 * Show notifications for **slow queries** and **queries with errors**
 * Filter queries by **query type** (`SELECT`, `UPDATE`, `DELETE`, etc)
 * Filter queries by **component** (WordPress core, Plugin X, Plugin Y, theme)
 * Filter queries by **calling function**
 * View **aggregate query information** grouped by component, calling function, and type
 * Super advanced: Supports **multiple instances of wpdb** on one page

Filtering queries by component or calling function makes it easy to see which plugins, themes, or functions on your site are making the most (or the slowest) database queries.

## Hooks ##

 * Shows all hooks fired on the current page, along with hooked actions, their priorities, and their components
 * Filter hooks by **part of their name**
 * Filter actions by **component** (WordPress core, Plugin X, Plugin Y, theme)

## Theme ##

 * Shows the **template filename** for the current page
 * Shows the available **body classes** for the current page
 * Shows the active theme name

## PHP Errors ##

 * PHP errors (warnings, notices, stricts and deprecated) are presented nicely along with their component and call stack
 * Shows an easily visible warning in the admin toolbar

## Request ##

 * Shows **matched rewrite rules** and associated query strings
 * Shows **query vars** for the current request, and highlights **custom query vars**
 * Shows the **queried object** details (collapsed by default)
 * Shows details of the **current blog** (multisite only) and **current site** (multi-network only)

## Scripts & Styles ##

 * Shows all **enqueued scripts and styles** on the current page, along with their URL and version
 * Shows their **dependencies and dependents**, and alerts you to any **broken dependencies**

## HTTP Requests ##

 * Shows all HTTP requests performed on the current page (as long as they use WordPress' HTTP API)
 * Shows the response code, call stack, transport, timeout, and time taken
 * Highlights **erroneous responses**, such as failed requests and anything without a `200` response code

## Redirects ##

 * Whenever a redirect occurs, Query Monitor adds an `X-QM-Redirect` HTTP header containing the call stack, so you can use your favourite HTTP inspector to easily trace where a redirect has come from

## AJAX ##

The response from any jQuery AJAX request on the page will contain various debugging information in its header that gets output to the developer console. **No hooking required**.

AJAX debugging is in its early stages. Currently it only includes PHP errors (warnings, notices and stricts), but this will be built upon in future versions.

## Admin Screen ##

Hands up who can remember the correct names for the filters and actions for custom admin screen columns?

 * Shows the correct names for **custom column filters and actions** on all admin screens that have a listing table
 * Shows the state of `get_current_screen()` and a few variables

## Environment Information ##

 * Shows **various PHP information** such as memory limit and error reporting levels
 * Highlights the fact when any of these are overridden at runtime
 * Shows **various MySQL information**, including caching and performance related configuration
 * Highlights the fact when any performance related configurations are not optimal
 * Shows various details about **WordPress** and the **web server**
 * Shows version numbers for all the things

## Everything Else ##

 * Shows any **transients that were set**, along with their timeout, component, and call stack
 * Shows all **WordPress conditionals** on the current page, highlighted nicely
 * Shows an overview at the top, including page generation time and memory limit as absolute values and as % of their respective limits
 * Shows all *scripts and styles* which were enqueued on the current page, along with their URL, dependencies, dependents, and version number

## Authentication ##

By default, Query Monitor's output is only shown to Administrators on single-site installs, and Super Admins on Multisite installs.

In addition to this, you can set an authentication cookie which allows you to view Query Monitor output when you're not logged in (or if you're logged in as a non-administrator). See the bottom of Query Monitor's output for details.

# Notes #

## A Note on Profiling ##

Query Monitor does not currently contain a profiling mechanism. The main reason for this is that profiling is best done at a lower level using tools such as [XHProf](https://github.com/facebook/xhprof).

However, it is likely that I will add some form of profiling functionality at some point. It'll probably be similar to how Joe Hoyle's [TimeStack](https://github.com/joehoyle/Time-Stack) does it, because that works nicely. Suggestions welcome.

## A Note on Query Monitor's Implementation ##

In order to do a few clever things, Query Monitor loads earlier than you ever thought humanly possible (almost). It does this by symlinking a custom `db.php` into your `WP_CONTENT_DIR`. This file gets included before the database driver is loaded, meaning this portion of Query Monitor loads before WordPress even engages its brain.

In this file is Query Monitor's extension to the `wpdb` class which:

 * Allows us to log **all** database queries (including ones that happen before plugins are loaded)
 * Logs the full stack trace for each query, which allows us to determine the component that's responsible for the query
 * Logs the query result, which allows us to display the affected rows or error message if applicable
 * Logs various PHP configurations before anything has loaded, which allows us to display a message if these get altered at runtime by a plugin or theme

If your `WP_CONTENT_DIR` isn't writable and therefore the symlink for `db.php` can't be put in place, Query Monitor still functions, but this extended functionality won't be available. You can [manually create the db.php symlink](https://github.com/johnbillion/query-monitor/wiki/db.php-Symlink) if you have permission.

# Screenshots #

### Admin Toolbar Menu ###

![Admin Menu](https://raw.github.com/johnbillion/QueryMonitor/master/assets-wp-repo/screenshot-1.png)

### Database Queries ###

Database listing panel showing all queries, and the controls for filtering by query type, caller, and component

![Database Queries](https://raw.github.com/johnbillion/QueryMonitor/master/assets-wp-repo/screenshot-4.png)

A slow database query (over 0.05s by default) that has been highlighted in a separate panel

![Slow Database Queries](https://raw.github.com/johnbillion/QueryMonitor/master/assets-wp-repo/screenshot-3.png)

### Aggregate Database Queries by Component ###

Ordered by most time spent

![Aggregate Database Queries by Component](https://raw.github.com/johnbillion/QueryMonitor/master/assets-wp-repo/screenshot-2.png)

### Aggregate Database Queries by Calling Function ###

Ordered by most time spent

![Aggregate Database Queries by Calling Function](https://raw.github.com/johnbillion/QueryMonitor/master/assets-wp-repo/screenshot-7.png)

### Hooks ###

Hook listing panel showing all hooks, and the controls for filtering by name and component

![Hooks](https://raw.github.com/johnbillion/QueryMonitor/master/assets-wp-repo/screenshot-5.png)

### HTTP Requests ###

Showing an HTTP request with an error

![HTTP](https://raw.github.com/johnbillion/QueryMonitor/master/assets-wp-repo/screenshot-6.png)

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
