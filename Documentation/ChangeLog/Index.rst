..  include:: /Includes.rst.txt


..  _changelog:

=========
ChangeLog
=========

Version 9.0.0
=============

*   [TASK] Add compatibility for TYPO3 14 LTS
*   [TASK] Removed support for TYPO3 13 LTS
*   [TASK] Replace removed FlexFormService with FlexFormTools in AddWallsProcessor and backend preview
*   [TASK] Use request of ContentObjectRenderer in AddWallsProcessor instead of injecting ServerRequest
*   [REFACTOR] Base WallsIoBackendPreviewRenderer on StandardContentPreviewRenderer and remove StandaloneView usage
*   [BUGFIX] Show page cache expire time in backend preview
*   [BUGFIX] Detect the Yoast page request header case-insensitively and skip storing the expire time without cache data collector
*   [TASK] Register plugin via addPlugin() with SelectItem
*   [TASK] Remove obsolete service definitions from Services.yaml
*   [BUGFIX] Download external resources only from http(s) URLs of public hosts. The target file name is a hash of the URL and the file extension is derived from the detected image type (jpg, png, gif, webp). Non-images, empty and oversized files (max. 5 MB) are rejected and logged. The remote URL is not used for the target path anymore (path traversal and PHP file upload)
*   [BUGFIX] Sanitize the comments of walls.io posts with TYPO3's HTML sanitizer before they are rendered. Only simple text formatting, http(s) links and cached images are allowed
*   [BUGFIX] Remove links of posts which are not http(s) and URL-encode external user names in profile links of the Fluid template
*   [BUGFIX] Never fall back to the external image URL, if an image could not be cached
*   [BUGFIX] Do not write the access token to log files anymore
*   [!!!][BUGFIX] The request type of the plugin is not stored as PHP class name anymore, but as one of the stable identifiers `posts` or `changed`. The new class `WallsIoRequestFactory` resolves these identifiers. Old class names stored in existing plugins are still mapped to the new identifiers, any other value is rejected
*   [!!!][BUGFIX] Replace the DataHandler `clearCachePostProc` hook (which echoed its result) with the backend AJAX route `walls_io_proxy_clear_cache`. The route requires POST and an administrator and validates the content record
*   [!!!][REFACTOR] Classes WallsService, WallsIoClient, AddWallsProcessor, PluginConfiguration, requests, preview renderer and ViewHelper are `final`. `PluginConfiguration` is a readonly value object, use `PluginConfiguration::fromProcessedData()` to create it from processed data. The constructor of `WallsService` has changed
*   [!!!][REFACTOR] Remove `WallsService::getTargetDirectory()` (moved to `ExternalResourceCache`), `DataHandlerHook` and `Utility\StringUtility`. `WallsService::clearCache()` returns a bool now
*   [BUGFIX] Clear the cached image files of a plugin recursively when the cache is cleared
*   [BUGFIX] Fix German label of "1 minute ago"
*   [BUGFIX] Replace the buggy handling of multiple external images within a comment
*   [BUGFIX] Do not create target directories when only reading them
*   [TASK] Log API failures, invalid plugin configuration, fallbacks to stored posts, rejected resource downloads and cache clearing (with backend user UID) to the logger `JWeiland.WallsIoProxy`
*   [TASK] Clear cache button of the plugin preview shows translated notifications. It is implemented as vanilla JavaScript ES module (no jQuery)
*   [TASK] Show a warning in the page module preview, if the plugin is not configured completely (access token, request type, ...)
*   [TASK] Use TYPO3 14 translation domains instead of `LLL:EXT:` references, add `target-language` to German XLIFF files
*   [TASK] Pass the FlexForm as 2nd argument of `addPlugin()` and remove the hand-written `showitem`
*   [TASK] Include CSS and JavaScript with `f:asset.css` and `f:asset.script` instead of `page.includeCSS` and `page.includeJSFooterlibs`
*   [TASK] Rewrite Wall.js as vanilla JavaScript without global functions and inline `onclick` handlers. Masonry (vanilla) is re-layouted automatically, the event `walls-io-proxy:entries-shown` is dispatched for other grids. jQuery Masonry is not triggered anymore
*   [TASK] Rename Fluid templates to `.fluid.html`
*   [TASK] Use the TYPO3 coding standards configuration of video_shariff, the current runTests.sh, add PHP 8.5 to the CI matrix and remove phpstan, php-cs-fixer and roave/security-advisories from require-dev where already provided

Version 8.1.1
=============

*   BUGFIX: Do not relate on server request on CLI usage

Version 8.1.0
=============

*   BUGFIX: Removed old version compatibility supported items
*   TASK: Moved page.tsconfig to Site Sets folder
*   TASK: Removes redundant target-language attributes from XLF files
*   TASK: Drop support for TYPO3 v11 and cleanup configuration

Version 8.0.3
=============

*   BUGFIX: Deleted ContentElementPreview.tsconfig as it show errors in BE

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
