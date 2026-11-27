<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/walls-io-proxy.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\WallsIoProxy\Tests\Unit\Request;

use JWeiland\WallsIoProxy\Request\RequestType;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test RequestType
 */
class RequestTypeTest extends UnitTestCase
{
    #[Test]
    public function tryFromStoredValueResolvesStableIdentifiers(): void
    {
        self::assertSame(RequestType::Posts, RequestType::tryFromStoredValue('posts'));
        self::assertSame(RequestType::Changed, RequestType::tryFromStoredValue('changed'));
    }

    #[Test]
    public function tryFromStoredValueResolvesLegacyClassNames(): void
    {
        self::assertSame(
            RequestType::Posts,
            RequestType::tryFromStoredValue('JWeiland\WallsIoProxy\Request\PostsRequest'),
        );
        self::assertSame(
            RequestType::Changed,
            RequestType::tryFromStoredValue('JWeiland\WallsIoProxy\Request\Posts\ChangedRequest'),
        );
    }

    #[Test]
    public function tryFromStoredValueIgnoresSurroundingWhitespace(): void
    {
        self::assertSame(RequestType::Posts, RequestType::tryFromStoredValue(" posts\n"));
    }

    #[Test]
    public function tryFromStoredValueReturnsNullForUnknownValues(): void
    {
        self::assertNull(RequestType::tryFromStoredValue(''));
        self::assertNull(RequestType::tryFromStoredValue('POSTS'));
        self::assertNull(RequestType::tryFromStoredValue('TYPO3\CMS\Core\Http\RequestFactory'));
        self::assertNull(RequestType::tryFromStoredValue('../../etc/passwd'));
    }
}
