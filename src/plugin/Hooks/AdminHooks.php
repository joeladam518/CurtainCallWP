<?php

namespace CurtainCallWP\Hooks;

use CurtainCallWP\CurtainCall;
use CurtainCallWP\Helpers\CurtainCallHelper;
use CurtainCallWP\PostTypes\Production;
use Throwable;
use WP_Post;

/**
 * Class AdminController
 * @package CurtainCallWP\Controllers
 */
class AdminHooks
{
    /** @var string  */
    protected $assets_url;
    
    /** @var string  */
    protected $assets_path;
    
    /**
     * AdminHookController constructor.
     */
    public function __construct()
    {
        $this->assets_url = ccwpAssetsUrl() . 'admin/';
        $this->assets_path = ccwpAssetsPath() . 'admin/';
    }
    
    /**
     * Register the stylesheets for the admin area.
     * @return void
     */
    public function enqueueStyles()
    {
        $handle = CurtainCall::PLUGIN_NAME . '_admin';
        $admin_css_url = $this->assets_url . 'curtain-call-wp-admin.css';
        $version = (CCWP_DEBUG) ? rand() : CurtainCall::PLUGIN_VERSION;
        wp_enqueue_style($handle, $admin_css_url, array(), $version, 'all');
    }
    
    /**
     * Register the JavaScript for the admin area.
     * @return void
     */
    public function enqueueScripts()
    {
        $handle = CurtainCall::PLUGIN_NAME . '_admin';
        $admin_js_url = $this->assets_url . 'curtain-call-wp-admin.js';
        $version = (CCWP_DEBUG) ? rand() : CurtainCall::PLUGIN_VERSION;
        wp_enqueue_script($handle, $admin_js_url, array('jquery'), $version, true);
    }
    
    //  ----------------------------------------------------------------------------------------------------------------
    //  Global Functions
    //  ----------------------------------------------------------------------------------------------------------------
    
    /**
     * @param array $data
     * @param array $postarr
     * @return array
     */
    public function setPostTitleOnPostSave(array $data, array $postarr)
    {
        // if this is not a save from the edit post page dont do anything
        if (! isset($_POST['action'])
        ||  $_POST['action'] !== 'editpost'
        || ($data['post_type'] !== 'ccwp_production' && $data['post_type'] !== 'ccwp_cast_and_crew')
        ) {
            return $data;
        }
        
        $title_arr = [];
        if ($data['post_type'] === 'ccwp_production') {
            
            if (isset($_POST['ccwp_production_name'])) {
                $title_arr[] = sanitize_text_field($_POST['ccwp_production_name']);
            }
            
            /**
             * TODO: If, attached to a single season (post term), append the Season to the post title.
             *       Else, attach the start_date year to the production title.
            **/
            
            if (isset($_POST['ccwp_date_start'])) {
                if ( ! empty($title_arr)) {
                    $title_arr[] = '-';
                }
                
                $date_start = CurtainCallHelper::toCarbon(
                    sanitize_text_field($_POST['ccwp_date_start'])
                );
                
                if ($date_start) {
                    $title_arr[] = $date_start->format('Y');
                }
            }
            
            if (empty($title_arr)) {
                $title_arr[] = 'Untitled Curtain Call Production';
            }
        } else if ($data['post_type'] === 'ccwp_cast_and_crew') {
            if (isset($_POST['ccwp_name_first'])) {
                $title_arr[] = sanitize_text_field($_POST['ccwp_name_first']);
            }
            
            if (isset($_POST['ccwp_name_last'])) {
                $title_arr[] = sanitize_text_field($_POST['ccwp_name_last']);
            }
            
            if (empty($title_arr)) {
                $title_arr[] = 'Untitled Curtain Call Cast/Crew';
            }
        }
        
        if ( ! empty($title_arr)) {
            $title = implode(' ', $title_arr);
            $title = preg_replace('~\s\s+~', ' ', $title);
            
            $data['post_name']  = sanitize_title($title);
            $data['post_title'] = $title;
        }
        
        return $data;
    }
    
    //  ----------------------------------------------------------------------------------------------------------------
    //  Production Functions
    //  ----------------------------------------------------------------------------------------------------------------
    
    /**
     * @return void
     */
    public function addProductionPostMetaBoxes()
    {
        add_meta_box(
            'ccwp_add_cast_and_crew_to_production', // Unique ID
            __('Add Cast And Crew to Production', 'curtain-call-wp'), // Box title
            array($this, 'renderAddCastAndCrewMetaBox'), // Content callback
            'ccwp_production', // Post type
            'normal', // Context: (normal, side, advanced)
            'high' // Priority: (high, low)
            //[ 'example' => 'arguments you can place into the meta_box renderer' ]
        );
        
        add_meta_box(
            'ccwp_production_details', // Unique ID
            __('Production Details', 'curtain-call-wp'), // Box title
            array($this, 'renderProductionDetailsMetaBox'), // Content callback
            'ccwp_production', // Post type
            'normal', // Context: (normal, side, advanced)
            'high' // Priority: (high, low)
            //[ example' => 'arguments you can place into the meta_box renderer' ]
        );
    }
    
