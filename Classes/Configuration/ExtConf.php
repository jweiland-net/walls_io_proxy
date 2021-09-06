<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/walls-io-proxy.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\WallsIoProxy\Configuration;

use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This class will streamline the values from extension manager configuration
 *
 * @deprecated Will be removed with 5.0.0
 */
class ExtConf implements SingletonInterface
{
    /**
     * @var string
     */
    protected $accessToken = '';

    public function __construct()
    {
        // On a fresh installation this value can be null.
        if (isset($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['walls_io_proxy'])) {
            // get global configuration
            $extConf = unserialize(
                $GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['walls_io_proxy'],
                [
                    'allowed_classes' => false
                ]
            );
            if (is_array($extConf)) {
                // call setter method foreach configuration entry
                foreach ($extConf as $key => $value) {
                    $methodName = 'set' . ucfirst($key);
                    if (method_exists($this, $methodName)) {
                        $this->$methodName($value);
                    }
                }
            }
        }
    }

    public function getAccessToken(): string
    {
        if (!empty($this->accessToken)) {
            GeneralUtility::deprecationLog(
                'Defining AccessToken in Extension Settings is deprecated and will be removed with version 5.0.0. Define it in FlexForm of content record'
            );
        }
        return $this->accessToken;
    }

    public function setAccessToken(string $accessToken)
    {
        $this->accessToken = $accessToken;
    }
}
