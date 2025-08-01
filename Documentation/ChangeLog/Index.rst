..  include:: /Includes.rst.txt


..  _changelog:

=========
ChangeLog
=========

Version 8.0.2
=============

*   BUGFIX: Update testing directory

Version 8.0.1
=============

*   Prevent exception, when asset loading results in 403

Version 8.0.0
=============

*   TASK: Add TYPO3 13 compatibility
*   Removed lower version compatibilities

Version 7.0.1
=============

*   TASK: Add import path into proxy directory

Version 7.0.0
=============

*   TASK: Add TYPO3 12 compatibility
*   Test Suite Improvements
*   PHPStan Error Fixes
*   Documentation Updates

Version 6.0.0
=============

*   TASK: Add TYPO3 11 compatibility
*   TASK: Remove TYPO3 9 compatibility

Version 5.2.1
=============

*   BUGFIX: Add post_link to requested fields
*   DOCS: Set indents to 4 spaces
*   TASK: Update format to new config of php-cs-fixer

Version 5.2.0
=============

*   FEATURE: You can choose which request should be used to retrieve wall posts
*   BUGFIX: Repair message after clearing caches of wall posts
*   TASK: Introduce new object: PluginConfiguration
*   Added services.yaml
*   Add a lot more unit*   and functional tests

Version 5.1.0
=============

*   Replace PostsRequest with ChangedRequest
*   You can load up to 1000 entries by one call to walls.io
*   Limit the entries to load by a range of x days

Version 5.0.1
=============

*   Fix usage of deprecated method getModuleUrl() in UriBuilderViewHelper

Version 5.0.0
=============

*   Remove TYPO3 8 compatibility
*   Add TYPO3 10 compatibility

Version 4.2.0
=============

*   Deprecated: Do not configure accessToken in Extension Settings anymore
*   Feature: You can configure accessToken in walls.io plugin directly
*   Removed: There is no need to configure wallId anymore
*   Task: Use content record UID as cache identifier
*   Update translations. New label for Clear Cache button

Version 4.1.1
=============

*   Do not show deactivated wall posts

Version 4.1.0
=============

*   Cross posts from other social media plattforms will not be added anymore

Version 4.0.3
=============

*   Add ExtensionKey to composer.json
*   Typo. Change getError() to getErrors().
*   Code CleanUp

Version 4.0.2
=============

*   Add external name for twitter
*   Remove height from checkin name CSS

Version 4.0.1
=============

*   Add Author and prepare comment for HTML

Version 4.0.0
=============

*   Using API access instead of WebSocket

Version 3.0.0
=============

*   Use WebSocket connection to get walls.io entries
*   Update fluid templates to use local file paths
*   All external files will be proxied now
*   Clear Cache of Content Element now truncates Cache Directory, too.

Version 2.0.0
=============

*   Remove ClearCacheHook which removes cached Walls from Caching Framework
*   Use TYPO3 Registry instead of TYPO3 Caching Framework
*   Remove $contentElementUid from WallsIoService as it is not needed anymore

Version 1.0.1
=============

*   Bugfix: Check against jquery before calling masonry

Version 1.0.0
=============

*   Initial upload
