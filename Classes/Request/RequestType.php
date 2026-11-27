<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/walls-io-proxy.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\WallsIoProxy\Request;

/**
 * Stable identifiers of the walls.io endpoints a plugin can be configured with.
 * Only these identifiers can be resolved to a request, so no arbitrary class name
 * from the FlexForm will ever be instantiated.
 */
enum RequestType: string
{
    case Posts = 'posts';
    case Changed = 'changed';

    /**
     * Until version 8 the FlexForm stored the PHP class name of the request. Records which were not saved
     * again since then still contain these values.
     */
    private const LEGACY_VALUES = [
        'JWeiland\WallsIoProxy\Request\PostsRequest' => self::Posts,
        'JWeiland\WallsIoProxy\Request\Posts\ChangedRequest' => self::Changed,
    ];

    public static function tryFromStoredValue(string $storedValue): ?self
    {
        $storedValue = trim($storedValue);

        return self::tryFrom($storedValue) ?? self::LEGACY_VALUES[ltrim($storedValue, '\\')] ?? null;
    }
}
