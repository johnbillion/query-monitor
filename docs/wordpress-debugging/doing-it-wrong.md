---
title: Doing it Wrong
---

# Debugging "Doing it Wrong" notices with Query Monitor

WordPress has an internal function called `_doing_it_wrong()` which is triggered when code uses the WordPress API incorrectly. These notices can easily go unnoticed. Query Monitor surfaces them.

## What information is shown

The Doing it Wrong panel shows the explanation of what's being done incorrectly, which function or method triggered the notice, and which plugin, theme, or part of WordPress core was responsible.

## When is this useful?

These notices are WordPress telling you that your code (or a plugin's code) is not following the expected API conventions. Common examples include:

* Calling a function too early in the WordPress loading process
* Using a deprecated argument in a function call
* Registering something incorrectly, such as a post type or taxonomy
