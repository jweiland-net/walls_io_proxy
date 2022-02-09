<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/walls-io-proxy.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\WallsIoProxy\Tests\Unit\ViewHelpers\Be;

use Nimut\TestingFramework\TestCase\FunctionalTestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Test IsAdministratorViewHelper
 */
class IsAdministratorViewHelperTest extends FunctionalTestCase
{
    use ProphecyTrait;

    /**
     * @var array
     */
    protected $testExtensionsToLoad = [
        'typo3conf/ext/walls_io_proxy'
    ];

    /**
     * @test
     */
    public function beUserIsAdministrator(): void
    {
        /** @var BackendUserAuthentication|ObjectProphecy $backendUserAuthenticationProphecy */
        $backendUserAuthenticationProphecy = $this->prophesize(BackendUserAuthentication::class);
        $backendUserAuthenticationProphecy
            ->isAdmin()
            ->shouldBeCalled()
            ->willReturn(true);

        $GLOBALS['BE_USER'] = $backendUserAuthenticationProphecy->reveal();
        $GLOBALS['BE_USER']->user = ['uid' => 1];

        $view = new StandaloneView();
        $view->setTemplateSource('
            <html lang="en"
                  xmlns:f="http://typo3.org/ns/TYPO3/CMS/Fluid/ViewHelpers"
                  xmlns:w="http://typo3.org/ns/JWeiland/WallsIoProxy/ViewHelpers"
                  data-namespace-typo3-fluid="true">
                <w:be.security.isAdministrator>
                    <f:then>IS ADMIN</f:then>
                    <f:else>IS NOT ADMIN</f:else>
                </w:be.security.isAdministrator>
            </html>
        ');

        self::assertStringContainsString(
            'IS ADMIN',
            $view->render()
        );
    }

    /**
     * @test
     */
    public function beUserIsNotAdministrator(): void
    {
        /** @var BackendUserAuthentication|ObjectProphecy $backendUserAuthenticationProphecy */
        $backendUserAuthenticationProphecy = $this->prophesize(BackendUserAuthentication::class);
        $backendUserAuthenticationProphecy
            ->isAdmin()
            ->shouldBeCalled()
            ->willReturn(false);

        $GLOBALS['BE_USER'] = $backendUserAuthenticationProphecy->reveal();
        $GLOBALS['BE_USER']->user = ['uid' => 1];

        $view = new StandaloneView();
        $view->setTemplateSource('
            <html lang="en"
                  xmlns:f="http://typo3.org/ns/TYPO3/CMS/Fluid/ViewHelpers"
                  xmlns:w="http://typo3.org/ns/JWeiland/WallsIoProxy/ViewHelpers"
                  data-namespace-typo3-fluid="true">
                <w:be.security.isAdministrator>
                    <f:then>IS ADMIN</f:then>
                    <f:else>IS NOT ADMIN</f:else>
                </w:be.security.isAdministrator>
            </html>
        ');

        self::assertStringContainsString(
            'IS NOT ADMIN',
            $view->render()
        );
    }

    /**
     * @test
     */
    public function beUserHasNoUserRecord(): void
    {
        /** @var BackendUserAuthentication|ObjectProphecy $backendUserAuthenticationProphecy */
        $backendUserAuthenticationProphecy = $this->prophesize(BackendUserAuthentication::class);

        $GLOBALS['BE_USER'] = $backendUserAuthenticationProphecy->reveal();

        $view = new StandaloneView();
        $view->setTemplateSource('
            <html lang="en"
                  xmlns:f="http://typo3.org/ns/TYPO3/CMS/Fluid/ViewHelpers"
                  xmlns:w="http://typo3.org/ns/JWeiland/WallsIoProxy/ViewHelpers"
                  data-namespace-typo3-fluid="true">
                <w:be.security.isAdministrator>
                    <f:then>IS ADMIN</f:then>
                    <f:else>IS NOT ADMIN</f:else>
                </w:be.security.isAdministrator>
            </html>
        ');

        self::assertStringContainsString(
            'IS NOT ADMIN',
            $view->render()
        );
    }

    /**
     * @test
     */
    public function beUserIsNotSet(): void
    {
        $view = new StandaloneView();
        $view->setTemplateSource('
            <html lang="en"
                  xmlns:f="http://typo3.org/ns/TYPO3/CMS/Fluid/ViewHelpers"
                  xmlns:w="http://typo3.org/ns/JWeiland/WallsIoProxy/ViewHelpers"
                  data-namespace-typo3-fluid="true">
                <w:be.security.isAdministrator>
                    <f:then>IS ADMIN</f:then>
                    <f:else>IS NOT ADMIN</f:else>
                </w:be.security.isAdministrator>
            </html>
        ');

        self::assertStringContainsString(
            'IS NOT ADMIN',
            $view->render()
        );
    }
}
