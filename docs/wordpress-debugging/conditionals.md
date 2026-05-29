---
title: Conditionals
---

# Debugging conditional tags with Query Monitor

WordPress has a large number of [conditional tags](https://developer.wordpress.org/themes/classic-themes/basics/conditional-tags/) such as `is_single()`, `is_home()`, and `is_admin()`. These are used extensively in themes and plugins to determine what type of request is being handled, but it's not always obvious which conditionals are true for a given page.

The Conditionals panel in Query Monitor shows you the result of all of the main WordPress conditional functions for the current page load. True conditionals are highlighted so you can see at a glance what WordPress thinks the current request is.

## When is this useful?

* Figuring out which conditional to use in your theme or plugin for a particular page
* Debugging template logic that isn't behaving as expected because a conditional returns a different value than you assumed
* Quickly confirming the request type without adding `var_dump()` calls to your code