    /**
     * @param $post
     * @param $metabox
     * @return void
     * @throws Throwable
     */
    public function renderAddCastAndCrewMetaBox($post, $metabox)
    {
        /** @var Production $production */
        $production = Production::make($post);
        
        // Get all castcrew by id and name
        $all_cast_crew_names = $production->getCastCrewNames();
        
        // Get all related cast and crew members to this production
        $production_cast_and_crew_members = $production->getCastAndCrew();
        $cast_members = [];
        $crew_members = [];
        
        // Sort them into cast then crew members
        if (!empty($production_cast_and_crew_members) && is_array($production_cast_and_crew_members)) {
            $cast_members = array_filter($production_cast_and_crew_members, function ($castcrew_member) {
                return ($castcrew_member->ccwp_join->type == 'cast');
            });
            $cast_members = array_values($cast_members);
            
            $crew_members = array_filter($production_cast_and_crew_members, function ($castcrew_member) {
                return ($castcrew_member->ccwp_join->type == 'crew');
            });
            $crew_members = array_values($crew_members);
        }
        
        ccwpView('admin/metaboxes/production-add-cast-and-crew.php', [
            'wp_nonce' => wp_nonce_field(basename(__FILE__), 'ccwp_add_cast_and_crew_to_production_box_nonce', true, false),
            'post' => $post,
            'metabox' => $metabox,
            'all_cast_crew_names' => $all_cast_crew_names,
            'cast_members' => $cast_members,
            'crew_members' => $crew_members,
        ])->render();
    }
    
    /**
     * @param WP_Post $post
     * @param $metabox
     * @return void
     * @throws Throwable
     */
    public function renderProductionDetailsMetaBox($post, $metabox)
    {
        $date_start = get_post_meta($post->ID, '_ccwp_production_date_start', true);
        $date_start = CurtainCallHelper::convertDate($date_start, 'm/d/Y', '');
        
        $date_end = get_post_meta($post->ID, '_ccwp_production_date_end', true);
        $date_end = CurtainCallHelper::convertDate($date_end, 'm/d/Y', '');

        ccwpView('admin/metaboxes/production-details.php', [
            'wp_nonce'   => wp_nonce_field(basename(__FILE__), 'ccwp_production_details_box_nonce', true, false),
            'post'       => $post,
            'metabox'    => $metabox,
            'name'       => get_post_meta($post->ID, '_ccwp_production_name', true),
            'date_start' => $date_start,
            'date_end'   => $date_end,
            'show_times' => get_post_meta($post->ID, '_ccwp_production_show_times', true),
            'ticket_url' => get_post_meta($post->ID, '_ccwp_production_ticket_url', true),
            'venue'      => get_post_meta($post->ID, '_ccwp_production_venue', true),
            'press'      => get_post_meta($post->ID, '_ccwp_production_press', true),
        ])->render();
    }
    
    /**
     * @param int $post_id
     * @return void
     * @throws Throwable
     */
    public function saveProductionPostCastAndCrew($post_id)
    {
        # Verify meta box nonce
        if (
            ! isset($_POST['ccwp_add_cast_and_crew_to_production_box_nonce'])
        ||  ! wp_verify_nonce($_POST['ccwp_add_cast_and_crew_to_production_box_nonce'], basename(__FILE__))
        ) {
            return;
        }
        
        # Return if autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
    
        # Check the user's permissions.
        if (! current_user_can('edit_post', $post_id)) {
            return;
        }
        
        # Store custom fields values
        $production_cast = ! empty($_POST['ccwp_add_cast_to_production']) ? $_POST['ccwp_add_cast_to_production'] : [];
        $production_crew = ! empty($_POST['ccwp_add_crew_to_production']) ? $_POST['ccwp_add_crew_to_production'] : [];
        
        $production = Production::find($post_id); //
        $production->saveCastAndCrew('cast', $production_cast);
        $production->saveCastAndCrew('crew', $production_crew);
    }
    
