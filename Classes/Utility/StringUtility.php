<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/walls-io-proxy.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\WallsIoProxy\Utility;

use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\GeneralUtility;

class StringUtility
{
    /**
     * Checks if a string begins with the given substring.
     * Uses native str_starts_with in PHP 8.0+, TYPO3 Core's method if available in TYPO3 v11,
     * otherwise falls back to a custom implementation.
     */
    public static function beginsWith(string $haystack, string $needle): bool
    {
        // Check if PHP version is 8.0 or greater
        if (PHP_VERSION_ID >= 80000) {
            return str_starts_with($haystack, $needle);
        }

        // Check if TYPO3 version is 11.0 or less than 12 use the TYPO3 Core method
        if (self::isTypo3VersionLessThan12()) {
            return \TYPO3\CMS\Core\Utility\StringUtility::beginsWith($haystack, $needle);
        }

        // Fallback for older PHP and TYPO3 versions
        return self::customBeginsWith($haystack, $needle);
    }

    /**
     * Fallback implementation for PHP versions lower than 8.0.
     */
    private static function customBeginsWith(string $haystack, string $needle): bool
    {
        return substr($haystack, 0, strlen($needle)) === $needle;
    }

    /**
     * Checks if the TYPO3 version is 12.0 or greater.
     */
    private static function isTypo3VersionLessThan12(): bool
    {
        $versionInformation = GeneralUtility::makeInstance(
            Typo3Version::class
        );

        return $versionInformation->getMajorVersion() < 12;
    }
}
