<?php
declare(strict_types = 1);
namespace JWeiland\WallsIoProxy\Hooks;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Hook for DataHandler
 */
class DataHandler
{
    /**
     * Flushes the cache if a tt_content record was edited.
     *
     * @param array $params
     */
    public function clearCachePostProc(array $params)
    {
        if (
            (isset($params['table']) && $params['table'] === 'tt_content')
            || (isset($params['cacheCmd']) && in_array($params['cacheCmd'], ['all', 'pages', 'system'], true))
        ) {
            // @ToDo: Delete walls_io_proxy related cache
        }
    }
}
