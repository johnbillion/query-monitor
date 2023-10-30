---
title: wp_die()
parent: WordPress debugging
---

# wp_die() debugging in Query Monitor

The `wp_die()` output in WordPress is a thing of beauty... if you’re into minimalism.

[![Screenshot of the useless output of a call to wp_die()](/assets/wp-die-generic.png)](/assets/wp-die-generic.png)

Query Monitor adds some debugging information to the output of `wp_die()`, including the component responsible and the call stack, to help you identify the source of the message:

[![Screenshot of a slightly more useful output of a call to wp_die() with Query Monitor enabled](/assets/wp-die-stack.png)](/assets/wp-die-stack.png)
