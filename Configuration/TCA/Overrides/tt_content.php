<?php

/*
 * This file is part of the package jweiland/walls-io-proxy.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

if (!defined('TYPO3')) {
    die('Access denied.');
}

use JWeiland\WallsIoProxy\Backend\Preview\WallsIoBackendPreviewRenderer;
use TYPO3\CMS\Core\Schema\Struct\SelectItem;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

ExtensionManagementUtility::addPlugin(
    new SelectItem(
        type: 'select',
        label: 'walls_io_proxy.db:plugin.walls_io_proxy.title',
        value: 'wallsioproxy',
        icon: 'ext-wallsioproxy-wizard-icon',
        group: 'plugins',
        description: 'walls_io_proxy.db:plugin.walls_io_proxy.description',
    ),
    'FILE:EXT:walls_io_proxy/Configuration/FlexForms/WallsIoProxy.xml',
);

// Backend Plugin Preview
$GLOBALS['TCA']['tt_content']['types']['wallsioproxy']['previewRenderer'] = WallsIoBackendPreviewRenderer::class;
