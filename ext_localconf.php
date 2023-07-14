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

    // Register SVG Icon Identifier
    $iconRegistry = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(
        \TYPO3\CMS\Core\Imaging\IconRegistry::class
    );
    $svgIcons = [
        'ext-wallsioproxy-wizard-icon' => 'Extension.svg',
    ];
    foreach ($svgIcons as $identifier => $fileName) {
        $iconRegistry->registerIcon(
            $identifier,
            \TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider::class,
            ['source' => 'EXT:walls_io_proxy/Resources/Public/Icons/' . $fileName]
        );
    }

    // add walls_io_proxy plugin to new element wizard
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addPageTSConfig(
        '<INCLUDE_TYPOSCRIPT: source="FILE:EXT:walls_io_proxy/Configuration/TSconfig/ContentElementWizard.txt">'
    );

    $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tcemain.php']['clearCachePostProc']['wallsioproxy_clearcache'] =
        \JWeiland\WallsIoProxy\Hook\DataHandler::class . '->clearCachePostProc';
});
