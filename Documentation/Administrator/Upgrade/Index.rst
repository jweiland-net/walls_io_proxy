..  include:: /Includes.rst.txt


========
Updating
========

If you update EXT:walls_io_proxy to a newer version, please read this
section carefully!

Update to Version 9.0.0
=======================

TYPO3 13 is not supported anymore. Version 9.0.0 requires TYPO3 14.3.

**Request type (breaking, security)**

The request type of a plugin was stored as PHP class name in the FlexForm
and instantiated. That has been replaced by the stable identifiers `posts`
and `changed`. Existing plugins which still contain the old class names
(`JWeiland\WallsIoProxy\Request\PostsRequest` and
`JWeiland\WallsIoProxy\Request\Posts\ChangedRequest`) will continue to work,
because these values are mapped to the new identifiers. Any other value is
rejected, no posts will be requested and a warning is written to the log.
The selectbox in the backend may show the old value as "invalid" until you
save the plugin once again. Please open and save all walls.io plugins to store
the new identifiers.

**Clear cache button**

The button in the plugin preview does not use the DataHandler cache command
`WallIoProxy` anymore, but the backend AJAX route
`ajax_walls_io_proxy_clear_cache` (POST, administrators only). If you have
registered your own calls to the removed `DataHandlerHook`, please use
`WallsService::clearCache()` instead, which returns a bool now.

**Cached images**

Images are stored with a hash as file name and an extension derived from the
detected image type now. Files of earlier versions (with the original file
names of the walls.io CDN) are not used anymore. We recommend to remove the
folder `typo3temp/assets/walls_io_proxy`, or to clear the cache of all walls.io
plugins with the button in the backend. Posts without a valid image will not
show the image anymore (the remote URL is never used as fallback).

**Comments**

The `html_comment` of a post is sanitized now. Only simple formatting
(`b`, `strong`, `i`, `em`, `u`, `p`, `span`, `ul`, `ol`, `li`, `blockquote`,
`br`), links to http(s) targets and images of the local cache are allowed.
All other markup is encoded or removed.

**Fluid template and assets**

The templates have been renamed to `*.fluid.html`. If you have overridden
them, rename your files, too. CSS and JavaScript are included in the template by
`f:asset.css` and `f:asset.script` instead of the TypoScript options
`page.includeCSS` and `page.includeJSFooterlibs`. The "load more" button uses
the attribute `data-walls-io-load-more` instead of an inline `onclick` handler.
Wall.js does not provide the global function `showWallIoEntries()` anymore and
does not require jQuery. jQuery Masonry is not triggered automatically
anymore. Listen to the event `walls-io-proxy:entries-shown` on the container
`.socialwall_container` to re-layout your grid.

**API**

All PHP classes of the extension are `final`. `PluginConfiguration` is a
readonly value object: use `PluginConfiguration::fromProcessedData()` instead
of passing processed data to the constructor. The constructor of `WallsService`
has changed and `WallsService::getTargetDirectory()` has been moved to
`ExternalResourceCache`. The class `Utility\StringUtility` has been removed,
use `str_starts_with()`.

**Logging**

The extension logs to the logger `JWeiland.WallsIoProxy` (default: file
`var/log/typo3_walls_io_proxy_*.log`, level info and above). The access token is
never logged.

Update to Version 6.0.0
=======================

We have migrated the file ending `ts` to `typoscript`. Please update
your references, if you make use of the old file endings.

Update to Version 5.2.0
=======================

If you do not use `walls_io_proxy` as an API (WallsService) you can update to
version 5.2.0 without any problems.

For all other users: WallsService::getWallPosts() expects an object of type
PluginConfiguration now. Please update your code accordingly.

Update to Version 5.0.0
=======================

We have removed TYPO3 8 compatibility. No new features added, so no problems
while upgrading.

Update to Version 4.2.0
=======================

As a `WallId` is not needed anymore, all previously created cache entries
are invalid.

If you don't like unused data in database you can remove them with
following SQL query:

`DELETE FROM sys_registry WHERE entry_namespace = 'WallsIoProxy';`

Further you can remove all files in here:

`typo3temp/assets/walls_io_proxy`

Update to Version 4.0.0
=======================

Because of various problems with our PHP socket connection we will use
the walls.io API now. That's why you have to pay for Premium account. Else you
will not get API access. Insert access token in Extension Settings

Update to Version 3.0.0
=======================

We are not using cloudfront in Fluid templates anymore. As we download all
external resources now, you should update your Fluid templates to use the
local file path.

Update to Version 2.0.0
=======================

As we have switched over from Caching Framework to SysRegistry, it would be
good, if you remove the unneeded walls_io_proxy Cache Tables from Database.
