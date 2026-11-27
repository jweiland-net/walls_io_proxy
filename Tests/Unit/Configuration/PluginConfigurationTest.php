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
use JWeiland\WallsIoProxy\Request\RequestType;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test PluginConfiguration
 */
class PluginConfigurationTest extends UnitTestCase
{
    #[Test]
    public function getRecordInitiallyReturnsEmptyArray(): void
    {
        self::assertSame(
            [],
            PluginConfiguration::fromProcessedData([])->getRecord(),
        );
    }

    #[Test]
    public function getRecordWithRecordWillReturnRecord(): void
    {
        $data = [
            'uid' => 1,
        ];

        self::assertSame(
            $data,
            PluginConfiguration::fromProcessedData(['data' => $data])->getRecord(),
        );
    }

    #[Test]
    public function getAccessTokenInitiallyReturnsEmptyString(): void
    {
        self::assertSame(
            '',
            PluginConfiguration::fromProcessedData([])->getAccessToken(),
        );
    }

    #[Test]
    public function getAccessTokenWithAccessTokenWillReturnTrimmedAccessToken(): void
    {
        self::assertSame(
            'ABC123',
            PluginConfiguration::fromProcessedData(['conf' => ['accessToken' => ' ABC123 ']])->getAccessToken(),
        );
    }

    #[Test]
    public function getRequestTypeInitiallyReturnsEmptyString(): void
    {
        self::assertSame(
            '',
            PluginConfiguration::fromProcessedData([])->getRequestType(),
        );
    }

    #[Test]
    public function getRequestTypeWithRequestTypeWillReturnStoredValue(): void
    {
        self::assertSame(
            'RequestClass',
            PluginConfiguration::fromProcessedData(['conf' => ['requestType' => 'RequestClass']])->getRequestType(),
        );
    }

    /**
     * @return array<string, array{string, RequestType|null}>
     */
    public static function requestTypeDataProvider(): array
    {
        return [
            'Empty value' => ['', null],
            'Unknown identifier' => ['foo', null],
            'Identifier of posts' => ['posts', RequestType::Posts],
            'Identifier of changed posts' => ['changed', RequestType::Changed],
            'Legacy class name of posts' => ['JWeiland\WallsIoProxy\Request\PostsRequest', RequestType::Posts],
            'Legacy class name of changed posts' => [
                'JWeiland\WallsIoProxy\Request\Posts\ChangedRequest',
                RequestType::Changed,
            ],
            'Legacy class name with leading backslash' => [
                '\JWeiland\WallsIoProxy\Request\PostsRequest',
                RequestType::Posts,
            ],
            'Other existing class name is not allowed' => [\stdClass::class, null],
            'Class name of own abstract request is not allowed' => [
                'JWeiland\WallsIoProxy\Request\AbstractRequest',
                null,
            ],
        ];
    }

    #[Test]
    #[DataProvider('requestTypeDataProvider')]
    public function resolveRequestTypeWillOnlyResolveKnownTypes(string $storedValue, ?RequestType $expected): void
    {
        self::assertSame(
            $expected,
            PluginConfiguration::fromProcessedData(['conf' => ['requestType' => $storedValue]])->resolveRequestType(),
        );
    }

    #[Test]
    public function getEntriesToLoadInitiallyReturns24(): void
    {
        self::assertSame(
            24,
            PluginConfiguration::fromProcessedData([])->getEntriesToLoad(),
        );
    }

    #[Test]
    public function getEntriesToLoadWithEntriesToLoadWillReturnEntriesToLoad(): void
    {
        self::assertSame(
            34,
            PluginConfiguration::fromProcessedData(['conf' => ['entriesToLoad' => '34']])->getEntriesToLoad(),
        );
    }

    #[Test]
    public function getEntriesToShowInitiallyReturns8(): void
    {
        self::assertSame(
            8,
            PluginConfiguration::fromProcessedData([])->getEntriesToShow(),
        );
    }

