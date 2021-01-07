.. include:: ../Includes.txt


.. _changelog:

ChangeLog
=========

**Version 3.0.0**

- Use WebSocket connection to get walls.io entries
- Update fluid templates to use local file paths
- All external files will be proxied now
- Clear Cache of Content Element now truncates Cache Directory, too.

**Version 2.0.0**

- Remove ClearCacheHook which removes cached Walls from Caching Framework
- Use TYPO3 Registry instead of TYPO3 Caching Framework
- Remove $contentElementUid from WallsIoService as it is not needed anymore

**Version 1.0.1**

- Bugfix: Check against jquery before calling masonry

**Version 1.0.0**

- Initial upload
