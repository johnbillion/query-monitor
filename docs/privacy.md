---
nav_exclude: true
---

# Privacy statement

Query Monitor is private by default and always will be. It does not persistently store any of the data that it collects. It does not send data to any third party, nor does it include any third party resources.

Query Monitor implements an optional browser cookie that allows a user to view Query Monitor output when not logged in, or when logged in as another user who cannot usually view Query Monitor's output. This cookie can be set and cleared from the Settings panel in Query Monitor. This cookie operates using the same mechanism as the authentication cookies in WordPress core, and therefore it contains the user's `user_login` field in plain text which should be treated as potentially personally identifiable information. The name of the cookie is `wp-query_monitor_{COOKIEHASH}` where `{COOKIEHASH}` is the value of the `COOKIEHASH` constant on your site.

Query Monitor implements an optional browser cookie that allows a user to specify which text editor they use so that Query Monitor can display links that open in the chosen editor. This cookie includes no sensitive data. The name of the cookie is `wp-query_monitor_editor_{COOKIEHASH}` where `{COOKIEHASH}` is the value of the `COOKIEHASH` constant on your site.

Query Monitor stores some user preferences in the browser's Local Storage and Session Storage. It stores the ID of the most recently accessed panel, its dimensions and position, values for table column filters, and the dark/light mode preference. These data are stored using the browser's `localStorage` and `sessionStorage` APIs, which do not get sent with HTTP requests, and do not contain any personally identifiable information.

Please note that in a future version of Query Monitor, opt-in features may be introduced which allow a user to choose to persistently store data and/or send data to a third party service. Such features will only ever be opt-in.
