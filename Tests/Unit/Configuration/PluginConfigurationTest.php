<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/walls-io-proxy.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\WallsIoProxy\Tests\Unit\Configuration;

use JWeiland\WallsIoProxy\Configuration\PluginConfiguration;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test PluginConfiguration
 */
class PluginConfigurationTest extends UnitTestCase
{
    protected PluginConfiguration $subject;

    #[Test]
    public function getRecordInitiallyReturnsEmptyArray(): void
    {
        $this->subject = new PluginConfiguration([]);

        self::assertSame(
            [],
            $this->subject->getRecord()
        );
    }

    #[Test]
    public function getRecordWithRecordWillReturnRecord(): void
    {
        $data = [
            'uid' => 1,
        ];

        $this->subject = new PluginConfiguration([
            'data' => $data,
        ]);

        self::assertSame(
            $data,
            $this->subject->getRecord()
        );
    }

    #[Test]
    public function getAccessTokenInitiallyReturnsEmptyString(): void
    {
        $this->subject = new PluginConfiguration([]);

        self::assertSame(
            '',
            $this->subject->getAccessToken()
        );
    }

    #[Test]
    public function getAccessTokenWithAccessTokenWillReturnAccessToken(): void
    {
        $accessToken = 'ABC123';
        $this->subject = new PluginConfiguration([
            'conf' => [
                'accessToken' => $accessToken,
            ],
        ]);

        self::assertSame(
            $accessToken,
            $this->subject->getAccessToken()
        );
    }

    #[Test]
    public function getRequestTypeInitiallyReturnsEmptyString(): void
    {
        $this->subject = new PluginConfiguration([]);

        self::assertSame(
            '',
            $this->subject->getRequestType()
        );
    }

    #[Test]
    public function getRequestTypeWithRequestTypeWillReturnRequestType(): void
    {
        $requestType = 'RequestClass';

        $this->subject = new PluginConfiguration([
            'conf' => [
                'requestType' => $requestType,
            ],
        ]);

        self::assertSame(
            $requestType,
            $this->subject->getRequestType()
        );
    }

    #[Test]
    public function getEntriesToLoadInitiallyReturns24(): void
    {
        $this->subject = new PluginConfiguration([]);

        self::assertSame(
            24,
            $this->subject->getEntriesToLoad()
        );
    }

    #[Test]
    public function getEntriesToLoadWithEntriesToLoadWillReturnEntriesToLoad(): void
    {
        $entriesToLoad = 34;
        $this->subject = new PluginConfiguration([
            'conf' => [
                'entriesToLoad' => $entriesToLoad,
            ],
        ]);

        self::assertSame(
            $entriesToLoad,
            $this->subject->getEntriesToLoad()
        );
    }

    #[Test]
    public function getEntriesToShowInitiallyReturns8(): void
    {
        $this->subject = new PluginConfiguration([]);

        self::assertSame(
            8,
            $this->subject->getEntriesToShow()
        );
    }

    #[Test]
    public function getEntriesToShowWithEntriesToShowWillReturnEntriesToShow(): void
    {
        $entriesToShow = 12;
        $this->subject = new PluginConfiguration([
            'conf' => [
                'entriesToShow' => $entriesToShow,
            ],
        ]);

        self::assertSame(
            $entriesToShow,
            $this->subject->getEntriesToShow()
        );
    }

    #[Test]
    public function getShowWallsSinceInitiallyReturns365(): void
    {
        $this->subject = new PluginConfiguration([]);

        self::assertSame(
            365,
            $this->subject->getShowWallsSince()
        );
    }

    #[Test]
    public function getShowWallsSinceWithShowWallsSinceWillReturnShowWallsSince(): void
    {
        $showWallsSince = 48;
        $this->subject = new PluginConfiguration([
            'conf' => [
                'showWallsSince' => $showWallsSince,
            ],
        ]);

        self::assertSame(
            $showWallsSince,
            $this->subject->getShowWallsSince()
        );
    }

    #[Test]
    public function getRecordUidInitiallyReturns0(): void
    {
        $this->subject = new PluginConfiguration([]);

        self::assertSame(
            0,
            $this->subject->getRecordUid()
        );
    }

    #[Test]
    public function getRecordUidWithRecordWillReturnRecordUid(): void
    {
        $this->subject = new PluginConfiguration([
            'data' => [
                'uid' => 4,
                'pid' => 6,
            ],
        ]);

        self::assertSame(
            4,
            $this->subject->getRecordUid()
        );
    }
}
