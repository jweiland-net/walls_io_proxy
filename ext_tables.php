<?php
if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addLLrefForTCAdescr(
    'tt_content.pi_flexform.wallsioproxy',
    'EXT:walls_io_proxy/Resources/Private/Language/locallang_csh_flexform.xlf'
);
