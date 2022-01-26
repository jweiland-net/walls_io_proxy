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

    public function getPath(): string
    {
        return $this->path;
    }

    public function setPath(string $path): void
    {
        $this->path = trim($path);
    }

    public function getParameters(): array
    {
        return $this->parameters;
    }

    public function setParameters(array $parameters): void
    {
        $this->parameters = array_intersect_key($parameters, $this->allowedParameters);
    }

    public function addParameter(string $parameter, $value): void
    {
        if (array_key_exists($parameter, $this->allowedParameters)) {
            $this->parameters[$parameter] = $value;
        }
    }

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
        if (empty($this->getParameter('access_token'))) {
            // @deprecated
            $this->addParameter('access_token', $this->extConf->getAccessToken());
        }

        return sprintf(
            'https://walls.io%s?%s',
            $this->getPath(),
            http_build_query($this->getParameters())
        );
    }

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
