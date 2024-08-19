<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/walls-io-proxy.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\WallsIoProxy\Hook;

use TYPO3\CMS\Backend\View\PageLayoutView;
use TYPO3\CMS\Backend\View\PageLayoutViewDrawItemHookInterface;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Service\FlexFormService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Let's clear cache for our individual cacheCmd=WallsIoProxy
 * We do not use CachingFramework, but we use their API to catch that cacheCmd.
 */
class PageLayoutViewHook implements PageLayoutViewDrawItemHookInterface
{
    protected Registry $registry;

    public function __construct(Registry $registry)
    {
        $this->registry = $registry;
    }

    public function preProcess(PageLayoutView &$parentObject, &$drawItem, &$headerContent, &$itemContent, array &$row)
    {
        if ($row['CType'] !== 'wallsioproxy') {
            return;
        }

        $view = $this->getStandaloneView();
        $view->setTemplatePathAndFilename(
            'EXT:walls_io_proxy/Resources/Private/Templates/PluginPreview/TableView.html'
        );

        $view->assignMultiple($row);

        if (!empty($row['pi_flexform'])) {
            $flexFormService = GeneralUtility::makeInstance(FlexFormService::class);
            $view->assign('pi_flexform_transformed', $flexFormService->convertFlexFormContentToArray($row['pi_flexform']));
        }

        $view->assign('pageCacheExpireTime', $this->getPageCacheExpireTime((int)($row['uid'] ?? 0)));

        $itemContent = $view->render();
        $drawItem = false;
    }

    public function getPageCacheExpireTime(int $recordUid): int
    {
        return $this->registry->get(
            'WallsIoProxy',
            'PageCacheExpireTime_' . $recordUid,
            0
        );
    }

    private function getStandaloneView(): StandaloneView
    {
        return GeneralUtility::makeInstance(StandaloneView::class);
    }
}
