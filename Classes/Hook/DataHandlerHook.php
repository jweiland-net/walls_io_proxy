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
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Http\ServerRequestFactory;

/**
 * Let's clear cache for our individual cacheCmd=WallsIoProxy
 * We do not use CachingFramework, but we use their API to catch that cacheCmd.
 */
class DataHandlerHook
{
    protected WallsService $wallsService;

    public function __construct(WallsService $wallsService)
    {
        $this->wallsService = $wallsService;
    }

    /**
     * Removes the cache of one specific walls_io_proxy Plugin from sys_registry
     *
     * @param array<string, mixed> $params
     */
    public function clearCachePostProc(array $params): void
    {
        $request = $this->getTypo3Request();
        $contentRecordUid = (int)($request->getQueryParams()['contentRecordUid'] ?? 0);

        if (
            isset($params['cacheCmd'])
            && ($contentRecordUid)
            && strtolower($params['cacheCmd']) === 'wallioproxy'
            && $contentRecordUid > 0
        ) {
            echo $this->wallsService->clearCache($contentRecordUid);
        }
    }

    private function getTypo3Request(): ServerRequestInterface
    {
        if ($GLOBALS['TYPO3_REQUEST'] instanceof ServerRequestInterface) {
            return $GLOBALS['TYPO3_REQUEST'];
        }

        // Build up a minified version with just the server variables like GET, POST, COOKIE
        return ServerRequestFactory::fromGlobals();
    }
}
