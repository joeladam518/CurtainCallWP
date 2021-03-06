<?php

namespace CurtainCallWP\PostTypes\Traits;

use WP_Post;
use CurtainCallWP\PostTypes\CurtainCallPost;
use CurtainCallWP\Exceptions\PostNotFoundException;
use InvalidArgumentException;
use Throwable;

trait HasWordPressPost
{
    /**
     * @var WP_Post
     */
    protected $wp_post;
    
    /**
     * @var array
     */
    protected $wp_post_attributes = [];
    
    /**
     * @return WP_Post
     */
    public function getPost(): WP_Post
    {
        return $this->wp_post;
    }
    
    protected function isWordPressPostAttribute(string $key)
    {
        return in_array($key, $this->wp_post_attributes);
    }
    
    /**
     * @param int|WP_Post $post
     * @return void
     * @throws PostNotFoundException|InvalidArgumentException
     */
    protected function loadPost($post): void
    {
        if ($post instanceof WP_Post) {
            $this->setPost($post);
        } else if (intval($post) > 0) {
            $post = $this->fetchPost(intval($post));
            $this->setPost($post);
        } else {
            throw new InvalidArgumentException('Can not load $post it must be an int or an instance of WP_Post.');
        }
    
        $this->setWordPressPostAttributes();
    }
    
    /**
     * @param int $post_id
     * @return WP_Post
     * @throws PostNotFoundException
     */
    protected function fetchPost(int $post_id): WP_Post
    {
        global $wpdb;
        $sql = $wpdb->prepare("SELECT * FROM $wpdb->posts WHERE `ID` = %d AND `post_type` = %s LIMIT 1", $post_id, static::POST_TYPE);
        $post = $wpdb->get_row($sql);
    
        if (!$post) {
            throw new PostNotFoundException("Failed to fetch post. id #{$post_id} post_type: ". static::POST_TYPE);
        }
    
        $post = sanitize_post($post, 'raw');
    
        return new WP_Post($post);
    }
    
    /**
     * @param WP_Post $post
     * @return $this
     */
    protected function setPost(WP_Post $post): self
    {
        if ($post->post_type !== static::POST_TYPE) {
            throw new InvalidArgumentException('Can\'t set wp_post.  post_type: "'. $post->post_type .'" is incompatible with '. static::class);
        }

        $this->wp_post = $post;
        
        return $this;
    }
    
    /**
     * @return CurtainCallPost
     */
    protected function setWordPressPostAttributes(): self
    {
        $object_vars = get_object_vars($this->wp_post);
        $this->wp_post_attributes = array_keys($object_vars);
        
        return $this;
    }
}