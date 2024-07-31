<?php
if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

call_user_func(static function (): void {
    // Configure frontend plugin
    $pluginContent = trim('
tt_content.wallsioproxy =< lib.contentElement
tt_content.wallsioproxy {
templateName = WallsIoProxy
}');
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addTypoScript(
        'walls_io_proxy',
        'setup',
        '
# Setting walls_io_proxy plugin TypoScript
' . $pluginContent,
        'defaultContentRendering'
    );

    // add walls_io_proxy plugin to new element wizard
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig(
        '<INCLUDE_TYPOSCRIPT: source="FILE:EXT:walls_io_proxy/Configuration/TSconfig/ContentElementWizard.tsconfig">'
    );

    if (!isset($GLOBALS['TYPO3_CONF_VARS']['LOG']['JWeiland']['WallsIoProxy']['writerConfiguration'])) {
        $GLOBALS['TYPO3_CONF_VARS']['LOG']['JWeiland']['WallsIoProxy']['writerConfiguration'] = [
            \Psr\Log\LogLevel::INFO => [
                \TYPO3\CMS\Core\Log\Writer\FileWriter::class => [
                    'logFileInfix' => 'walls_io_proxy',
                ],
            ],
        ];
    }

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearCachePostProc']['wallsioproxy_clearcache'] =
        \JWeiland\WallsIoProxy\Hook\DataHandler::class . '->clearCachePostProc';
});
