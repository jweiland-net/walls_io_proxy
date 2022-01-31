.. include:: ../Includes.txt


.. _changelog:

ChangeLog
=========

**Version 4.3.0**

- Replace PostsRequest with ChangedRequest
- You can load up to 1000 entries by one call to walls.io
- Limit the entries to load by a range of x days

**Version 4.2.0**

- Deprecated: Do not configure accessToken in Extension Settings anymore
- Feature: You can configure accessToken in walls.io plugin directly
- Removed: There is no need to configure wallId anymore
- Task: Use content record UID as cache identifier
- Update translations. New label for Clear Cache button

**Version 4.1.1**

- Do not show deactivated wall posts

**Version 4.1.0**

- Cross posts from other social media plattforms will not be added anymore

**Version 4.0.3**

- Add ExtensionKey to composer.json
- Typo. Change getError() to getErrors().
- Code CleanUp

**Version 4.0.2**

- Add external name for twitter
- Remove height from checkin name CSS

**Version 4.0.1**

- Add Author and prepare comment for HTML

**Version 4.0.0**

- Using API access instead of WebSocket

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
