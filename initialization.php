<?php namespace jpn_structure;
/*
Plugin Name: Jewish Professional Network Miami - Custom Tools
Description: Event management, WhatsApp notifications, and host tools for JPN Miami.
Version: 1.2.0
Author: Michael Peres
Plugin URI: https://github.com/mikeyperes/jpn-structure
Author URI: https://michaelperes.com
GitHub Plugin URI: https://github.com/mikeyperes/jpn-structure
GitHub Branch: main
*/

defined( 'ABSPATH' ) || exit;
include_once("generic-functions.php");
include_once("dashboard-main.php");
include_once("feature-author-type-fill.php");
include_once("shortcodes.php");
include_once("acf-fields.php");
include_once("dashoboard-modifications.php");


class JPNStructure {

    public function __construct() {
        // Show events on author archive pages (moved from child theme functions.php)
        add_action( 'pre_get_posts', [ $this, 'event_author_archives' ] );
    }

    /**
     * Include 'event' post type in author archive queries.
     * Previously in hello-elementor-child/functions.php.
     */
    public function event_author_archives( $query ) {
        if ( $query->is_author && $query->is_main_query() ) {
            $query->set( 'post_type', array( 'event' ) );
        }
    }
}

add_action( 'acf/init', function() {
	acf_add_options_page( array(
	'page_title' => 'Notifications Dashboard',
	'menu_slug' => 'notifications-dashboard',
	'position' => '',
	'redirect' => false,
) );
} );

// Initialize the plugin
new JPNStructure();