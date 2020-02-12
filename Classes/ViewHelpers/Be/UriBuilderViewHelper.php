<?php
declare(strict_types = 1);
namespace JWeiland\WallsIoProxy\ViewHelpers\Be;

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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;
use TYPO3Fluid\Fluid\Core\ViewHelper\Traits\CompileWithRenderStatic;

/**
 * In case of TYPO3 8 we do not have a be.uri nor a be.link VH.
 * That's why we create our own here.
 * Remove it, if TYPO3 8 compatibility will be removed.
 */
class UriBuilderViewHelper extends AbstractViewHelper
{
    use CompileWithRenderStatic;

    /**
     * Initialize arguments
     */
    public function initializeArguments()
    {
        $this->registerArgument(
            'moduleName',
            'string',
            'The BE module name to generate the URI for',
            true
        );
        $this->registerArgument(
            'urlParameters',
            'array',
            'Additional URI Parameters for the module link',
            false,
            []
        );
    }

    /**
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     * @return string Rendered BE URI
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        return BackendUtility::getModuleUrl(
            $arguments['moduleName'],
            $arguments['urlParameters']
        );
    }
}
