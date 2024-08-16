<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/walls-io-proxy.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\WallsIoProxy\ViewHelpers\Be\Security;

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractConditionViewHelper;

/**
 * Check, if current logged-in user is Administrator
 */
class IsAdministratorViewHelper extends AbstractConditionViewHelper
{
    /**
     * This method decides if the current logged-in user is an Administrator.
     *
     * @param mixed $arguments ViewHelper arguments to evaluate the condition for this ViewHelper,
     * allows for flexibility in overriding this method.
     *
     * @return bool Returns true if the user is an administrator, otherwise false.
     */
    protected static function evaluateCondition($arguments = null): bool
    {
        return isset($GLOBALS['BE_USER'])
            && $GLOBALS['BE_USER']->user['uid'] > 0
            && $GLOBALS['BE_USER']->isAdmin();
    }
}
