---
title: Configuration constants
---

# Configuration constants

The following PHP constants can be defined in your wp-config.php file in order to control the behaviour of Query Monitor:

## `QM_DB_EXPENSIVE`

If an individual database query takes longer than this time to execute, it's considered "slow" and triggers a warning.

Default `0.05`

## `QM_DISABLED`

Disable Query Monitor entirely.

Default `false`

## `QM_DISABLE_ERROR_HANDLER`

Disable the handling of PHP errors.

Default `false`

## `QM_ENABLE_CAPS_PANEL`

Enable the Capability Checks panel.

Default `false`

## `QM_HIDE_CORE_ACTIONS`

Hide WordPress core on the Hooks & Actions panel.

Default `false`

## `QM_HIDE_SELF`

Hide Query Monitor itself from various panels. Set to `false` if you want to see how Query Monitor hooks into WordPress.

Default `true`

## `QM_SHOW_ALL_HOOKS`

In the Hooks & Actions panel, show every hook that has an action or filter attached (instead of every action hook that fired during the request).

Default `false`

## `QM_DB_SYMLINK`

Allow the wp-content/db.php file symlink to be put into place during activation. Set to `false` to prevent the symlink creation.

Default `true`
