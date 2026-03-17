---
title: PHP Errors
---

# Debugging PHP errors with Query Monitor

The PHP Errors panel appears in Query Monitor whenever PHP code triggers an error, warning, notice, or deprecation during the page load. If you see a bright red or orange highlight in Query Monitor's admin toolbar, this is usually why.

## What information is shown

For each PHP error, the panel shows the error message, error level, the caller, the component responsible (plugin, theme, or WordPress core), and the number of times the error occurred during the page load if one error is triggered multiple times.

Errors are aggregated by their unique call point, so if the same error occurs 50 times during a page load you'll see it once with a count of 50 rather than 50 separate entries.

## Why you should care

Warnings and notices don't just indicate code quality problems. Each error can get logged by your server (depending on its configuration), which adds overhead to every page load. A plugin that generates errors can measurably slow down your site.
