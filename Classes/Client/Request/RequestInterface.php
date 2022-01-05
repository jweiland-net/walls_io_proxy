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
     */
    public function getPath(): string;

    /**
     * Check, if current Request is valid
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
     */
    public function setParameters(array $parameters);

    /**
     * Adds a parameter
     *
     * @param mixed $value
     */
    public function addParameter(string $parameter, $value);

    /**
     * Gets a parameter
     *
     * @return mixed
     */
    public function getParameter(string $parameter);

    /**
     * Check, if parameter exists
     */
    public function hasParameter(string $parameter): bool;

    /**
     * Merge all parameters to build an URI
     */
    public function buildUri(): string;
}
