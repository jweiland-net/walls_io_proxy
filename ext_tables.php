<?php

use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

/*
 * This file is part of the package jweiland/walls-io-proxy.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

if (!defined('TYPO3')) {
    die('Access denied.');
}

ExtensionManagementUtility::addLLrefForTCAdescr(
    'tt_content.pi_flexform.wallsioproxy',
    'EXT:walls_io_proxy/Resources/Private/Language/locallang_csh_flexform.xlf'
);
