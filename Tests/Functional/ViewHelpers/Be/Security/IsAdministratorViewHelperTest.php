<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/walls-io-proxy.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\WallsIoProxy\Tests\Unit\ViewHelpers\Be;

use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\TestingFramework\Core\Exception;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Test IsAdministratorViewHelper
 */
class IsAdministratorViewHelperTest extends FunctionalTestCase
{
    protected bool $resetSingletonInstances = true;

    protected array $testExtensionsToLoad = [
        'jweiland/walls-io-proxy',
    ];

    /**
     * @test
     * @throws Exception
     */
    public function beUserIsAdministrator(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../../../Fixtures/Database/be_users.csv');

        $backendUserAuthentication = $this->setUpBackendUser(2);
        $GLOBALS['BE_USER'] = $backendUserAuthentication;

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
        /** @var BackendUserAuthentication|MockObject $backendUserAuthenticationMock */
        $backendUserAuthenticationMock = $this->createMock(BackendUserAuthentication::class);
        $backendUserAuthenticationMock
            ->expects(self::never())
            ->method('isAdmin')
            ->willReturn(false);

        $GLOBALS['BE_USER'] = $backendUserAuthenticationMock;
        $GLOBALS['BE_USER']->user = ['uid' => 1];

        // Mock the UserAspect to return the mocked BackendUserAuthentication
        $userAspectMock = $this->getMockBuilder(UserAspect::class)
            ->disableOriginalConstructor()
            ->getMock();
        $userAspectMock
            ->expects(self::never())
            ->method('isAdmin')
            ->willReturn(true);

        // Mock the Context to return the mocked UserAspect
        $contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->getMock();
        $contextMock
            ->expects(self::never())
            ->method('getAspect')
            ->willReturn($userAspectMock);

        // Replace the Context instance in the GeneralUtility
        GeneralUtility::setSingletonInstance(Context::class, $contextMock);

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
        /** @var BackendUserAuthentication|MockObject $backendUserAuthenticationMock */
        $backendUserAuthenticationMock = $this->createMock(BackendUserAuthentication::class);

        $GLOBALS['BE_USER'] = $backendUserAuthenticationMock;

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

    protected function tearDown(): void
    {
        // The tearDown process will reset the Singleton instances because
        // resetSingletonInstances is set to true.
        parent::tearDown();
    }
}
