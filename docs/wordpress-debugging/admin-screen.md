---
title: Admin Screen
---

# Debugging the admin screen with Query Monitor

The Admin Screen panel in Query Monitor is available when you're viewing a page in the WordPress admin area. It shows you information about the current admin screen and some useful global variables.

## What information is shown

### Current screen properties

The panel shows the properties of the current `WP_Screen` object as returned by `get_current_screen()`. This includes the screen ID, base, post type, taxonomy, and other properties that are often needed when you're targeting a specific admin screen with your code.

### Global variables

The current values of the admin-related global variables are shown:

* `$pagenow`
* `$typenow`
* `$taxnow`
* `$hook_suffix`

### List table information

If the current admin screen uses a list table (for example the Posts or Users screen), the panel helpfully shows the list table class name and the filter names for columns, sortable columns, and the column action. This saves you from having to dig through WordPress core to find the right filter name.

## When is this useful?

* Finding the correct screen ID to use with `add_meta_box()` or `add_screen_option()`
* Determining the right hook suffix for `admin_print_scripts-{$hook_suffix}` or similar hooks
* Looking up the correct filter name for customising list table columns
