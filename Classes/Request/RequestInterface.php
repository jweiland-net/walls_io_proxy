<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/walls-io-proxy.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\WallsIoProxy\Request;

/**
 * Interface for walls.io requests
 */
interface RequestInterface
{
    public function getPath(): string;

    public function isValidRequest(): bool;

    public function getParameters(): array;

    public function setParameters(array $parameters): void;

    /**
     * @param mixed $value
     */
    public function addParameter(string $parameter, $value): void;

    /**
     * @return mixed
     */
    public function getParameter(string $parameter);

    public function hasParameter(string $parameter): bool;

    /**
     * Merge all parameters to build an URI
     */
    public function buildUri(): string;
}
