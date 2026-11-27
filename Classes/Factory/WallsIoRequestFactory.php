<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/walls-io-proxy.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\WallsIoProxy\Factory;

use JWeiland\WallsIoProxy\Configuration\PluginConfiguration;
use JWeiland\WallsIoProxy\Request\Posts\ChangedRequest;
use JWeiland\WallsIoProxy\Request\PostsRequest;
use JWeiland\WallsIoProxy\Request\RequestInterface;
use JWeiland\WallsIoProxy\Request\RequestType;
use TYPO3\CMS\Core\Context\Context;

/**
 * Resolves a configured request type to the request object which will be sent to walls.io.
 */
final readonly class WallsIoRequestFactory
{
    private const SECONDS_PER_DAY = 86400;

    /**
     * Fields to get from the API
     *
     * @var list<string>
     */
    private const ENDPOINT_FIELDS = [
        'id',
        'comment',
        'type',
        'is_crosspost',
        'status',
        'created_timestamp',
        'external_name',
        'external_fullname',
        'external_user_id',
        'external_image',
        'post_image',
        'post_link',
    ];

    public function __construct(
        private Context $context,
    ) {}

    public function create(RequestType $requestType, PluginConfiguration $pluginConfiguration): RequestInterface
    {
        $request = match ($requestType) {
            RequestType::Posts => new PostsRequest(),
            RequestType::Changed => $this->createChangedRequest($pluginConfiguration),
        };

        $request->setFields(self::ENDPOINT_FIELDS);
        $request->setAccessToken($pluginConfiguration->getAccessToken());
        $request->setLimit($pluginConfiguration->getEntriesToLoad());

        return $request;
    }

    private function createChangedRequest(PluginConfiguration $pluginConfiguration): ChangedRequest
    {
        $request = new ChangedRequest();
        $request->setSince(
            (int)$this->context->getPropertyFromAspect('date', 'timestamp')
            - (self::SECONDS_PER_DAY * $pluginConfiguration->getShowWallsSince()),
        );

        return $request;
    }
}
