---
title: db.php symlink
---

# db.php symlink

In addition to the main plugin files, Query Monitor includes a file named `db.php` which gets symlinked into your `wp-content` directory when the plugin is activated. This special file is a WordPress dropin plugin and it allows Query Monitor to provide extended functionality such as the result count, full stack trace, and error detection for all database queries.

Occasionally the PHP process won't be able to put this symlink in place. Some common causes are:

* The file permissions of the `wp-content` directory means it isn't writable by PHP
* Another `wp-content/db.php` file is already in place
* Files for the site were copied from elsewhere (eg. during a migration from another hosting provider) and the existing symlink no longer points to a valid location

## When Query Monitor is unable to symlink its db.php file into place

**Query Monitor will still work fine in this situation** but you won't see extended information that makes Query Monitor much more useful.

In this situation you can create the symlink manually using one of the methods below.

### Relax the file permissions

Relax the file permissions on the `wp-content` directory so it's writable by the PHP process, then de-activate and re-activate Query Monitor and it'll attempt to create the symlink again.

### Use WP-CLI

Query Monitor includes a [WP-CLI](https://wp-cli.org/) command for putting the symlink into place:

```
wp qm enable
```

### Use the command line

If you don't have access to WP-CLI you can run a command to create the symlink manually:

macOS / Linux:

```
ln -s /path/to/wordpress/wp-content/plugins/query-monitor/wp-content/db.php /path/to/wordpress/wp-content/db.php
```

Windows (requires administrator privileges):

```
mklink C:\path\to\wordpress\wp-content\db.php C:\path\to\wordpress\wp-content\plugins\query-monitor\wp-content\db.php
```

### Via your hosting control panel

If you're unable to do any of the above you should be able to use your web hosting control panel (such as Plesk or cPanel) to create the symlink. Contact your web host if you're unsure.

## When an existing db.php file is already in place

The `db.php` file will sometimes conflict with another plugin that also uses a `db.php` file. Such plugins include:

* W3 Total Cache
* LudicrousDB
* HyperDB

**There is nothing that can be done about this**. This a WordPress core limitation due to the fact that the dropin plugin file must be called `db.php` and placed in the `wp-content` directory, and only one can exist there.
