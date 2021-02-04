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

Wall ID
~~~~~~~

Please insert the walls.io ID

Amount of entries to load
~~~~~~~~~~~~~~~~~~~~~~~~~

How many entries should be loaded with request? Should be a multiple of "Amount of entries to show".

Amount of entries to show
~~~~~~~~~~~~~~~~~~~~~~~~~

This amount of entries will be visible displayed in frontend. With each hit on the load more button this amount
of entries will be appended to the current visible entries in frontend.
