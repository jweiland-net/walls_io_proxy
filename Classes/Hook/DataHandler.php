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
     * @var WallsService
     */
    protected $wallsService;

    public function __construct(WallsService $wallsService = null)
    {
        $this->wallsService = $wallsService ?? GeneralUtility::makeInstance(WallsService::class);
    }

    /**
     * Removes the cache of one specific walls_io_proxy Plugin from sys_registry
     *
     * @param array $params
     */
    public function clearCachePostProc(array $params)
    {
        if (
            isset($params['cacheCmd'])
            && strtolower($params['cacheCmd']) === 'wallioproxy'
            && ($contentRecordUid = (int)GeneralUtility::_GET('contentRecordUid'))
            && $contentRecordUid > 0
        ) {
            echo $this->wallsService->clearCache($contentRecordUid);
        }
    }
}
