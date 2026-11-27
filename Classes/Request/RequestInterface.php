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

    /**
     * @return array<string, mixed>
     */
    public function getParameters(): array;

    /**
     * @param array<string, mixed> $parameters
     */
    public function setParameters(array $parameters): void;

    public function addParameter(string $parameter, int|string $value): void;

    public function getParameter(string $parameter): int|string;

    public function hasParameter(string $parameter): bool;

    /**
     * Merge all parameters to build an URI
     */
    public function buildUri(): string;

    /**
     * @param array<int, string> $fields
     */
    public function setFields(array $fields): void;

    public function setAccessToken(string $accessToken): void;

    public function setLimit(int $limit): void;
}
