<?php
declare(strict_types = 1);
namespace JWeiland\WallsIoProxy\ViewHelpers\Be\Security;

/*
 * This file is part of the walls_io_proxy project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use TYPO3Fluid\Fluid\Core\ViewHelper\AbstractConditionViewHelper;

/**
 * Check, if current logged in user is Administrator
 */
class IsAdministratorViewHelper extends AbstractConditionViewHelper
{
    /**
     * This method decides if the current loged in user is Administrator
     *
     * @param array $arguments ViewHelper arguments to evaluate the condition for this ViewHelper, allows for flexiblity in overriding this method.
     * @return bool
     */
    protected static function evaluateCondition($arguments = null)
    {
        return isset($GLOBALS['BE_USER']) && $GLOBALS['BE_USER']->user['uid'] > 0 && $GLOBALS['BE_USER']->isAdmin();
    }
}
