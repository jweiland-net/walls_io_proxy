<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/walls-io-proxy.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\WallsIoProxy\Request;

use JWeiland\WallsIoProxy\Configuration\PluginConfiguration;

/**
 * An abstract request with useful methods for extending request objects
 */
abstract class AbstractRequest implements RequestInterface
{
    /**
     * @var array<string, mixed>
     */
    protected array $allowedParameters = [];

    /**
     * @var array<string, mixed>
     */
    protected array $parameters = [];

    protected PluginConfiguration $pluginConfiguration;

    protected string $path = '';

    public function getPath(): string
    {
        return $this->path;
    }

    public function setPath(string $path): void
    {
        $this->path = trim($path);
    }

    /**
     * @return array<string, mixed>
     */
    public function getParameters(): array
    {
        return $this->parameters;
    }

    /**
     * @param  array<string, mixed> $parameters
     */
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
     */
    public function hasParameter(string $parameter): bool
    {
        return array_key_exists($parameter, $this->parameters);
    }

    /**
     * Merge all parameters to build an URI
     */
    public function buildUri(): string
    {
        return sprintf(
            'https://api.walls.io%s?%s',
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

        if ($uri === '' || $uri === '0') {
            $isValid = false;
        }

        if (!filter_var($uri, FILTER_VALIDATE_URL)) {
            $isValid = false;
        }

        return $isValid;
    }
}
