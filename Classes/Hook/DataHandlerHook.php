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

/**
 * Let's clear the cache for our individual cacheCmd=WallsIoProxy
 * We do not use CachingFramework, but we use their API to catch that cacheCmd.
 */
readonly class DataHandlerHook
{
    public function __construct(
        protected WallsService $wallsService,
    ) {}

    /**
     * Removes the cache of one specific walls_io_proxy Plugin from sys_registry
     *
     * @param array<string, mixed> $params
     */
    public function clearCachePostProc(array $params): void
    {
        if (($request = $this->getTypo3Request()) === null) {
            return;
        }

        $contentRecordUid = (int)($request->getQueryParams()['contentRecordUid'] ?? 0);

        if (
            isset($params['cacheCmd'])
            && strtolower($params['cacheCmd']) === 'wallioproxy'
            && $contentRecordUid > 0
        ) {
            // ToDo: With next release we should check how to get rid of that "echo". ResponseInterface?
            echo $this->wallsService->clearCache($contentRecordUid);
        }
    }

    private function getTypo3Request(): ?ServerRequestInterface
    {
        return ($GLOBALS['TYPO3_REQUEST'] ?? null) instanceof ServerRequestInterface
            ? $GLOBALS['TYPO3_REQUEST']
            : null;
    }
}
