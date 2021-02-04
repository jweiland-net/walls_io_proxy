<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/walls-io-proxy.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\WallsIoProxy\Client\Request;

/**
 * Interface for walls.io requests
 */
interface RequestInterface
{
    /**
     * Returns the path
     *
     * @return string
     */
    public function getPath(): string;

    /**
     * Check, if current Request is valid
     *
     * @return bool
     */
    public function isValidRequest(): bool;

    /**
     * Returns the parameters
     *
     * @return array $parameters
     */
    public function getParameters(): array;

    /**
     * Sets the parameters
     *
     * @param array $parameters
     */
    public function setParameters(array $parameters);

    /**
     * Adds a parameter
     *
     * @param string $parameter
     * @param mixed $value
     */
    public function addParameter(string $parameter, $value);

    /**
     * Gets a parameter
     *
     * @param string $parameter
     * @return mixed
     */
    public function getParameter(string $parameter);

    /**
     * Check, if parameter exists
     *
     * @param string $parameter
     * @return bool
     */
    public function hasParameter(string $parameter): bool;

    /**
     * Merge all parameters to build an URI
     *
     * @return string
     */
    public function buildUri(): string;
}
