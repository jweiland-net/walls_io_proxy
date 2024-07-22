<?php
if (!defined('TYPO3')) {
    die('Access denied.');
}

call_user_func(static function () {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
        'walls_io_proxy',
        'Configuration/TypoScript',
        'Walls.io Proxy'
    );
});