    /**
     * @param int $post_id
     * @return void
     */
    public function saveProductionPostDetails($post_id)
    {
        # Verify meta box nonce
        if (
            ! isset($_POST['ccwp_production_details_box_nonce'])
        ||  ! wp_verify_nonce($_POST['ccwp_production_details_box_nonce'], basename(__FILE__))
        ) {
            return;
        }
        
        # Return if autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        
        # Check the user's permissions.
        if (! current_user_can('edit_post', $post_id)) {
            return;
        }
        
        # Store custom fields values
        if (! empty($_REQUEST['ccwp_production_name'])) {
            update_post_meta($post_id, '_ccwp_production_name', sanitize_text_field($_POST['ccwp_production_name']));
        } else {
            delete_post_meta($post_id, '_ccwp_production_name');
        }
        
        if (! empty($_REQUEST['ccwp_date_start'])) {
            $ccwp_date_start = sanitize_text_field($_POST['ccwp_date_start']);
            $ccwp_date_start = CurtainCallHelper::convertDate($ccwp_date_start, 'Y-m-d');
            update_post_meta($post_id, '_ccwp_production_date_start', $ccwp_date_start);
        } else {
            delete_post_meta($post_id, '_ccwp_production_date_start');
        }
        
        if (! empty($_REQUEST['ccwp_date_end'])) {
            $ccwp_date_end = sanitize_text_field($_POST['ccwp_date_end']);
            $ccwp_date_end = CurtainCallHelper::convertDate($ccwp_date_end, 'Y-m-d');
            update_post_meta($post_id, '_ccwp_production_date_end', $ccwp_date_end);
        } else {
            delete_post_meta($post_id, '_ccwp_production_date_end');
        }
        
        if (! empty($_REQUEST['ccwp_show_times'])) {
            update_post_meta($post_id, '_ccwp_production_show_times', sanitize_text_field($_POST['ccwp_show_times']));
        } else {
            delete_post_meta($post_id, '_ccwp_production_show_times');
        }
        
        if (! empty($_REQUEST['ccwp_ticket_url'])) {
            update_post_meta($post_id, '_ccwp_production_ticket_url', sanitize_text_field($_POST['ccwp_ticket_url']));
        } else {
            delete_post_meta($post_id, '_ccwp_production_ticket_url');
        }
        
        if (! empty($_REQUEST['ccwp_venue'])) {
            update_post_meta($post_id, '_ccwp_production_venue', sanitize_text_field($_POST['ccwp_venue']));
        } else {
            delete_post_meta($post_id, '_ccwp_production_venue');
        }
        
        if (! empty($_REQUEST['ccwp_press'])) {
            update_post_meta($post_id, '_ccwp_production_press', sanitize_text_field($_POST['ccwp_press']));
        } else {
            delete_post_meta($post_id, '_ccwp_production_press');
        }
        
        if (empty($title_arr)) {
            $title_arr[] = 'Untitled Curtain Call Production';
        }
    }
    
    //----------------------------------------------------------------------------------------------------------------
    //  Cast And Crew Functions
    //----------------------------------------------------------------------------------------------------------------
    
    /**
     * @return void
     */
    public function addCastAndCrewPostMetaBoxes()
    {
        add_meta_box(
            'ccwp_cast_and_cast_details', // Unique ID
            __('Cast and Crew Details', CCWP_TEXT_DOMAIN), // Box title
            array($this, 'ccwp_cast_and_crew_details_box_html'), // Content callback
            'ccwp_cast_and_crew', // Post type
            'normal', // Context: (normal, side, advanced)
            'high' // Priority: (high, low)
            //[ 'example' => 'arguments you can place into the meta_box renderer' ]
        );
    }
    
    /**
     * @param WP_Post $post
     * @param $metabox
     * @return void
     * @throws Throwable
     */
    public function ccwp_cast_and_crew_details_box_html($post, $metabox)
    {
        $castcrew_birthday = get_post_meta($post->ID, '_ccwp_cast_crew_birthday',true);
        $castcrew_birthday = CurtainCallHelper::convertDate($castcrew_birthday, 'm/d/Y', '');
        
        ccwpView('admin/metaboxes/castcrew-details.php', [
            'wp_nonce'       => wp_nonce_field(basename(__FILE__), 'ccwp_cast_and_crew_details_box_nonce', true, false),
            'post'           => $post,
            'metabox'        => $metabox,
            'name_first'     => get_post_meta($post->ID, '_ccwp_cast_crew_name_first', true),
            'name_last'      => get_post_meta($post->ID, '_ccwp_cast_crew_name_last', true),
            'self_title'     => get_post_meta($post->ID, '_ccwp_cast_crew_self_title', true),
            'birthday'       => $castcrew_birthday,
            'hometown'       => get_post_meta($post->ID, '_ccwp_cast_crew_hometown', true),
            'website_link'   => get_post_meta($post->ID, '_ccwp_cast_crew_website_link', true),
            'facebook_link'  => get_post_meta($post->ID, '_ccwp_cast_crew_facebook_link', true),
            'instagram_link' => get_post_meta($post->ID, '_ccwp_cast_crew_instagram_link', true),
            'twitter_link'   => get_post_meta($post->ID, '_ccwp_cast_crew_twitter_link', true),
            'fun_fact'       => get_post_meta($post->ID, '_ccwp_cast_crew_fun_fact', true),
        ])->render();
    }
    
