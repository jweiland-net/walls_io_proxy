..  include:: /Includes.rst.txt


..  _known-problems:

==============
Known Problems
==============

I'm missing new wall posts
==========================

`walls_io_proxy` was was designed to cache wall posts. This is useful, if API of walls.io is offline. Please check
the cache invalidation property in page property. Instead of caching the page content for 8 hours try values like
5 or 15 minutes.
