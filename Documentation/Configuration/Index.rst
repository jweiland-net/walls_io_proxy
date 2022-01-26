.. include:: ../Includes.txt


.. _configuration:

Configuration
=============

Target group: **Developers, Integrators**


Minimal Example
---------------

- It is necessary to include static template `Walls.io Proxy (walls_io_proxy)`

This will set the path to our Main Template file, which can can change of cause:

.. code-block:: typoscript

   tt_content.wallsioproxy {
      # Override our template, if you want
      templateRootPaths.10 = EXT:your_ext/Resources/Private/Templates/
   }


.. _configuration-typoscript:

TypoScript Setup Reference
--------------------------

You can change the templateRootPaths. See above.

Further you can change walls.io CSS file and our JS:

.. code-block:: typoscript

   # Change CSS file
   page.includeCSS.wall-fluid = EXT:your_ext/Resources/Public/Css/WhatEver.css

   # Change our JS file
   page.includeJSFooterlibs.wall-fluid = EXT:your_ext/Resources/Public/JavaScript/Wall.js


.. _configuration-wallsioproxy:

ContentElement Settings
-----------------------

After installation of walls_io_proxy you will find a new ContentElement in newContentElementWizard called
"Walls.io Proxy". On Tab "Walls.io" you can configure the Output as follows:

Access Token
~~~~~~~~~~~~

Enter the access token. Retrieve one in the customer menu of walls.io

Amount of entries to load
~~~~~~~~~~~~~~~~~~~~~~~~~

Default: 24
Max: 999 (1000 is the max. value allowed by walls.io)

Define the amount of entries to load from walls.io within one request. If you have some conditions implemented
in Fluid View, you should increase this value a little bit to match your needs.

Amount of entries to show
~~~~~~~~~~~~~~~~~~~~~~~~~

Default: 8

This amount of entries will be visible displayed in frontend. With each hit on the "load more" button this amount
of entries will be appended to the current visible entries in frontend.

Show entries of the last x days
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

Default: 365 (1 year)

Since walls_io_proxy v5.1.0 we had to switch to another API call. The new API call needs a further mandatory
value called `since`. `since` is used to load entries from walls.io with a maximum date. Don't be afraid, you still
will get the most current, updated entries first ;-)
