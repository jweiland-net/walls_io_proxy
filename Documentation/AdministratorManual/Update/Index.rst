.. include:: ../../Includes.txt

Updating
========

If you update EXT:walls_io_proxy to a newer version, please read this section carefully!

Update to Version 4.0.0
-----------------------

Because of various problems with our PHP socket connection we will use the walls.io API now. That's why you
have to pay for Premium account. Else you will not get API access.
Insert access token in Extension Settings

Update to Version 3.0.0
-----------------------

We are not using cloudfront in Fluid templates anymore. As we download all external resources now, you should
update your Fluid templates to use the local file path.

Update to Version 2.0.0
-----------------------

As we have switched over from Caching Framework to SysRegistry, it would be good, if you remove the unneeded
walls_io_proxy Cache Tables from Database.
