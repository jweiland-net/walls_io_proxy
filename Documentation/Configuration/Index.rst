..  include:: /Includes.rst.txt


..  _configuration:

=============
Configuration
=============

Target group: **Developers, Integrators**


Minimal Example
===============

*   It is necessary to include static template `Walls.io Proxy (walls_io_proxy)`

This will set the path to our Main Template file, which can can change of cause:

..  code-block:: typoscript

    tt_content.wallsioproxy {
      # Override our template, if you want
      templateRootPaths.10 = EXT:your_ext/Resources/Private/Templates/
    }


..  _configuration-typoscript:

TypoScript Setup Reference
==========================

You can change the templateRootPaths. See above.

Further you can change walls.io CSS file and our JS:

..  code-block:: typoscript

    # Change CSS file
    page.includeCSS.wall-fluid = EXT:your_ext/Resources/Public/Css/WhatEver.css

    # Change our JS file
    page.includeJSFooterlibs.wall-fluid = EXT:your_ext/Resources/Public/JavaScript/Wall.js


..  _configuration-wallsioproxy:

ContentElement Settings
=======================

After installation of `walls_io_proxy` you will find a new ContentElement in newContentElementWizard called
"Walls.io Proxy". On Tab "Walls.io" you can configure the Output as follows:

Access Token
------------

Enter the access token. Retrieve one in the customer menu of walls.io

Request Type
------------

walls.io comes with two main API interfaces to request wall posts:

**PostsRequest (/v1/posts)**

These wall posts are sorted by their date of receipt at walls.io. This option should be OK for most
customers. Keep in mind: If you add a new social media platform to this wall in customer menu of walls.io
ALL wall posts of this platform will get the same creation date (receipt date at walls.io). This may lead
to old wall posts visible at first. If you often switch the social media platforms you should consider
to use **ChangedRequests**. If you add a new social media platform once a year we prefer to just wait a few
days (20 or 50 new posts) until the ordering is OK for you again.

**ChangedRequest (/v1/posts/changed)**

These wall posts are sorted by their modification date. If you update an old wall post it will be visible
at first position in frontend.

Amount of entries to load
-------------------------

Default: 24
Max: 999 (1000 is the max. value allowed by walls.io)

Define the amount of entries to load from walls.io within one request. If you have some conditions implemented
in Fluid View, you should increase this value a little bit to match your needs.

Amount of entries to show
-------------------------

Default: 8

This amount of entries will be visible displayed in frontend. With each hit on the "load more" button this amount
of entries will be appended to the current visible entries in frontend.

Show entries of the last x days
-------------------------------

Default: 365 (1 year)

Since walls_io_proxy v5.1.0 you can sort wall posts by their modification date. This new API call needs a further
mandatory value called `since`. `since` is used to load entries from walls.io with a maximum date. Don't be afraid, you
still will get the most current, updated entries first ;-)
