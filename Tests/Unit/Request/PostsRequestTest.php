<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/walls-io-proxy.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\WallsIoProxy\Tests\Unit\Request;

use JWeiland\WallsIoProxy\Request\PostsRequest;
use Nimut\TestingFramework\TestCase\UnitTestCase;
use Prophecy\PhpUnit\ProphecyTrait;

/**
 * Walls IO Request Test
 */
class PostsRequestTest extends UnitTestCase
{
    use ProphecyTrait;

    /**
     * @var PostsRequest
     */
    protected $subject;

    protected function setUp(): void
    {
        $this->subject = new PostsRequest();
    }

    protected function tearDown(): void
    {
        unset(
            $this->subject
        );
        parent::tearDown();
    }

    /**
     * @test
     */
    public function getPathInitiallyReturnsDefaultPath(): void
    {
        self::assertSame(
            '/v1/posts',
            $this->subject->getPath()
        );
    }

    /**
     * @test
     */
    public function setPathSetsPath(): void
    {
        $this->subject->setPath('foo bar');

        self::assertSame(
            'foo bar',
            $this->subject->getPath()
        );
    }

    /**
     * @test
     */
    public function setPathSetsTrimmedPath(): void
    {
        $this->subject->setPath('   trimmed   ');

        self::assertSame(
            'trimmed',
            $this->subject->getPath()
        );
    }

    /**
     * @test
     */
    public function getParametersInitiallyReturnsDefaultParameters(): void
    {
        self::assertSame(
            [
                'fields' => 'id,comment,type',
                'include_inactive' => 0,
                'limit' => 24
            ],
            $this->subject->getParameters()
        );
    }

    /**
     * @test
     */
    public function getParameterReturnsOneParameter(): void
    {
        self::assertSame(
            24,
            $this->subject->getParameter('limit')
        );
    }

    /**
     * @test
     */
    public function hasParameterReturnsTrue(): void
    {
        self::assertTrue(
            $this->subject->hasParameter('limit')
        );
    }

    /**
     * @test
     */
    public function hasParameterReturnsFalse(): void
    {
        self::assertFalse(
            $this->subject->hasParameter('accessToken')
        );
    }

    /**
     * @test
     */
    public function setParametersSetsParameters(): void
    {
        $expectedParameters = [
            'access_token' => '123',
            'languages' => 'de'
        ];
        $this->subject->setParameters($expectedParameters);

        self::assertSame(
            $expectedParameters,
            $this->subject->getParameters()
        );
    }

    /**
     * @test
     */
    public function setParametersSetsOnlyAllowedParameters(): void
    {
        $this->subject->setParameters([
            'accessToken' => '123',
            'languages' => 'de'
        ]);

        self::assertSame(
            [
                'languages' => 'de'
            ],
            $this->subject->getParameters()
        );
    }

    /**
     * @test
     */
    public function addParameterAddsParameter(): void
    {
        $this->subject->addParameter('access_token', '123');

        self::assertSame(
            [
                'fields' => 'id,comment,type',
                'include_inactive' => 0,
                'limit' => 24,
                'access_token' => '123'
            ],
            $this->subject->getParameters()
        );
    }

    /**
     * @test
     */
    public function addParameterWillNotAddDisallowedParameter(): void
    {
        $this->subject->addParameter('accessToken', '123');

        self::assertSame(
            [
                'fields' => 'id,comment,type',
                'include_inactive' => 0,
                'limit' => 24
            ],
            $this->subject->getParameters()
        );
    }

    /**
     * @test
     */
    public function buildUriReturnsUriWithEmptyAccessToken(): void
    {
        self::assertSame(
            'https://api.walls.io/v1/posts?fields=id%2Ccomment%2Ctype&include_inactive=0&limit=24',
            $this->subject->buildUri()
        );
    }

    /**
     * @test
     */
    public function buildUriReturnsUriWithAccessToken(): void
    {
        $this->subject = new PostsRequest();
        $this->subject->setAccessToken('ABC123');

        self::assertSame(
            'https://api.walls.io/v1/posts?fields=id%2Ccomment%2Ctype&include_inactive=0&limit=24&access_token=ABC123',
            $this->subject->buildUri()
        );
    }

    /**
     * @test
     */
    public function buildUriWithAdditionalParameterReturnsUriWithAccessToken(): void
    {
        $this->subject = new PostsRequest();
        $this->subject->setAccessToken('ABC123');

        $this->subject->addParameter('languages', 'de');

        self::assertSame(
            'https://api.walls.io/v1/posts?fields=id%2Ccomment%2Ctype&include_inactive=0&limit=24&access_token=ABC123&languages=de',
            $this->subject->buildUri()
        );
    }

    /**
     * @test
     */
    public function isValidRequestWithInvalidAccessTokenReturnsFalse(): void
    {
        self::assertFalse(
            $this->subject->isValidRequest()
        );
    }

    /**
     * @test
     */
    public function isValidRequestWithAccessTokenReturnsTrue(): void
    {
        $this->subject = new PostsRequest();
        $this->subject->setAccessToken('ABC123');

        self::assertTrue(
            $this->subject->isValidRequest()
        );
    }

    /**
     * @test
     */
    public function setFieldsWithValidFieldsSetsFields(): void
    {
        $this->subject->setFields([
            'id',
            'type',
            'post_link'
        ]);

        self::assertSame(
            'id,type,post_link',
            $this->subject->getParameter('fields')
        );
    }

    /**
     * @test
     */
    public function setFieldsWithInvalidFieldsSomeFields(): void
    {
        $this->subject->setFields([
            'id',
            'instagram',
            'post_link'
        ]);

        self::assertSame(
            'id,post_link',
            $this->subject->getParameter('fields')
        );
    }

    /**
     * @test
     */
    public function setLimitSetsLimit(): void
    {
        $this->subject->setLimit(15);

        self::assertSame(
            15,
            $this->subject->getParameter('limit')
        );
    }

    /**
     * @test
     */
    public function setIncludeInactiveWithTrueSetsIncludeInactive(): void
    {
        $this->subject->setIncludeInactive(true);

        self::assertSame(
            '1',
            $this->subject->getParameter('include_inactive')
        );
    }

    /**
     * @test
     */
    public function setIncludeInactiveWithFalseSetsIncludeInactive(): void
    {
        $this->subject->setIncludeInactive(false);

        self::assertSame(
            '0',
            $this->subject->getParameter('include_inactive')
        );
    }
}
