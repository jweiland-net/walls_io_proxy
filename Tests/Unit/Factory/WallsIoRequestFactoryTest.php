<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/walls-io-proxy.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\WallsIoProxy\Tests\Unit\Factory;

use JWeiland\WallsIoProxy\Configuration\PluginConfiguration;
use JWeiland\WallsIoProxy\Factory\WallsIoRequestFactory;
use JWeiland\WallsIoProxy\Request\Posts\ChangedRequest;
use JWeiland\WallsIoProxy\Request\PostsRequest;
use JWeiland\WallsIoProxy\Request\RequestType;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\DateTimeAspect;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test WallsIoRequestFactory
 */
class WallsIoRequestFactoryTest extends UnitTestCase
{
    private const NOW = 1700000000;

    private WallsIoRequestFactory $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $context = new Context();
        $context->setAspect('date', new DateTimeAspect(new \DateTimeImmutable('@' . self::NOW)));

        $this->subject = new WallsIoRequestFactory($context);
    }

    protected function tearDown(): void
    {
        unset($this->subject);

        parent::tearDown();
    }

    #[Test]
    public function createWithPostsTypeReturnsConfiguredPostsRequest(): void
    {
        $request = $this->subject->create(
            RequestType::Posts,
            new PluginConfiguration([], 'ABC123', 'posts', 12),
        );

        self::assertInstanceOf(PostsRequest::class, $request);
        self::assertSame('/v1/posts', $request->getPath());
        self::assertSame('ABC123', $request->getParameter('access_token'));
        self::assertSame(12, $request->getParameter('limit'));
        self::assertStringContainsString('comment', (string)$request->getParameter('fields'));
        self::assertFalse($request->hasParameter('since'));
    }

    #[Test]
    public function createWithChangedTypeReturnsChangedRequestWithConfiguredStartDate(): void
    {
        $request = $this->subject->create(
            RequestType::Changed,
            new PluginConfiguration([], 'ABC123', 'changed', 24, 8, 2),
        );

        self::assertInstanceOf(ChangedRequest::class, $request);
        self::assertSame('/v1/posts/changed', $request->getPath());
        self::assertSame('ABC123', $request->getParameter('access_token'));
        self::assertSame(self::NOW - (2 * 86400), $request->getParameter('since'));
    }

    #[Test]
    public function createAlwaysCreatesNewRequestObjects(): void
    {
        $pluginConfiguration = new PluginConfiguration([], 'ABC123', 'posts');

        self::assertNotSame(
            $this->subject->create(RequestType::Posts, $pluginConfiguration),
            $this->subject->create(RequestType::Posts, $pluginConfiguration),
        );
    }

    #[Test]
    public function createdRequestIsValid(): void
    {
        self::assertTrue(
            $this->subject->create(RequestType::Posts, new PluginConfiguration([], 'ABC123', 'posts'))->isValidRequest(),
        );
    }
}
