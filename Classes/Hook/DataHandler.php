<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/walls-io-proxy.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\WallsIoProxy\Hook;

use JWeiland\WallsIoProxy\Service\WallsService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Let's clear cache for our individual cacheCmd=WallsIoProxy
 * We do not use CachingFramework, but we use their API to catch that cacheCmd.
 */
class DataHandler
{
    /**
     * Removes the cache of one specific walls_io_proxy Plugin from sys_registry
     */
    public function clearCachePostProc(array $params): void
    {
        if (
            isset($params['cacheCmd'])
            && strtolower($params['cacheCmd']) === 'wallioproxy'
        ) {
            $wallsService = GeneralUtility::makeInstance(WallsService::class);

            echo $wallsService->clearCache((int)GeneralUtility::_GET('contentRecordUid'));
        }
    }
}
