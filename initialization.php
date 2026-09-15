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
        add_action( 'elementor/query/jpn_home_upcoming_events', [ $this, 'home_upcoming_events_query' ] );
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

    /**
     * Elementor home page query: future event posts only, sorted by event date,
     * with generated ACF related-event children removed from the listing.
     */
    public function home_upcoming_events_query( $query ) {
        if ( ! $query instanceof \WP_Query ) {
            return;
        }

        $query->set( 'post_type', 'event' );
        $query->set( 'post_status', 'publish' );
        $query->set( 'meta_key', 'start_date_timestamp' );
        $query->set( 'orderby', 'meta_value_num' );
        $query->set( 'order', 'ASC' );
        $query->set( 'ignore_sticky_posts', true );
        $query->set(
            'meta_query',
            array(
                array(
                    'key'     => 'start_date_timestamp',
                    'value'   => current_time( 'timestamp' ),
                    'compare' => '>=',
                    'type'    => 'NUMERIC',
                ),
            )
        );

        $excluded = $this->get_related_event_child_ids();
        if ( empty( $excluded ) ) {
            return;
        }

        $existing = (array) $query->get( 'post__not_in' );
        $query->set( 'post__not_in', array_values( array_unique( array_merge( $existing, $excluded ) ) ) );
    }

    private function get_related_event_child_ids() {
        global $wpdb;

        static $excluded = null;

        if ( null !== $excluded ) {
            return $excluded;
        }

        $published_event_ids = $wpdb->get_col(
            "SELECT ID FROM {$wpdb->posts} WHERE post_type = 'event' AND post_status = 'publish'"
        );
        $published_event_ids = array_map( 'intval', $published_event_ids );
        $published_lookup    = array_fill_keys( $published_event_ids, true );

        $related_rows = $wpdb->get_results(
            "SELECT post_id, meta_key, meta_value
            FROM {$wpdb->postmeta}
            WHERE meta_key IN ('related_events', 'related_event_links')",
            ARRAY_A
        );

        $start_rows = $wpdb->get_results(
            "SELECT post_id, meta_value
            FROM {$wpdb->postmeta}
            WHERE meta_key = 'start_date_timestamp'",
            ARRAY_A
        );
        $start_timestamps = array();
        foreach ( $start_rows as $row ) {
            $start_timestamps[ (int) $row['post_id'] ] = (int) $row['meta_value'];
        }

        $graph = array();
        foreach ( $related_rows as $row ) {
            $event_id = (int) $row['post_id'];
            if ( ! isset( $published_lookup[ $event_id ] ) ) {
                continue;
            }

            $graph[ $event_id ] = $graph[ $event_id ] ?? array();

            $related = $this->parse_related_event_ids( $row['meta_value'] );
            foreach ( $related as $related_id ) {
                if ( ! isset( $published_lookup[ $related_id ] ) ) {
                    continue;
                }

                $graph[ $event_id ][] = $related_id;
                $graph[ $related_id ] = $graph[ $related_id ] ?? array();
                $graph[ $related_id ][] = $event_id;
            }
        }

        $excluded = array();
        $visited  = array();
        foreach ( array_keys( $graph ) as $event_id ) {
            if ( isset( $visited[ $event_id ] ) ) {
                continue;
            }

            $component = $this->collect_related_event_component( $event_id, $graph, $visited );
            if ( count( $component ) < 2 ) {
                continue;
            }

            usort(
                $component,
                function ( $left, $right ) use ( $start_timestamps ) {
                    $left_start  = (int) ( $start_timestamps[ $left ] ?? 0 );
                    $right_start = (int) ( $start_timestamps[ $right ] ?? 0 );

                    if ( $left_start === $right_start ) {
                        return $left <=> $right;
                    }

                    if ( $left_start <= 0 ) {
                        return 1;
                    }
                    if ( $right_start <= 0 ) {
                        return -1;
                    }

                    return $left_start <=> $right_start;
                }
            );

            array_shift( $component );
            $excluded = array_merge( $excluded, $component );
        }

        $excluded = array_values( array_unique( array_map( 'intval', $excluded ) ) );

        return $excluded;
    }

    private function parse_related_event_ids( $raw_value ) {
        if ( empty( $raw_value ) ) {
            return array();
        }

        $decoded = json_decode( wp_unslash( (string) $raw_value ), true );
        if ( ! is_array( $decoded ) ) {
            $decoded = maybe_unserialize( $raw_value );
        }
        if ( ! is_array( $decoded ) ) {
            return array();
        }

        $ids = array();
        foreach ( $decoded as $item ) {
            if ( is_array( $item ) && ! empty( $item['post_id'] ) ) {
                $ids[] = (int) $item['post_id'];
            } elseif ( is_numeric( $item ) ) {
                $ids[] = (int) $item;
            }
        }

        return array_values( array_unique( array_filter( $ids ) ) );
    }

    private function collect_related_event_component( $start_id, $graph, &$visited ) {
        $stack     = array( (int) $start_id );
        $component = array();

        while ( $stack ) {
            $event_id = array_pop( $stack );
            if ( isset( $visited[ $event_id ] ) ) {
                continue;
            }

            $visited[ $event_id ] = true;
            $component[]          = $event_id;

            foreach ( $graph[ $event_id ] ?? array() as $related_id ) {
                if ( ! isset( $visited[ $related_id ] ) ) {
                    $stack[] = (int) $related_id;
                }
            }
        }

        return $component;
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
