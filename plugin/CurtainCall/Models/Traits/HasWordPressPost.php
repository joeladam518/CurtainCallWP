<?php

namespace CurtainCall\Models\Traits;

use WP_Post;
use CurtainCall\Exceptions\PostNotFoundException;
use InvalidArgumentException;

trait HasWordPressPost
{
    /** @var WP_Post */
    protected $wp_post;
    /** @var array|string[] */
    protected $wp_post_attributes = [];

    /**
     * Get the WordPress Post
     *
     * @return WP_Post
     */
    public function getPost(): WP_Post
    {
        return $this->wp_post;
    }

    /**
     * Fetch the WordPress Post
     *
     * @param int $postId
     * @return WP_Post
     * @throws PostNotFoundException
     */
    protected function fetchPost(int $postId): WP_Post
    {
        global $wpdb;

        $query = "SELECT * FROM {$wpdb->posts} WHERE `ID` = %d AND `post_type` = %s LIMIT 1";
        $sql = $wpdb->prepare($query, $postId, static::POST_TYPE);
        $post = $wpdb->get_row($sql);

        if (!$post) {
            throw new PostNotFoundException("Failed to fetch post. id #{$postId} post_type: " . static::POST_TYPE);
        }

        $post = sanitize_post($post, 'raw');

        return new WP_Post($post);
    }

    /**
     * Determine if the key is a wp post attribute
     *
     * @param string $key
     * @return bool
     */
    protected function isPostAttribute(string $key): bool
    {
        return in_array($key, $this->wp_post_attributes);
    }

    /**
     * Load the WordPress Post
     *
     * @param int|WP_Post $post
     * @return void
     * @throws PostNotFoundException|InvalidArgumentException
     */
    protected function loadPost($post): void
    {
        if ($post instanceof WP_Post) {
            $this->setPost($post);
        } elseif (is_numeric($post)) {
            $post = $this->fetchPost((int) $post);
            $this->setPost($post);
        } else {
            throw new InvalidArgumentException('Can not load $post it must be an int or an instance of WP_Post.');
        }

        $this->wp_post_attributes = array_keys(get_object_vars($this->wp_post));
    }

    /**
     * Set the WordPress post on the CurtainCallPost
     *
     * @param WP_Post $post
     * @return $this
     */
    protected function setPost(WP_Post $post)
    {
        if ($post->post_type === static::POST_TYPE) {
            $this->wp_post = $post;
        } else {
            throw new InvalidArgumentException(
                sprintf(
                    "Can't set wp_post. \"%s\" is the wrong post type for %s.",
                    $post->post_type,
                    static::class
                )
            );
        }

        return $this;
    }
}
