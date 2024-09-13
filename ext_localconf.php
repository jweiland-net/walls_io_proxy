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

use JWeiland\WallsIoProxy\Hook\DataHandler;
use JWeiland\WallsIoProxy\Hook\PageLayoutViewHook;
use Psr\Log\LogLevel;
use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Log\Writer\FileWriter;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

call_user_func(static function (): void {
    // Configure frontend plugin
    $pluginContent = trim('
    tt_content.wallsioproxy =< lib.contentElement
    tt_content.wallsioproxy {
        templateName = WallsIoProxy
    }');

    ExtensionManagementUtility::addTypoScript(
        'walls_io_proxy',
        'setup',
        '
        # Setting walls_io_proxy plugin TypoScript
        ' . $pluginContent,
        'defaultContentRendering'
    );

    if (!isset($GLOBALS['TYPO3_CONF_VARS']['LOG']['JWeiland']['WallsIoProxy']['writerConfiguration'])) {
        $GLOBALS['TYPO3_CONF_VARS']['LOG']['JWeiland']['WallsIoProxy']['writerConfiguration'] = [
            LogLevel::INFO => [
                FileWriter::class => [
                    'logFileInfix' => 'walls_io_proxy',
                ],
            ],
        ];
    }

    $versionInformation = GeneralUtility::makeInstance(
        Typo3Version::class
    );

    // add walls_io_proxy plugin to new element wizard
    // Only include page.tsconfig if TYPO3 version is below 12 so that it is not imported twice.
    if ($versionInformation->getMajorVersion() < 12) {
        ExtensionManagementUtility::addPageTSConfig('
            @import "EXT:walls_io_proxy/Configuration/page.tsconfig"
        ');
    }

    if (version_compare($versionInformation->getBranch(), '11.5', '<=')) {
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['tt_content_drawItem']['walls_io_proxy']
            = PageLayoutViewHook::class;
    }

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearCachePostProc']['wallsioproxy_clearcache']
        = DataHandler::class . '->clearCachePostProc';

});
