---
title: Clickable stack traces
---

# Clickable stack traces and function names in Query Monitor

Many panels in Query Monitor display function names or stack traces. Wouldn't it be great if you could click the function name and the file opens up in your text editor or IDE at the correct position? With the clickable file links feature you can, and you'll wonder how you lived without it:

[![Screenshot of clickable function names in Query Monitor](/clickable.png)](/clickable.png)

You just need to open up the Settings panel in Query Monitor (click the cog next to the Close icon) and choose your editor in the "Editor" section. That's it!

[![Screenshot of the Editor setting in Query Monitor](/editor-setting.png)](/editor-setting.png)

If you use one of the following editors you may need to configure its URL scheme handler:

* Sublime Text: [Install the Sublime Text URL handler](https://github.com/inopinatus/sublime_url)
* Netbeans: [Enable clickable stack traces with Netbeans](https://simonwheatley.co.uk/2012/08/clickable-stack-traces-with-netbeans/)

## Remote File Path Mapping

If you're debugging a remote site or using Docker or a virtual machine, you'll need to map the path on the server to its path on your local machine so your editor doesn't try to load a non-existent file. You can do this using a filter on the `qm/output/file_path_map` hook which accepts an array of remote paths and the local path they map to.

Here are examples for various local development environments:

### Altis local server

No need to do anything, the path mapping is handled automatically.

### WordPress core development environment

No need to do anything, the path mapping is handled automatically.

### WordPress VIP on Lando

No need to do anything, the path mapping is handled automatically.

### VVV

```php
add_filter( 'qm/output/file_path_map', function( $map ) {
	$map['/srv/'] = '/path/to/vvv/';
	return $map;
} );
```

### Chassis or another Vagrant-based VM

```php
add_filter( 'qm/output/file_path_map', function( $map ) {
	$map['/vagrant/'] = '/path/to/local/project/';
	return $map;
} );
```

If you've changed the paths configuration in Chassis, you may need to map the `/chassis` directory instead:

```php
add_filter( 'qm/output/file_path_map', function( $map ) {
	$map['/chassis/'] = '/path/to/local/project/';
	return $map;
} );
```

## Xdebug

Xdebug supports an `xdebug.file_link_format` configuration directive. Query Monitor will use this as the default value for the file link format if it's present.

## Customisation

You can use the `qm/output/file_link_format` filter to override the file link format. If this filter is in use then the editor selection control will be hidden.

## That's it!

I hope you enjoy clicking on your newly-clickable file links.
