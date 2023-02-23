# Query Monitor
Contributors: johnbillion
Tags: debug, debug-bar, development, performance, query monitor, rest-api
Requires at least: 4.9
Tested up to: 6.1
Stable tag: 3.11.2
License: GPLv2 or later
Requires PHP: 7.2
Donate link: https://github.com/sponsors/johnbillion

Query Monitor is the developer tools panel for WordPress.

## Description

Query Monitor is the developer tools panel for WordPress. It enables debugging of database queries, PHP errors, hooks and actions, block editor blocks, enqueued scripts and stylesheets, HTTP API calls, and more.

It includes some advanced features such as debugging of Ajax calls, REST API calls, and user capability checks. It includes the ability to narrow down much of its output by plugin or theme, allowing you to quickly determine poorly performing plugins, themes, or functions.

Query Monitor focuses heavily on presenting its information in a useful manner, for example by showing aggregate database queries grouped by the plugins, themes, or functions that are responsible for them. It adds an admin toolbar menu showing an overview of the current page, with complete debugging information shown in panels once you select a menu item.

For complete information, please see [the Query Monitor website](https://querymonitor.com/).

Here's an overview of what's shown for each page load:

* Database queries, including notifications for slow, duplicate, or erroneous queries. Allows filtering by query type (`SELECT`, `UPDATE`, `DELETE`, etc), responsible component (plugin, theme, WordPress core), and calling function, and provides separate aggregate views for each.
* The template filename, the complete template hierarchy, and names of all template parts that were loaded or not loaded.
* PHP errors presented nicely along with their responsible component and call stack, and a visible warning in the admin toolbar.
* Blocks and associated properties in post content when using WordPress 5.0+ or the Gutenberg plugin.
* Matched rewrite rules, associated query strings, and query vars.
* Enqueued scripts and stylesheets, along with their dependencies, dependents, and alerts for broken dependencies.
* Language settings and loaded translation files (MO files) for each text domain.
* HTTP API requests, with response code, responsible component, and time taken, with alerts for failed or erroneous requests.
* User capability checks, along with the result and any parameters passed to the capability check.
* Environment information, including detailed information about PHP, the database, WordPress, and the web server.
* The values of all WordPress conditional functions such as `is_single()`, `is_home()`, etc.
* Transients that were updated.

In addition:

* Whenever a redirect occurs, Query Monitor adds an HTTP header containing the call stack, so you can use your favourite HTTP inspector or browser developer tools to trace what triggered the redirect.
* The response from any jQuery-initiated Ajax request on the page will contain various debugging information in its headers. PHP errors also get output to the browser's developer console.
* The response from an authenticated WordPress REST API request will contain an overview of performance information and PHP errors in its headers, as long as the authenticated user has permission to view Query Monitor's output. An [an enveloped REST API request](https://developer.wordpress.org/rest-api/using-the-rest-api/global-parameters/#_envelope) will include even more debugging information in the `qm` property of the response.

By default, Query Monitor's output is only shown to Administrators on single-site installations, and Super Admins on Multisite installations.

In addition to this, you can set an authentication cookie which allows you to view Query Monitor output when you're not logged in (or if you're logged in as a non-Administrator). See the Settings panel for details.

### Other Plugins

I maintain several other plugins for developers. Check them out:

* [User Switching](https://wordpress.org/plugins/user-switching/) provides instant switching between user accounts in WordPress.
* [WP Crontrol](https://wordpress.org/plugins/wp-crontrol/) lets you view and control what's happening in the WP-Cron system

### Privacy Statement

Query Monitor is private by default and always will be. It does not persistently store any of the data that it collects. It does not send data to any third party, nor does it include any third party resources.

[Query Monitor's full privacy statement can be found here](https://github.com/johnbillion/query-monitor/wiki/Privacy-Statement).

## Screenshots

1. Admin Toolbar Menu
2. Aggregate Database Queries by Component
3. Capability Checks
4. Database Queries
5. Hooks and Actions
6. HTTP API Requests
7. Aggregate Database Queries by Calling Function

## Frequently Asked Questions

### Does this plugin work with PHP 8?

Yes, it's actively tested and working up to PHP 8.2.

### Who can access Query Monitor's output?

By default, Query Monitor's output is only shown to Administrators on single-site installations, and Super Admins on Multisite installations.

In addition to this, you can set an authentication cookie which allows you to view Query Monitor output when you're not logged in, or when you're logged in as a user who cannot usually see Query Monitor's output. See the Settings panel for details.

### Does Query Monitor itself impact the page generation time or memory usage?

Short answer: Yes, but only a little.

Long answer: Query Monitor has a small impact on page generation time because it hooks into WordPress in the same way that other plugins do. The impact is low; typically between 10ms and 100ms depending on the complexity of your site.

Query Monitor's memory usage typically accounts for around 10% of the total memory used to generate the page.

### Can I prevent Query Monitor from collecting data during long-running requests?

Yes, if anything calls `do_action( 'qm/cease' )` then Query Monitor will cease operating for the remainder of the page generation. It detaches itself from further data collection, discards any data it's collected so far, and skips the output of its information.

This is useful for long-running operations that perform a very high number of database queries, consume a lot of memory, or otherwise are of no concern to Query Monitor, for example:

* Backing up or restoring your site
* Exporting a large amount of data
* Running security scans

### Are there any add-on plugins for Query Monitor?

[A list of add-on plugins for Query Monitor can be found here.](https://github.com/johnbillion/query-monitor/wiki/Query-Monitor-Add-on-Plugins)

In addition, Query Monitor transparently supports add-ons for the Debug Bar plugin. If you have any Debug Bar add-ons installed, deactivate Debug Bar and the add-ons will show up in Query Monitor's menu.

### Where can I suggest a new feature or report a bug?

Please use [the issue tracker on Query Monitor's GitHub repo](https://github.com/johnbillion/query-monitor/issues) as it's easier to keep track of issues there, rather than on the wordpress.org support forums.

### Is Query Monitor available on Altis?

Yes, the [Altis Developer Tools](https://www.altis-dxp.com/resources/developer-docs/dev-tools/) are built on top of Query Monitor.

### Is Query Monitor available on WordPress.com VIP Go?

Yes, it's included as part of the VIP Go platform. However, a user needs to be granted the `view_query_monitor` capability to see Query Monitor even if they're an administrator.

Please note that information about database queries and the environment is somewhat restricted on VIP. This is a platform restriction and not a Query Monitor issue.

### I'm using multiple instances of `wpdb`. How do I get my additional instances to show up in Query Monitor?

You'll need to hook into the `qm/collect/db_objects` filter and add an item to the array containing your `wpdb` instance. For example:

    add_filter( 'qm/collect/db_objects', function( $objects ) {
        $objects['my_db'] = $GLOBALS['my_db'];
        return $objects;
    } );

Your `wpdb` instance will then show up as a separate panel, and the query time and query count will show up separately in the admin toolbar menu. Aggregate information (queries by caller and component) will not be separated.

### Can I click on stack traces to open the file in my editor?

Yes. You can enable this on the Settings panel.

### Do you accept donations?

[I am accepting sponsorships via the GitHub Sponsors program](https://johnblackbourn.com/donations/) and any support you can give will help me maintain this plugin and keep it free for everyone.

In addition, if you like the plugin then I'd love for you to [leave a review](https://wordpress.org/support/view/plugin-reviews/query-monitor). Tell all your friends about it too!

<!-- changelog -->