    public function saveCastAndCrewPostDetails($post_id)
    {
        # Verify meta box nonce
        if (
            ! isset($_POST['ccwp_cast_and_crew_details_box_nonce'])
        ||  ! wp_verify_nonce($_POST['ccwp_cast_and_crew_details_box_nonce'], basename(__FILE__))
        ) {
            return;
        }
        /*
        # Return if autosave
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        */
        # Check the user's permissions.
        if (! current_user_can('edit_post', $post_id)) {
            return;
        }
        
        # Store custom fields values
        
        if (! empty($_REQUEST['ccwp_name_first'])) {
            // update data
            update_post_meta($post_id, '_ccwp_cast_crew_name_first', sanitize_text_field($_POST['ccwp_name_first']));
        } else {
            // delete data
            delete_post_meta($post_id, '_ccwp_cast_crew_name_first');
        }
        
        if (! empty($_REQUEST['ccwp_name_last'])) {
            // update data
            update_post_meta($post_id, '_ccwp_cast_crew_name_last', sanitize_text_field($_POST['ccwp_name_last']));
        } else {
            // delete data
            delete_post_meta($post_id, '_ccwp_cast_crew_name_last');
        }
        
        if (! empty($_REQUEST['ccwp_self_title'])) {
            // update data
            update_post_meta($post_id, '_ccwp_cast_crew_self_title', sanitize_text_field($_POST['ccwp_self_title']));
        } else {
            // delete data
            delete_post_meta($post_id, '_ccwp_cast_crew_self_title');
        }
        
        if (! empty($_REQUEST['ccwp_birthday'])) {
            // update data
            $ccwp_birthday = sanitize_text_field($_POST['ccwp_birthday']);
            $ccwp_birthday = CurtainCallHelper::convertDate($ccwp_birthday, 'Y-m-d');
            update_post_meta($post_id, '_ccwp_cast_crew_birthday', $ccwp_birthday);
        } else {
            // delete data
            delete_post_meta($post_id, '_ccwp_cast_crew_birthday');
        }
        
        if (! empty($_REQUEST['ccwp_hometown'])) {
            // update data
            update_post_meta($post_id, '_ccwp_cast_crew_hometown', sanitize_text_field($_POST['ccwp_hometown']));
        } else {
            // delete data
            delete_post_meta($post_id, '_ccwp_cast_crew_hometown');
        }
        
        if (! empty($_REQUEST['ccwp_website_link'])) {
            // update data
            $link = ccwpStripHttp($_POST['ccwp_website_link']);
            $link = sanitize_text_field($link);
            update_post_meta($post_id, '_ccwp_cast_crew_website_link', $link);
        } else {
            // delete data
            delete_post_meta($post_id, '_ccwp_cast_crew_website_link');
        }
        
        if (! empty($_REQUEST['ccwp_facebook_link'])) {
            // update data
            $link = ccwpStripHttp($_POST['ccwp_facebook_link']);
            $link = sanitize_text_field($link);
            update_post_meta($post_id, '_ccwp_cast_crew_facebook_link', $link);
        } else {
            // delete data
            delete_post_meta($post_id, '_ccwp_cast_crew_facebook_link');
        }
    
        if (! empty($_REQUEST['ccwp_twitter_link'])) {
            // update data
            $link = ccwpStripHttp($_POST['ccwp_twitter_link']);
            $link = sanitize_text_field($link);
            update_post_meta($post_id, '_ccwp_cast_crew_twitter_link', $link);
        } else {
            // delete data
            delete_post_meta($post_id, '_ccwp_cast_crew_twitter_link');
        }
        
        if (! empty($_REQUEST['ccwp_instagram_link'])) {
            // update data
            $link = ccwpStripHttp($_POST['ccwp_instagram_link']);
            $link = sanitize_text_field($link);
            update_post_meta($post_id, '_ccwp_cast_crew_instagram_link', $link);
        } else {
            // delete data
            delete_post_meta($post_id, '_ccwp_cast_crew_instagram_link');
        }
        
        if (! empty($_REQUEST['ccwp_fun_fact'])) {
            // update data
            update_post_meta($post_id, '_ccwp_cast_crew_fun_fact', sanitize_text_field($_POST['ccwp_fun_fact']));
        } else {
            // delete data
            delete_post_meta($post_id, '_ccwp_cast_crew_fun_fact');
        }
        
        if (empty($title_arr)) {
            $title_arr[] = 'Untitled Curtain Call Cast/Crew';
        }
    }
}