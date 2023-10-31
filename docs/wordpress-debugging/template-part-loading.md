---
title: Template part loading
parent: WordPress debugging
redirect_from: /blog/2019/07/debugging-wordpress-template-part-loading/
---

# Debugging WordPress template part loading with Query Monitor

Template parts are a fundamental part of building WordPress themes, but sometimes it can be difficult to find out why a given template part is or isn't loading.

[Query Monitor](https://wordpress.org/plugins/query-monitor/) makes debugging template part loading easier by exposing the list of template parts that either were or were not loaded. Here's a screenshot of it in action:

[![Screenshot of the Template Parts section of the Template panel in Query Monitor](../../assets/template-parts.png)](../../assets/template-parts.png)

What this allows you to do is to see the value of the `$slug` and `$name` parameters that were passed to `get_template_part()` when an unsuccessful request for a template part was made. Query Monitor will show you the file name and line number where the call was made, so you can find it easily and investigate if necessary, or if you've got clickable stack traces enabled from the settings screen you can just click it to be taken straight there.

I hope this feature is useful to you! I've certainly been finding it useful myself.
