---
nav_exclude: true
publish: false
---

# Privacy statements

## Query Monitor plugin privacy statement

Query Monitor is private by default and always will be. It does not send data to any third party, nor does it include any third party resources.

Query Monitor implements an optional browser cookie that allows a user to view Query Monitor output when not logged in, or when logged in as another user who cannot usually view Query Monitor's output. This cookie can be set and cleared from the Settings panel in Query Monitor. This cookie operates using the same mechanism as the authentication cookies in WordPress core, and therefore it contains the user's `user_login` field in plain text which should be treated as potentially personally identifiable information. The name of the cookie is `wp-query_monitor_{COOKIEHASH}` where `{COOKIEHASH}` is the value of the `COOKIEHASH` constant on your site.

Query Monitor stores some user preferences in the browser's Local Storage and Session Storage. It stores the ID of the most recently accessed panel, its dimensions and position, values for table column filters, the editor preference, and the dark/light mode preference. These data are stored using the browser's `localStorage` and `sessionStorage` APIs, which do not get sent with HTTP requests, and do not contain any personally identifiable information.

Query Monitor writes the data it collects for each request to a file in the `query-monitor` directory within your WordPress uploads directory. This allows its output to be loaded separately from the page and allows it to show data for requests other than the current page load, such as Ajax and REST API requests. These files remain on your own server, are not sent to any third party, and can be deleted safely at any time. The location of this directory can be changed using the `qm/data/dir` filter.

## Query Monitor browser extension privacy statement

The Query Monitor browser extension only reads Query Monitor data from pages you inspect with your browser's developer tools. It does not read any other data, all data remains in your browser, and no data is sent to any third party.

The browser extension stores the same user preferences as the Query Monitor plugin in the browser's Local Storage, as described above.

## querymonitor.com website privacy statement

The querymonitor.com website:

* Makes no use of cookies and collects no client-side data from visitors. Anonymous analytics are collected server-side.
* Is operated by [John Blackbourn](/about/).
* Is hosted on [Netlify](https://www.netlify.com/).

This privacy statement is subject to change and was last updated on April 20, 2026.
