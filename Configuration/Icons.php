<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/walls-io-proxy.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

use TYPO3\CMS\Core\Imaging\IconProvider\SvgIconProvider;

return [
    'ext-wallsioproxy-wizard-icon' => [
        'provider' => SvgIconProvider::class,
        'source' => 'EXT:walls_io_proxy/Resources/Public/Icons/Extension.svg',
    ],
];
