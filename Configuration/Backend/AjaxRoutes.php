<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/walls-io-proxy.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

use JWeiland\WallsIoProxy\Controller\ClearCacheController;

return [
    'walls_io_proxy_clear_cache' => [
        'path' => '/walls-io-proxy/clear-cache',
        'methods' => ['POST'],
        'target' => ClearCacheController::class . '::clearAction',
    ],
];
