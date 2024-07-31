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
use TYPO3\CMS\Core\Log\Writer\FileWriter;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

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

    // add walls_io_proxy plugin to new element wizard
    ExtensionManagementUtility::addPageTSConfig(
        '<INCLUDE_TYPOSCRIPT:source = "FILE:EXT:walls_io_proxy/Configuration/TSconfig/ContentElementWizard.tsconfig" > '
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

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms/layout/class.tx_cms_layout.php']['tt_content_drawItem']['walls_io_proxy']
        = PageLayoutViewHook::class;

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearCachePostProc']['wallsioproxy_clearcache']
        = DataHandler::class . '->clearCachePostProc';
});
