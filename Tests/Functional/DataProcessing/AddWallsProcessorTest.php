<?php

/*
 * This file is part of the package jweiland/walls-io-proxy.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\WallsIoProxy\Tests\Unit\DataProcessing;

use JWeiland\WallsIoProxy\DataProcessing\AddWallsProcessor;
use JWeiland\WallsIoProxy\Service\WallsService;
use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * Walls.io Data Processing Test
 */
class AddWallsProcessorTest extends FunctionalTestCase
{
    /**
     * @var AddWallsProcessor
     */
    protected $subject;

    /**
     * Because of using EXT: we have to load our extension before testing
     *
     * @var array
     */
    protected $testExtensionsToLoad = [
        'typo3conf/ext/walls_io_proxy'
    ];

    protected function setUp()
    {
        parent::setUp();
        $this->subject = new AddWallsProcessor();
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
    public function processAddsWallsToProcessedData()
    {
        $cObj = new ContentObjectRenderer();
        $processedData = [
            'data' => [
                'pi_flexform' => file_get_contents(GeneralUtility::getFileAbsFileName(
                    'EXT:walls_io_proxy/Tests/Functional/Fixtures/FlexForm.xml'
                ))
            ]
        ];

        /** @var WallsService|ObjectProphecy $wallsServiceProphecy */
        $wallsServiceProphecy = $this->prophesize(WallsService::class);
        $wallsServiceProphecy
            ->getWallPosts(
                Argument::exact(24)
            )
            ->shouldBeCalled()
            ->willReturn([
                0 => 'Test',
                1 => [
                    'key' => 'value'
                ]
            ]);
        $wallsServiceProphecy
            ->getTargetDirectory()
            ->shouldBeCalled()
            ->willReturn('');
        GeneralUtility::addInstance(WallsService::class, $wallsServiceProphecy->reveal());

        $newProcessedData = $this->subject->process($cObj, [], [], $processedData);

        self::assertArrayHasKey(
            'conf',
            $newProcessedData
        );
        self::assertArrayHasKey(
            'walls',
            $newProcessedData
        );
    }
}
