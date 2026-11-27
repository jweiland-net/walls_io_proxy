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

$GLOBALS['TCA']['tt_content']['types']['wallsioproxy']['showitem'] = '
    --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
        --palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.general;general,
        --palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.headers;headers,
    --div--;LLL:EXT:walls_io_proxy/Resources/Private/Language/locallang_db.xlf:tabs.configuration,pi_flexform,
    --div--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:tabs.appearance,
        --palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.frames;frames,
        --palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.appearanceLinks;appearanceLinks,
    --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:language,
        --palette--;;language,
    --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
        --palette--;;hidden,
        --palette--;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:palette.access;access,
    --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:categories,
        categories,
    --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:notes,
        rowDescription,
    --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,
';

ExtensionManagementUtility::addPlugin(
    new SelectItem(
        type: 'select',
        label: 'LLL:EXT:walls_io_proxy/Resources/Private/Language/locallang_db.xlf:plugin.walls_io_proxy.title',
        value: 'wallsioproxy',
        icon: 'ext-wallsioproxy-wizard-icon',
        group: 'plugins',
        description: 'LLL:EXT:walls_io_proxy/Resources/Private/Language/locallang_db.xlf:plugin.walls_io_proxy.description',
    ),
);

// pi_flexform is already part of the showitem list above, so the data structure is assigned directly
$GLOBALS['TCA']['tt_content']['types']['wallsioproxy']['columnsOverrides']['pi_flexform']['config']['ds']
    = 'FILE:EXT:walls_io_proxy/Configuration/FlexForms/WallsIoProxy.xml';

// Backend Plugin Preview
$GLOBALS['TCA']['tt_content']['types']['wallsioproxy']['previewRenderer'] = WallsIoBackendPreviewRenderer::class;
