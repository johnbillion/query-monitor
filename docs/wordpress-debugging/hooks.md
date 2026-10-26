---
title: Hooks
---

# Debugging WordPress hooks, actions, and filters with Query Monitor

The Hooks panel in Query Monitor shows you every action and filter that fired during the page load, along with all the callbacks attached to each one.

## What information is shown

For each hook, the panel lists the name of the action or filter, the function, method, or closure attached at each priority, and which plugin, theme, or part of WordPress core is responsible for the callback.

This is useful for understanding the order of execution on a page and for finding out which code is hooked onto a particular action or filter.

## Finding what's hooked where

If you're trying to figure out why something is behaving unexpectedly, the Hooks panel lets you search for a specific hook name and see everything that's attached to it. This is often faster than searching through plugin source code.

You can also filter by component to see all the hooks that a particular plugin is using.

## The `all` hook

If something is hooked onto the special `all` hook, Query Monitor will warn you about it. The `all` hook fires for every single action and filter in WordPress and has a significant performance cost. It's sometimes used for debugging but should never be present in production.

## Related hooks

Many of Query Monitor's panels also include a sub-menu which shows hooks related to that panel's functionality. [Read more about related hooks](../related-hooks/).
