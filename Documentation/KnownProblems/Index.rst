.. include:: ../Includes.txt


.. _known-problems:

==============
Known Problems
==============

If walls.io will change their JS implementation and their XHR/Ajax Requests, this extensions
may not work anymore.

We try to keep the requests to walls.io as small as possible. That's why we cache the response into a record
of sys_registry. Why not TYPO3 Caching Framework? In past we found out, that 1 or 2 times a day we get no data
from walls.io. So, if you clear the TYPO3 Cache, the Cache of walls_io_proxy would be cleared, too. In that case it
may happen, that the output is empty and would be stored in cache_hash. In that case the output will be empty until
the cache is cleared again.
That's why we store the request in sys_registry. If you clear the cache and we don't get data from walls.io we
can still access the entry from sys_registry and show the wall. But how to remove the entry from sys_registry? We
have added a button into Plugin Preview in Backend to clear the cache for the configured wall in the plugin.
