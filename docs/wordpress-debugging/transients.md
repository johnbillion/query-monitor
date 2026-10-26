---
title: Transients
---

# Debugging transients with Query Monitor

The Transients panel in Query Monitor shows you all of the transients that were written to during the current page load.

## What information is shown

For each transient that was updated, the panel shows the transient name, its expiry, its approximate size, and which function, plugin, theme, or part of WordPress core set the transient.

## When is this useful?

Transients should typically be set infrequently, and preferably not on the front end of the site. If you see transients being set on every page load, that's a sign that something is wrong. Either the transient is being set unnecessarily, or it's expiring immediately and being regenerated every time.

This panel is also helpful for understanding which plugins are storing data in transients and how much data they're storing.
