<?php

/*
 * This file is part of the package jweiland/walls-io-proxy.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\WallsIoProxy\Tests\Unit\Client\Request;

use JWeiland\WallsIoProxy\Client\Request\PostsRequest;
use JWeiland\WallsIoProxy\Configuration\ExtConf;
use Nimut\TestingFramework\TestCase\UnitTestCase;

/**
 * Walls IO Request Test
 */
class PostsRequestTest extends UnitTestCase
{
    /**
     * @var PostsRequest
     */
    protected $subject;

    protected function setUp()
    {
        $this->subject = new PostsRequest();
    }

    protected function tearDown()
    {
        unset(
            $this->subject
        );
        parent::tearDown();
    }

    /**
     * @test
     */
    public function getPathInitiallyReturnsDefaultPath()
    {
        self::assertSame(
            '/api/posts.json',
            $this->subject->getPath()
        );
    }

    /**
     * @test
     */
    public function setPathSetsPath()
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
    public function setPathSetsTrimmedPath()
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
    public function getParametersInitiallyReturnsDefaultParameters()
    {
        self::assertSame(
            [
                'fields' => 'id,comment,type',
                'include_inactive' => 1,
                'limit' => 24
            ],
            $this->subject->getParameters()
        );
    }

    /**
     * @test
     */
    public function getParameterReturnsOneParameter()
    {
        self::assertSame(
            24,
            $this->subject->getParameter('limit')
        );
    }

    /**
     * @test
     */
    public function hasParameterReturnsTrue()
    {
        self::assertTrue(
            $this->subject->hasParameter('limit')
        );
    }

    /**
     * @test
     */
    public function hasParameterReturnsFalse()
    {
        self::assertFalse(
            $this->subject->hasParameter('accessToken')
        );
    }

    /**
     * @test
     */
    public function setParametersSetsParameters()
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
    public function setParametersSetsOnlyAllowedParameters()
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
    public function addParameterAddsParameter()
    {
        $this->subject->addParameter('access_token', '123');

        self::assertSame(
            [
                'fields' => 'id,comment,type',
                'include_inactive' => 1,
                'limit' => 24,
                'access_token' => '123'
            ],
            $this->subject->getParameters()
        );
    }

    /**
     * @test
     */
    public function addParameterWillNotAddDisallowedParameter()
    {
        $this->subject->addParameter('accessToken', '123');

        self::assertSame(
            [
                'fields' => 'id,comment,type',
                'include_inactive' => 1,
                'limit' => 24
            ],
            $this->subject->getParameters()
        );
    }

    /**
     * @test
     */
    public function buildUriReturnsUriWithEmptyAccessToken()
    {
        self::assertSame(
            'https://walls.io/api/posts.json?fields=id%2Ccomment%2Ctype&include_inactive=1&limit=24&access_token=',
            $this->subject->buildUri()
        );
    }

    /**
     * @test
     */
    public function buildUriReturnsUriWithAccessToken()
    {
        $extConf = new ExtConf();
        $extConf->setAccessToken('123');
        $this->subject = new PostsRequest($extConf);

        self::assertSame(
            'https://walls.io/api/posts.json?fields=id%2Ccomment%2Ctype&include_inactive=1&limit=24&access_token=123',
            $this->subject->buildUri()
        );
    }

    /**
     * @test
     */
    public function buildUriWithAdditionalParameterReturnsUriWithAccessToken()
    {
        $extConf = new ExtConf();
        $extConf->setAccessToken('123');
        $this->subject = new PostsRequest($extConf);

        $this->subject->addParameter('languages', 'de');

        self::assertSame(
            'https://walls.io/api/posts.json?fields=id%2Ccomment%2Ctype&include_inactive=1&limit=24&languages=de&access_token=123',
            $this->subject->buildUri()
        );
    }

    /**
     * @test
     */
    public function isValidRequestWithInvalidAccessTokenReturnsFalse()
    {
        self::assertFalse(
            $this->subject->isValidRequest()
        );
    }

    /**
     * @test
     */
    public function isValidRequestWithAccessTokenReturnsTrue()
    {
        $extConf = new ExtConf();
        $extConf->setAccessToken('123');
        $this->subject = new PostsRequest($extConf);

        self::assertTrue(
            $this->subject->isValidRequest()
        );
    }

    /**
     * @test
     */
    public function setFieldsWithValidFieldsSetsFields()
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
    public function setFieldsWithInvalidFieldsSomeFields()
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
    public function setLimitSetsLimit()
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
    public function setIncludeInactiveWithTrueSetsIncludeInactive()
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
    public function setIncludeInactiveWithFalseSetsIncludeInactive()
    {
        $this->subject->setIncludeInactive(false);

        self::assertSame(
            '0',
            $this->subject->getParameter('include_inactive')
        );
    }
}
