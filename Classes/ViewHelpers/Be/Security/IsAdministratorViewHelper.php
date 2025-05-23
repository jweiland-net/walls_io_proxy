<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/walls-io-proxy.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\WallsIoProxy\ViewHelpers\Be\Security;

use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\Exception\AspectNotFoundException;
use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractConditionViewHelper;

/**
 * Check, if current logged-in user is Administrator
 */
class IsAdministratorViewHelper extends AbstractConditionViewHelper
{
    /**
     * This method decides, if the current logged-in user is an administrator
     *
     * @return bool Returns true if the user is an administrator, otherwise false.
     */
    public static function verdict(array $arguments, RenderingContextInterface $renderingContext): bool
    {
        return self::isBeUserAdmin();
    }

    private static function isBeUserAdmin(): bool
    {
        $context = self::getContext();
        try {
            $context = self::getContext();
            if ($context->hasAspect('backend.user')) {
                $userAspect = $context->getAspect('backend.user');
                if ($userAspect instanceof UserAspect) {
                    return $userAspect->isAdmin();
                }
            }
        } catch (AspectNotFoundException $aspectNotFoundException) {
        }

        return false;
    }

    private static function getContext(): Context
    {
        return GeneralUtility::makeInstance(Context::class);
    }
}
