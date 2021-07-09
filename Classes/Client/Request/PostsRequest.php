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
 * Walls.io Request to retrieve Posts
 *
 * @link https://github.com/DieSocialisten/Walls.io-API-Docs#get-apipostsjson
 */
class PostsRequest extends AbstractRequest
{
    /**
     * @var string
     */
    protected $path = '/api/posts.json';

    protected $parameters = [
        'fields' => 'id,comment,type',
        'include_inactive' => 1,
        'limit' => 24
    ];

    /**
     * @var array
     */
    protected $allowedParameters = [
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
        'include_source' => 1
    ];

    /**
     * @var array
     */
    protected $allowedFields = [
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
        'longitude'
    ];

    /**
     * A comma-separated list of fields you would like to receive for each post.
     *
     * @param array $fields
     */
    public function setFields(array $fields)
    {
        $this->addParameter(
            'fields',
            implode(',', array_intersect($this->allowedFields, $fields))
        );
    }

    /**
     * A comma-separated list of fields you would like to receive for each post.
     *
     * @param int $limit
     */
    public function setLimit(int $limit)
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
     *
     * @param string $postId
     */
    public function setBefore(string $postId)
    {
        // Do not cast to INT as $postId can be really huge, which may occurs into problems on 32 bit systems.
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
     *
     * @param bool $includeInactive
     */
    public function setIncludeInactive(bool $includeInactive)
    {
        $this->addParameter(
            'include_inactive',
            $includeInactive ? '1' : '0'
        );
    }
}
