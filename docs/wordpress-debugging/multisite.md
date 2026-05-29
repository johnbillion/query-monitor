---
title: Multisite
---

# Debugging multisite with Query Monitor

The Multisite panel in Query Monitor tracks the blog switching that occurs during a page load on a WordPress multisite installation. Every call to `switch_to_blog()` and `restore_current_blog()` is logged so you can see exactly when and why the current site context changes.

## What information is shown

For each blog switch, the panel shows calls to `switch_to_blog()` and `restore_current_blog()`, which ID was switched from and to, and which function, plugin, theme, or part of WordPress core is responsible.

## When is this useful?

Blog switching can be a source of subtle bugs on multisite installations. If a plugin switches to another blog and doesn't properly restore the original blog, it can cause data to be read from or written to the wrong site. This panel helps you spot those problems.

It's also useful for performance debugging. Each `switch_to_blog()` call flushes parts of the object cache and reinitialises various parts of WordPress for the target site, which has a performance cost, although notably this cost is significantly less in newer versions of WordPress than it was historically. If you see a large number of switches on a single page load, there may be an opportunity to optimise.
