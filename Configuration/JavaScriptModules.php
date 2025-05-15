<?php

/*
 * This file is part of the package jweiland/walls-io-proxy.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

return [
    'dependencies' => ['core', 'backend'],
    'imports' => [
        '@jweiland/walls-io-proxy/' => [
            'path' => 'EXT:walls_io_proxy/Resources/Public/JavaScript/',
            'exclude' => [
                'frontend-wall-js' => 'EXT:walls_io_proxy/Resources/Public/JavaScript/Wall.js',
            ],
        ],
    ],
];
