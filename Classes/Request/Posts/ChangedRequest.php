<?php

declare(strict_types=1);

/*
 * This file is part of the package jweiland/walls-io-prox.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

namespace JWeiland\WallsIoProxy\Request\Posts;

use JWeiland\WallsIoProxy\Request\AbstractRequest;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Walls.io Request to retrieve changed posts
 *
 * @link https://github.com/DieSocialisten/Walls.io-API-Docs/blob/master/endpoints/GET_posts-changed.md
 */
class ChangedRequest extends AbstractRequest
{
    /**
     * @var string
     */
    protected $path = '/v1/posts/changed';

    protected $parameters = [
        'fields' => 'id,comment,type',
        'include_inactive' => 0,
        'limit' => 24,
    ];

    /**
     * @var array
     */
    protected $allowedParameters = [
        'access_token' => 1,
        'since' => 1,
        'limit' => 1,
        'fields' => 1,
        'types' => 1,
        'media_types' => 1,
        'languages' => 1,
        'highlighted_only' => 1,
        'include_inactive' => 1,
        'include_source' => 1,
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
        'longitude',
    ];

    /**
     * The accessToken to allow retrieving the posts from wallsIO
     *
     * @param string $accessToken
     */
    public function setAccessToken(string $accessToken): void
    {
        $this->addParameter(
            'access_token',
            $accessToken
        );
    }

    /**
     * This property is needed for pagination. Initially filled by current time(). For further records
     * use current_time property of last fetched records.
     *
     * @param int $since
     */
    public function setSince(int $since): void
    {
        $this->addParameter(
            'since',
            $since
        );
    }

    /**
     * A comma-separated list of fields you would like to receive for each post.
     *
     * @param array $fields
     */
    public function setFields(array $fields): void
    {
        $this->addParameter(
            'fields',
            implode(',', array_intersect($this->allowedFields, $fields))
        );
    }

    /**
     * Set a maximum of records to load. walls.io limits this value to 1000
     *
     * @param int $limit
     */
    public function setLimit(int $limit): void
    {
        $this->addParameter(
            'limit',
            MathUtility::forceIntegerInRange($limit, 1, 1000)
        );
    }

    /**
     * Per default, only active posts are returned.
     * If you want to receive all posts, regardless of status, set this to true.
     *
     * @param bool $includeInactive
     */
    public function setIncludeInactive(bool $includeInactive): void
    {
        $this->addParameter(
            'include_inactive',
            $includeInactive ? '1' : '0'
        );
    }
}
