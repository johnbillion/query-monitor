---
title: Cache hit rate
---

# Cache hit rate

Does your object cache hit rate always show as `100%`? If so, this is possibly due to a bug in the Memcached object cache controller.

The bug was fixed here: https://github.com/Ipstenu/memcached-redux/pull/2 . You may need to update your object cache drop-in.

If you're not using the Memcached object cache controller and you're always seeing either `0%` or `100%` cache hit rate, please let me know!
