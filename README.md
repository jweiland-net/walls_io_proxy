# TYPO3 Extension: walls_io_proxy

[![Packagist][packagist-logo-stable]][extension-packagist-url]
[![Latest Stable Version][extension-build-shield]][extension-ter-url]
[![Total Downloads][extension-downloads-badge]][extension-packagist-url]
[![Monthly Downloads][extension-monthly-downloads]][extension-packagist-url]
[![TYPO3 12.4][TYPO3-shield]][TYPO3-12-url]

![Build Status](https://github.com/jweiland-net/walls_io_proxy/workflows/CI/badge.svg)

**walls.io** is a service that displays entries or posts related to a specific topic or hashtag from various social media websites like Facebook, Twitter, and Instagram on your website. However, without a Premium Account (costing €500 per month as of January 10, 2020), you cannot use their API and must accept their cookies when using their embedded iframe or JS file implementation.

## Introduction

The **walls_io_proxy** extension for TYPO3 addresses this limitation by eliminating the need for client-side cookies from walls.io. It achieves this by moving all XHR/Ajax requests from walls.io's JavaScript file to the server-side using PHP. This approach ensures that no walls.io cookies are set on the client-side, enhancing privacy and compliance with cookie regulations.

## Features

- **Server-Side Data Retrieval**: Moves all data retrieval processes to the server-side, avoiding client-side cookies.
- **Customizable Templates**: Allows you to create your own FluidTemplate for displaying the social wall, providing full control over the design and layout.
  (Note: Each social media provider has its own design restrictions. So, you have to adapt these design rules into your templates.)
- **Enhanced Privacy**: No walls.io cookies are set on the client-side, ensuring better privacy for your users.

## Installation

You can install this extension via Composer with the following command:

```bash
composer req jweiland/walls-io-proxy
```

## Usage

After installing the extension, you need to configure it to start retrieving data from walls.io. Follow these steps:

1. **Configuration**: Configure the extension in the TYPO3 backend by setting up the necessary parameters such as the walls.io URL and any authentication if required.
2. **Template Setup**: Create a FluidTemplate to define how the social media posts should be displayed on your website.
3. **Integration**: Integrate the template into your TYPO3 site using TypoScript or other preferred methods.


<!-- MARKDOWN LINKS & IMAGES -->

[extension-build-shield]: https://poser.pugx.org/jweiland/walls-io-proxy/v/stable.svg?style=for-the-badge

[extension-downloads-badge]: https://poser.pugx.org/jweiland/walls-io-proxy/d/total.svg?style=for-the-badge

[extension-monthly-downloads]: https://poser.pugx.org/jweiland/walls-io-proxy/d/monthly?style=for-the-badge

[extension-ter-url]: https://extensions.typo3.org/extension/walls_io_proxy/

[extension-packagist-url]: https://packagist.org/packages/jweiland/walls-io-proxy/

[packagist-logo-stable]: https://img.shields.io/badge/--grey.svg?style=for-the-badge&logo=packagist&logoColor=white

[TYPO3-12-url]: https://get.typo3.org/version/12

[TYPO3-shield]: https://img.shields.io/badge/TYPO3-12.4-green.svg?style=for-the-badge&logo=typo3
