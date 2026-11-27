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

use Psr\Log\LogLevel;
use TYPO3\CMS\Core\Log\Writer\FileWriter;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

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
    'defaultContentRendering',
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