    #[Test]
    public function getEntriesToShowWithEntriesToShowWillReturnEntriesToShow(): void
    {
        self::assertSame(
            12,
            PluginConfiguration::fromProcessedData(['conf' => ['entriesToShow' => 12]])->getEntriesToShow(),
        );
    }

    #[Test]
    public function getShowWallsSinceInitiallyReturns365(): void
    {
        self::assertSame(
            365,
            PluginConfiguration::fromProcessedData([])->getShowWallsSince(),
        );
    }

    #[Test]
    public function getShowWallsSinceWithShowWallsSinceWillReturnShowWallsSince(): void
    {
        self::assertSame(
            48,
            PluginConfiguration::fromProcessedData(['conf' => ['showWallsSince' => 48]])->getShowWallsSince(),
        );
    }

    #[Test]
    public function getRecordUidInitiallyReturns0(): void
    {
        self::assertSame(
            0,
            PluginConfiguration::fromProcessedData([])->getRecordUid(),
        );
    }

    #[Test]
    public function getRecordUidWithRecordWillReturnRecordUid(): void
    {
        self::assertSame(
            4,
            PluginConfiguration::fromProcessedData(['data' => ['uid' => 4, 'pid' => 6]])->getRecordUid(),
        );
    }

    #[Test]
    public function processedDataWithInvalidTypesWillBeStreamlined(): void
    {
        $subject = PluginConfiguration::fromProcessedData([
            'data' => 'foo',
            'conf' => ['accessToken' => ['foo'], 'requestType' => new \stdClass()],
        ]);

        self::assertSame([], $subject->getRecord());
        self::assertSame('', $subject->getAccessToken());
        self::assertSame('', $subject->getRequestType());
    }

    #[Test]
    public function constructorAcceptsAllOptions(): void
    {
        $subject = new PluginConfiguration(['uid' => 5], 'ABC', 'posts', 10, 5, 30);

        self::assertSame(5, $subject->getRecordUid());
        self::assertSame('ABC', $subject->getAccessToken());
        self::assertSame(RequestType::Posts, $subject->resolveRequestType());
        self::assertSame(10, $subject->getEntriesToLoad());
        self::assertSame(5, $subject->getEntriesToShow());
        self::assertSame(30, $subject->getShowWallsSince());
        self::assertSame([], $subject->getInvalidFields());
    }

    /**
     * @return array<string, array{PluginConfiguration, list<string>}>
     */
    public static function invalidFieldsDataProvider(): array
    {
        return [
            'Empty configuration' => [new PluginConfiguration(), ['accessToken', 'requestType']],
            'Missing access token' => [new PluginConfiguration([], '', 'posts'), ['accessToken']],
            'Whitespace only access token' => [
                PluginConfiguration::fromProcessedData(['conf' => ['accessToken' => '  ', 'requestType' => 'posts']]),
                ['accessToken'],
            ],
            'Invalid request type' => [new PluginConfiguration([], 'ABC', 'foo'), ['requestType']],
            'No entries to load' => [new PluginConfiguration([], 'ABC', 'posts', 0), ['entriesToLoad']],
            'No entries to show' => [new PluginConfiguration([], 'ABC', 'posts', 24, 0), ['entriesToShow']],
            'Everything is wrong' => [
                new PluginConfiguration([], '', '', 0, 0),
                ['accessToken', 'requestType', 'entriesToLoad', 'entriesToShow'],
            ],
            'Valid configuration' => [new PluginConfiguration([], 'ABC', 'changed'), []],
        ];
    }

    /**
     * @param list<string> $expected
     */
    #[Test]
    #[DataProvider('invalidFieldsDataProvider')]
    public function getInvalidFieldsReturnsNamesOfInvalidFields(PluginConfiguration $subject, array $expected): void
    {
        self::assertSame($expected, $subject->getInvalidFields());
    }
}
