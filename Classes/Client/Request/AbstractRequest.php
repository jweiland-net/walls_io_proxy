<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/walls-io-proxy.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\WallsIoProxy\Client\Request;

use JWeiland\WallsIoProxy\Configuration\ExtConf;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * An abstract request with useful methods for extending request objects
 */
abstract class AbstractRequest implements RequestInterface
{
    /**
     * @var ExtConf
     */
    protected $extConf;

    /**
     * @var string
     */
    protected $path = '';

    /**
     * @var array
     */
    protected $parameters = [];

    public function __construct(ExtConf $extConf = null)
    {
        $this->extConf = $extConf ?? GeneralUtility::makeInstance(ExtConf::class);
    }

    /**
     * Returns the path
     *
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Sets the path
     *
     * @param string $path
     */
    public function setPath(string $path)
    {
        $this->path = trim($path);
    }

    /**
     * Returns the parameters
     *
     * @return array $parameters
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * Sets the parameters
     *
     * @param array $parameters
     */
    public function setParameters(array $parameters)
    {
        $this->parameters = array_intersect_key($parameters, $this->allowedParameters);
    }

    /**
     * Adds a parameter
     *
     * @param string $parameter
     * @param mixed $value
     */
    public function addParameter(string $parameter, $value)
    {
        if (array_key_exists($parameter, $this->allowedParameters)) {
            $this->parameters[$parameter] = $value;
        }
    }

    /**
     * Gets a parameter
     *
     * @param string $parameter
     * @return mixed
     */
    public function getParameter(string $parameter)
    {
        return $this->parameters[$parameter];
    }

    /**
     * Check, if parameter exists
     *
     * @param string $parameter
     * @return bool
     */
    public function hasParameter(string $parameter): bool
    {
        return array_key_exists($parameter, $this->parameters);
    }

    /**
     * Merge all parameters to build an URI
     *
     * @return string
     */
    public function buildUri(): string
    {
        $this->addParameter('access_token', $this->extConf->getAccessToken());

        return sprintf(
            'https://walls.io%s?%s',
            $this->getPath(),
            http_build_query($this->getParameters(), '', '&')
        );
    }

    /**
     * Check, if current Request is valid
     *
     * @return bool
     */
    public function isValidRequest(): bool
    {
        $isValid = true;
        $uri = $this->buildUri();

        if (
            !array_key_exists('access_token', $this->getParameters())
            || empty($this->getParameters()['access_token'])
        ) {
            $isValid = false;
        }

        if (empty($uri)) {
            $isValid = false;
        }

        if (!filter_var($uri, FILTER_VALIDATE_URL)) {
            $isValid = false;
        }

        return $isValid;
    }
}
