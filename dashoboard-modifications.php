<?php
namespace jpn_structure;

// Add our custom column only for the 'profile' post type.
add_filter( 'manage_edit-profile_columns', __NAMESPACE__ . '\\add_profile_featured_image_column' );
add_action( 'manage_profile_posts_custom_column', __NAMESPACE__ . '\\display_profile_featured_image_column', 10, 2 );

// Add Featured Image column to the Profile post type list view
function add_profile_featured_image_column( $columns ) {
    $new_columns = array();
    foreach ( $columns as $key => $value ) {
        $new_columns[ $key ] = $value;
        if ( 'cb' === $key ) {
            $new_columns['featured_image'] = __( 'Featured Image', 'your-textdomain' );
        }
    }
    return $new_columns;
}

// Display the featured image in the new column
function display_profile_featured_image_column( $column, $post_id ) {
    // Extra safety: explicitly check for the 'profile' post type.
    if ( 'profile' !== get_post_type( $post_id ) ) {
        return;
    }
    
    if ( 'featured_image' === $column ) {
        if ( has_post_thumbnail( $post_id ) ) {
            // Output a small 50x50 pixel thumbnail
            echo get_the_post_thumbnail( $post_id, array( 50, 50 ) );
        } else {
            echo __( 'No Featured Image', 'your-textdomain' );
        }
    }
}
?>
