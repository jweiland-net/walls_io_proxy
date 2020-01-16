# TYPO3 Extension: walls_io_cache

walls.io is a service to show you entries/posts of a specific topic or hashtag from various social media websites
like Facebook, Twitter and Instagram on your website. But as long as you don't have a
Premium Account (500 € each month. Date: 10.01.2020) you can not use their API and you have to accept their cookies
while using their embedded iframe or JS file implementation. With walls_io_proxy no Cookie of walls.io will be set
on client-side anymore, as we have moved all XHR/Ajax Requests of their JS file to server-side (PHP).

Our walls_io_proxy is no API and will not use the API of walls.io.

As walls_io_proxy gets all data from walls.io now it's up to you to create your own FluidTemplate for your
social wall. We don't support all their integrated Themes, Styles and Views. You have the data now, do what you want.

# Installation

You can install this extension via Composer with:

`composer req jweiland/walls-io-proxy`
