---
title: Scripts and Styles
---

# Debugging enqueued scripts and styles with Query Monitor

The Scripts and Styles panels in Query Monitor show you all of the JavaScript and CSS files that have been enqueued on the current page via the WordPress dependency system.

## Why is this useful?

If your site loads a large number of JavaScript or CSS files it won't necessarily affect the page generation time on your server, but it will slow down the page load time for your visitors. These panels help you see exactly which assets are being loaded and which plugins or themes are responsible.

## What information is shown

For each enqueued asset, the panels show the asset name, URL, dependencies, dependents, and which plugin, theme, or part of WordPress core registered the asset.

This panel will also highlight assets with problems such as missing dependencies, which are otherwise hard to debug as WordPress silently discards these assets.

## Identifying problems

A good use for this panel is to identify plugins that are loading their assets on every page of your site when they should only be loaded where needed.
