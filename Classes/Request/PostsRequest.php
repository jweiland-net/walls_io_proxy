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
 * Walls.io Request to retrieve Posts
 * SF: This requests returns the records not in expected order. They will be ordered by the date when they are
 * added/imported to walls.io. So, if you add a new service/collection to walls.io all these records get the same
 * time. It may happen, that you will get hundreds of old records at first. Please use v1/posts/changed instead.
 *
 * @link https://github.com/DieSocialisten/Walls.io-API-Docs/blob/master/endpoints/GET_posts.md
 */
class PostsRequest extends AbstractRequest
{
    protected string $path = '/v1/posts';

    /**
     * @var array<string, mixed> $parameters
     */
    protected array $parameters = [
        'fields' => 'id,comment,type',
        'include_inactive' => 0,
        'limit' => 24,
    ];

    /**
     * @var array<string, mixed> $allowedParameters
     */
    protected array $allowedParameters = [
        'access_token' => 1,
        'limit' => 1,
        'after' => 1,
        'before' => 1,
        'fields' => 1,
        'types' => 1,
        'media_types' => 1,
        'languages' => 1,
        'highlighted_only' => 1,
        'include_inactive' => 1,
        'include_source' => 1,
    ];

    /**
     * @var array<string> $allowedFields
     */
    protected array $allowedFields = [
        'id',
        'comment',
        'cta',
        'language',
        'type',
        'external_post_id',
        'external_image',
        'external_name',
        'external_fullname',
        'external_user_id',
        'post_image',
        'post_image_cdn',
        'post_video',
        'post_video_cdn',
        'permalink',
        'post_link',
        'twitter_entities',
        'twitter_retweet',
        'is_crosspost',
        'is_highlighted',
        'status',
        'created',
        'created_timestamp',
        'modified',
        'modified_timestamp',
        'userlink',
        'location',
        'latitude',
        'longitude',
    ];

    /**
     * The accessToken to allow retrieving the posts from wallsIO
     */
    public function setAccessToken(string $accessToken): void
    {
        $this->addParameter(
            'access_token',
            $accessToken
        );
    }

    /**
     * A comma-separated list of fields you would like to receive for each post.
     *
     *  @param array<string, mixed> $fields
     */
    public function setFields(array $fields): void
    {
        $this->addParameter(
            'fields',
            implode(',', array_intersect($this->allowedFields, $fields))
        );
    }

    /**
     * Limit the records to fetch
     */
    public function setLimit(int $limit): void
    {
        $this->addParameter(
            'limit',
            $limit
        );
    }

    /**
     * Set "before" to only get posts before this given post ID.
     * Should be used as offset for pagination.
     *
     * We will add this value to request, if it contains numbers only.
     */
    public function setBefore(string $postId): void
    {
        // Do not cast to INT as $postId can be really huge, which may occurs into problems on 32-bit systems.
        if (preg_match('/\d+/', $postId)) {
            $this->addParameter(
                'before',
                $postId
            );
        }
    }

    /**
     * Per default, only active posts are returned.
     * If you want to receive all posts, regardless of status, set this to true.
     */
    public function setIncludeInactive(bool $includeInactive): void
    {
        $this->addParameter(
            'include_inactive',
            $includeInactive ? '1' : '0'
        );
    }
}
