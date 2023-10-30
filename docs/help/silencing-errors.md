---
title: Silencing errors
parent: Help
---

# Silencing errors from certain plugins and themes in Query Monitor

When a PHP warning or notice occurs during the page load, Query Monitor displays a coloured notification in the admin toolbar that links to the PHP Errors panel. This is great for debugging but can be an annoyance if a third party plugin or theme continually triggers errors that aren't your responsibility to fix.

[![Screenshot of a PHP error in Query Monitor](/assets/php-errors.png)](/assets/php-errors.png)

Query Monitor allows you to silence errors from specified plugins or themes. Errors will still be shown in the PHP Errors panel but they won't trigger a coloured notification in the admin toolbar.

Here's how you hide PHP notices from a plugin named "foo":

```php
add_filter( 'qm/collect/php_error_levels', function( array $levels ) {
	$levels['plugin']['foo'] = ( E_ALL & ~E_NOTICE );
	return $levels;
} );
```

This code hooks into the `qm/collect/php_error_levels` filter and specifies the error levels which should get reported by Query Monitor for the specified plugin. The error levels are specified using the same bitmask syntax used for [PHP's `error_reporting()` function](https://secure.php.net/manual/en/function.error-reporting.php), and in this example is telling Query Monitor to report all errors except notices.

The name to use for the plugin array's index is what Query Monitor shows as the name for the "Component", for example "Plugin: foo" ends up as "foo".

You could also tell Query Monitor to only report warnings from your child theme, and completely silence errors from its parent theme (probably not a good idea):

```php
add_filter( 'qm/collect/php_error_levels', function( array $levels ) {
	$levels['theme']['stylesheet'] = ( E_WARNING & E_USER_WARNING );
	$levels['theme']['template']   = ( 0 );
	return $levels;
} );
```

Any plugin or theme which doesn't have an error level specified via this filter is assumed to have the default level of `E_ALL`, which shows all errors.

To silence deprecated errors from WordPress core:

```php
add_filter( 'qm/collect/php_error_levels', function( $levels ) {
	$levels['core']['core'] = ( E_ALL & ~E_DEPRECATED );
	return $levels;
} );
```

Finally, if you have special PHP error handling in place on your site and you don't want Query Monitor to handle errors at all, you can disable the error handling functionality completely:

```php
define( 'QM_DISABLE_ERROR_HANDLER', true );
```
