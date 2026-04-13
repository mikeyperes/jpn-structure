<?php
namespace jpn_structure;

function events_photos_shortcode($atts = array()) {
    // Parse shortcode attributes; default to 7 days.
    $atts = shortcode_atts(array(
        'days' => 7,
    ), $atts, 'events-photos');
    
    $days = intval($atts['days']);
    
    // Retrieve events for the given number of days.
    $events = get_active_events($days);
    if (empty($events)) {
        return '<p>No events found for this period.</p>';
    }
    
    $output = '<div class="events-photos">';
    foreach ($events as $event) {
        // Get the featured image in "medium_large" size.
        $thumb_html = get_the_post_thumbnail($event->ID, 'medium_large');
        // Skip events without a featured image.
        if (!$thumb_html) {
            continue;
        }
        
        // Container defaults.
        $container_class = 'event-photo';
        $container_style = 'margin-bottom: 20px;';
        
        // Check for an ACF "link" field.
        $link = get_field('link', $event->ID);
        if ($link) {
            // Mark container as clickable and set position for overlay.
            $container_class .= ' clickable';
            $container_style .= ' position: relative;';
            
            // Wrap the image in a link that opens in a new tab.
            $thumb_html = '<a href="' . esc_url($link) . '" target="_blank" class="event-photo-link" style="display:block;">'
                        . $thumb_html 
                        . '</a>';
            
            // Append an overlay indicator that appears at the bottom of the image.
            $thumb_html .= '<div class="click-overlay">Click to register</div>';
        }
        
        // Output one image per container.
        $output .= '<div class="' . esc_attr($container_class) . '" style="' . esc_attr($container_style) . '">'
                    . $thumb_html .
                   '</div>';
    }
    $output .= '
    <style>
    .event-photo img{width:100%}
    .event-photo .click-overlay 
    {
          position: absolute;
                bottom: 0;
                left: 0;
                right: 0;
                background: rgba(0, 0, 0, 0.8);
                color: #fff;
                padding: 7px;
                text-align: center;
                font-size: 2.1em;
                pointer-events: none;}
    </div>';
    
    return $output;
}
add_shortcode('events-photos', __NAMESPACE__ . '\\events_photos_shortcode');
