<?php
declare(strict_types = 1);
namespace JWeiland\WallsIoProxy\Hook;

/*
 * This file is part of the walls_io_proxy project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Let's clear cache for our individual cacheCmd=WallsIoProxy
 * We do not use CachingFramework, but we use their API to catch that cacheCmd.
 */
class DataHandler
{
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
        ) {
            $wallId = (int)GeneralUtility::_GET('wallId');
            if ($wallId) {
                $registry = GeneralUtility::makeInstance(Registry::class);
                $registry->remove('WallsIoProxy', 'WallId_' . $wallId);
                echo 1;
            } else {
                echo 0;
            }
        }
    }
}
