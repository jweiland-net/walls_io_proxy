<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/walls-io-proxy.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\WallsIoProxy\Backend\Preview;

use TYPO3\CMS\Backend\Preview\StandardContentPreviewRenderer;
use TYPO3\CMS\Backend\View\BackendLayout\Grid\GridColumnItem;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\View\ViewFactoryData;
use TYPO3\CMS\Core\View\ViewFactoryInterface;

/**
 * Renders the content of the walls.io plugin in the page module.
 * Header, footer and wrapping are inherited from TYPO3's standard preview renderer.
 */
class WallsIoBackendPreviewRenderer extends StandardContentPreviewRenderer
{
    private const PREVIEW_TEMPLATE = 'EXT:walls_io_proxy/Resources/Private/Templates/PluginPreview/WallsIoProxyPluginPreview.html';

    public function __construct(
        private readonly FlexFormTools $flexFormTools,
        private readonly ViewFactoryInterface $viewFactory,
        private readonly Registry $registry,
    ) {
        parent::__construct();
    }

    public function renderPageModulePreviewContent(GridColumnItem $item): string
    {
        $row = $item->getRow();
        $recordUid = (int)($row['uid'] ?? 0);

        $view = $this->viewFactory->create(
            new ViewFactoryData(
                templatePathAndFilename: self::PREVIEW_TEMPLATE,
            ),
        );
        $view->assignMultiple($row);
        $view->assign('pi_flexform_transformed', $this->getPiFlexformData($row));
        $view->assign('pageCacheExpireTime', $this->getPageCacheExpireTime($recordUid));

        return $view->render();
    }

    public function getPageCacheExpireTime(int $recordUid): int
    {
        return (int)$this->registry->get('WallsIoProxy', 'PageCacheExpireTime_' . $recordUid, 0);
    }

    /**
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function getPiFlexformData(array $row): array
    {
        $flexFormContent = $row['pi_flexform'] ?? '';
        if (!is_string($flexFormContent) || $flexFormContent === '') {
            return [];
        }

        return $this->flexFormTools->convertFlexFormContentToArray($flexFormContent);
    }
}
