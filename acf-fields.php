<?php
/**
 * JPN Structure — ACF field registrations added programmatically.
 *
 * These augment (not replace) any field groups created via the ACF admin UI.
 * Each group below adds new fields to existing locations:
 *   - Event CPT  -> event_host (User relationship)
 *   - Host User  -> jpn_host_code, jpn_auto_approve, website
 */

namespace jpn_structure;

defined( 'ABSPATH' ) || exit;

add_action( 'acf/init', function () {
    if ( ! function_exists( 'acf_add_local_field_group' ) ) {
        return;
    }

    /* Event CPT - event_host (User relationship, role=host) */
    acf_add_local_field_group( array(
        'key'      => 'group_jpn_event_host_link',
        'title'    => 'Event - Host Link (JPN)',
        'fields'   => array(
            array(
                'key'           => 'field_jpn_event_host',
                'label'         => 'Event Host',
                'name'          => 'event_host',
                'type'          => 'user',
                'instructions'  => 'WordPress user (Host role) responsible for this event.',
                'required'      => 0,
                'role'          => array( 'host' ),
                'allow_null'    => 1,
                'multiple'      => 0,
                'return_format' => 'id',
            ),
        ),
        'location' => array(
            array(
                array(
                    'param'    => 'post_type',
                    'operator' => '==',
                    'value'    => 'event',
                ),
            ),
        ),
        'menu_order'      => 5,
        'position'        => 'side',
        'style'           => 'default',
        'label_placement' => 'top',
        'active'          => true,
        'show_in_rest'    => 1,
    ) );

    /* Host role users - jpn_host_code, jpn_auto_approve, website */
    acf_add_local_field_group( array(
        'key'      => 'group_jpn_host_meta',
        'title'    => 'Host - JPN Meta',
        'fields'   => array(
            array(
                'key'          => 'field_jpn_host_code',
                'label'        => 'JPN Host Code',
                'name'         => 'jpn_host_code',
                'type'         => 'text',
                'instructions' => 'Auto-generated submission code given to the host. Used by /jpn/submit to identify the poster.',
                'required'     => 0,
                'wrapper'      => array( 'width' => '50' ),
                'readonly'     => 1,
            ),
            array(
                'key'           => 'field_jpn_auto_approve',
                'label'         => 'Auto Approve Submissions',
                'name'          => 'jpn_auto_approve',
                'type'          => 'true_false',
                'instructions'  => 'When ON, this host coded submissions publish immediately. When OFF, every submission lands in Pending Events for moderation.',
                'default_value' => 1,
                'ui'            => 1,
                'wrapper'       => array( 'width' => '50' ),
            ),
            array(
                'key'          => 'field_jpn_host_website',
                'label'        => 'Website',
                'name'         => 'website',
                'type'         => 'url',
                'instructions' => 'Host primary website (single URL).',
                'required'     => 0,
            ),
        ),
        'location' => array(
            array(
                array(
                    'param'    => 'user_role',
                    'operator' => '==',
                    'value'    => 'host',
                ),
            ),
        ),
        'menu_order'      => 5,
        'position'        => 'normal',
        'style'           => 'default',
        'label_placement' => 'top',
        'active'          => true,
        'show_in_rest'    => 1,
    ) );
} );
