<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/walls-io-proxy.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\WallsIoProxy\Tests\Unit\Request\Posts;

use JWeiland\WallsIoProxy\Request\Posts\ChangedRequest;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Walls IO Request Test
 */
class ChangedRequestTest extends UnitTestCase
{
    /**
     * @var ChangedRequest
     */
    protected $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->subject = new ChangedRequest();
    }

    protected function tearDown(): void
    {
        unset(
            $this->subject
        );
        parent::tearDown();
    }

    #[Test]
    public function getPathInitiallyReturnsDefaultPath(): void
    {
        self::assertSame(
            '/v1/posts/changed',
            $this->subject->getPath()
        );
    }

    #[Test]
    public function setPathSetsPath(): void
    {
        $this->subject->setPath('foo bar');

        self::assertSame(
            'foo bar',
            $this->subject->getPath()
        );
    }

    #[Test]
    public function setPathSetsTrimmedPath(): void
    {
        $this->subject->setPath('   trimmed   ');

        self::assertSame(
            'trimmed',
            $this->subject->getPath()
        );
    }

    #[Test]
    public function getParametersInitiallyReturnsDefaultParameters(): void
    {
        self::assertSame(
            [
                'fields' => 'id,comment,type',
                'include_inactive' => 0,
                'limit' => 24,
            ],
            $this->subject->getParameters()
        );
    }

    #[Test]
    public function getParameterReturnsOneParameter(): void
    {
        self::assertSame(
            24,
            $this->subject->getParameter('limit')
        );
    }

    #[Test]
    public function hasParameterReturnsTrue(): void
    {
        self::assertTrue(
            $this->subject->hasParameter('limit')
        );
    }

    #[Test]
    public function hasParameterReturnsFalse(): void
    {
        self::assertFalse(
            $this->subject->hasParameter('accessToken')
        );
    }

    #[Test]
    public function setParametersSetsParameters(): void
    {
        $expectedParameters = [
            'access_token' => '123',
            'languages' => 'de',
        ];
        $this->subject->setParameters($expectedParameters);

        self::assertSame(
            $expectedParameters,
            $this->subject->getParameters()
        );
    }

    #[Test]
    public function setParametersSetsOnlyAllowedParameters(): void
    {
        $this->subject->setParameters([
            'accessToken' => '123',
            'languages' => 'de',
        ]);

        self::assertSame(
            [
                'languages' => 'de',
            ],
            $this->subject->getParameters()
        );
    }

    #[Test]
    public function addParameterAddsParameter(): void
    {
        $this->subject->addParameter('access_token', '123');

        self::assertSame(
            [
                'fields' => 'id,comment,type',
                'include_inactive' => 0,
                'limit' => 24,
                'access_token' => '123',
            ],
            $this->subject->getParameters()
        );
    }

    #[Test]
    public function addParameterWillNotAddDisallowedParameter(): void
    {
        $this->subject->addParameter('accessToken', '123');

        self::assertSame(
            [
                'fields' => 'id,comment,type',
                'include_inactive' => 0,
                'limit' => 24,
            ],
            $this->subject->getParameters()
        );
    }

    #[Test]
    public function buildUriReturnsUriWithEmptyAccessToken(): void
    {
        self::assertSame(
            'https://api.walls.io/v1/posts/changed?fields=id%2Ccomment%2Ctype&include_inactive=0&limit=24',
            $this->subject->buildUri()
        );
    }

    #[Test]
    public function buildUriReturnsUriWithAccessToken(): void
    {
        $this->subject = new ChangedRequest();
        $this->subject->setAccessToken('ABC123');

        self::assertSame(
            'https://api.walls.io/v1/posts/changed?fields=id%2Ccomment%2Ctype&include_inactive=0&limit=24&access_token=ABC123',
            $this->subject->buildUri()
        );
    }

    #[Test]
    public function buildUriWithAdditionalParameterReturnsUriWithAccessToken(): void
    {
        $this->subject = new ChangedRequest();
        $this->subject->setAccessToken('ABC123');

        $this->subject->addParameter('languages', 'de');

        self::assertSame(
            'https://api.walls.io/v1/posts/changed?fields=id%2Ccomment%2Ctype&include_inactive=0&limit=24&access_token=ABC123&languages=de',
            $this->subject->buildUri()
        );
    }

    #[Test]
    public function isValidRequestWithInvalidAccessTokenReturnsFalse(): void
    {
        self::assertFalse(
            $this->subject->isValidRequest()
        );
    }

    #[Test]
    public function isValidRequestWithAccessTokenReturnsTrue(): void
    {
        $this->subject = new ChangedRequest();
        $this->subject->setAccessToken('ABC123');

        self::assertTrue(
            $this->subject->isValidRequest()
        );
    }

    #[Test]
    public function setFieldsWithValidFieldsSetsFields(): void
    {
        $this->subject->setFields([
            'id',
            'type',
            'post_link',
        ]);

        self::assertSame(
            'id,type,post_link',
            $this->subject->getParameter('fields')
        );
    }

    #[Test]
    public function setFieldsWithInvalidFieldsSomeFields(): void
    {
        $this->subject->setFields([
            'id',
            'instagram',
            'post_link',
        ]);

        self::assertSame(
            'id,post_link',
            $this->subject->getParameter('fields')
        );
    }

    #[Test]
    public function setLimitSetsLimit(): void
    {
        $this->subject->setLimit(15);

        self::assertSame(
            15,
            $this->subject->getParameter('limit')
        );
    }

    #[Test]
    public function setIncludeInactiveWithTrueSetsIncludeInactive(): void
    {
        $this->subject->setIncludeInactive(true);

        self::assertSame(
            '1',
            $this->subject->getParameter('include_inactive')
        );
    }

    #[Test]
    public function setIncludeInactiveWithFalseSetsIncludeInactive(): void
    {
        $this->subject->setIncludeInactive(false);

        self::assertSame(
            '0',
            $this->subject->getParameter('include_inactive')
        );
    }
}
