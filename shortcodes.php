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
            $thumb_html = '<a href="' . esc_url($link) . '" target="_blank" rel="noopener" class="event-photo-link" style="display:block;">'
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
    $output .= '</div>';
    $output .= '
    <style>
    .event-photo img { width: 100%; }
    .event-photo .click-overlay {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        background: rgba(0, 0, 0, 0.8);
        color: #fff;
        padding: 7px;
        text-align: center;
        font-size: 2.1em;
        pointer-events: none;
    }
    </style>';
    
    return $output;
}
add_shortcode('events-photos', __NAMESPACE__ . '\\events_photos_shortcode');

function get_homepage_upcoming_event() {
    $now = current_time('timestamp');

    $base_args = array(
        'post_type'      => 'event',
        'post_status'    => 'publish',
        'posts_per_page' => 1,
        'meta_key'       => 'start_date_timestamp',
        'orderby'        => 'meta_value_num',
        'order'          => 'ASC',
        'meta_query'     => array(
            array(
                'key'     => 'start_date_timestamp',
                'value'   => $now,
                'compare' => '>=',
                'type'    => 'NUMERIC',
            ),
        ),
    );

    // Featured future events are intentionally preferred. Past featured events are ignored.
    $featured_args = $base_args;
    $featured_args['meta_query'][] = array(
        'key'     => 'featured_event',
        'value'   => '1',
        'compare' => '=',
    );

    $featured = new \WP_Query($featured_args);
    if (!empty($featured->posts)) {
        return $featured->posts[0];
    }

    $upcoming = new \WP_Query($base_args);
    return !empty($upcoming->posts) ? $upcoming->posts[0] : null;
}

function format_event_banner_date($event_id) {
    $timestamp = (int) get_post_meta($event_id, 'start_date_timestamp', true);
    if ($timestamp > 0) {
        return strtoupper(wp_date('D M j', $timestamp));
    }

    $display = (string) get_post_meta($event_id, 'start_date_display', true);
    return $display !== '' ? strtoupper($display) : '';
}

function get_event_banner_venue($event_id, $title) {
    $location = function_exists('get_field') ? get_field('location', $event_id) : '';
    if (is_string($location) && trim($location) !== '') {
        return trim($location);
    }

    $area = function_exists('get_field') ? get_field('area', $event_id) : get_post_meta($event_id, 'area', true);
    if ($area instanceof \WP_Term) {
        return $area->name;
    }
    if (is_numeric($area)) {
        $term = get_term((int) $area, 'area');
        if ($term && !is_wp_error($term)) {
            return $term->name;
        }
    }
    if (is_array($area)) {
        $first = reset($area);
        if ($first instanceof \WP_Term) {
            return $first->name;
        }
        if (is_numeric($first)) {
            $term = get_term((int) $first, 'area');
            if ($term && !is_wp_error($term)) {
                return $term->name;
            }
        }
    }

    if (strpos($title, ' - ') !== false) {
        $parts = array_map('trim', explode(' - ', $title));
        $last = end($parts);
        if ($last !== '') {
            return $last;
        }
    }

    return 'Event Details';
}

function normalize_event_url($url, $fallback) {
    $url = trim((string) $url);
    if ($url === '') {
        return $fallback;
    }
    if (!preg_match('#^https?://#i', $url)) {
        $url = 'https://' . ltrim($url, '/');
    }
    return $url;
}

function upcoming_event_banner_shortcode() {
    $event = get_homepage_upcoming_event();
    if (!$event) {
        return '';
    }

    $event_id = (int) $event->ID;
    $title = get_the_title($event_id);
    $venue = get_event_banner_venue($event_id, $title);
    $date = format_event_banner_date($event_id);
    $link = normalize_event_url(get_post_meta($event_id, 'link', true), get_permalink($event_id));
    $is_featured = get_post_meta($event_id, 'featured_event', true) === '1';
    $label = $is_featured ? 'FEATURED' : 'UPCOMING';

    ob_start();
    ?>
    <a class="jpn-upcoming-event-banner" href="<?php echo esc_url($link); ?>" target="_blank" rel="noopener">
        <span class="jpn-event-banner-main">
            <span class="jpn-event-banner-label"><?php echo esc_html($label); ?></span>
            <span class="jpn-event-banner-title"><?php echo esc_html($title); ?></span>
        </span>
        <span class="jpn-event-banner-divider" aria-hidden="true"></span>
        <span class="jpn-event-banner-meta">
            <span class="jpn-event-banner-label">VENUE</span>
            <span class="jpn-event-banner-value"><?php echo esc_html($venue); ?></span>
        </span>
        <span class="jpn-event-banner-divider" aria-hidden="true"></span>
        <span class="jpn-event-banner-meta jpn-event-banner-date">
            <span class="jpn-event-banner-label">DATE</span>
            <span class="jpn-event-banner-value"><?php echo esc_html($date); ?></span>
        </span>
        <span class="jpn-event-banner-button">RSVP <span aria-hidden="true">→</span></span>
    </a>
    <style>
    .jpn-upcoming-event-banner {
        align-items: center;
        background: rgba(255, 255, 255, 0.04);
        border: 1px solid rgba(255, 255, 255, 0.09);
        box-shadow: 0 0 40px rgba(0, 0, 0, 0.4);
        color: #fff;
        display: flex;
        gap: 24px;
        justify-content: space-between;
        padding: 24px;
        text-decoration: none;
        width: 100%;
    }
    .jpn-upcoming-event-banner:hover,
    .jpn-upcoming-event-banner:focus {
        color: #fff;
        text-decoration: none;
    }
    .jpn-event-banner-main,
    .jpn-event-banner-meta {
        display: flex;
        flex-direction: column;
        min-width: 0;
    }
    .jpn-event-banner-main {
        flex: 1 1 42%;
    }
    .jpn-event-banner-meta {
        flex: 0 1 180px;
    }
    .jpn-event-banner-date {
        flex-basis: 120px;
    }
    .jpn-event-banner-label {
        color: rgba(255, 255, 255, 0.55);
        font-family: "Segoe UI", sans-serif;
        font-size: 12px;
        font-weight: 400;
        letter-spacing: 0.4em;
        line-height: 1.3;
        margin-bottom: 8px;
    }
    .jpn-event-banner-title {
        color: #fff;
        font-family: "Segoe UI", sans-serif;
        font-size: clamp(16px, 2vw, 24px);
        font-weight: 900;
        line-height: 1.15;
        text-transform: uppercase;
    }
    .jpn-event-banner-value {
        color: #fff;
        font-family: "Segoe UI", sans-serif;
        font-size: 14px;
        font-weight: 400;
        line-height: 1.5;
    }
    .jpn-event-banner-divider {
        background: rgba(255, 255, 255, 0.1);
        height: 32px;
        width: 1px;
    }
    .jpn-event-banner-button {
        border: 1px solid rgba(255, 255, 255, 0.25);
        color: #fff;
        flex: 0 0 auto;
        font-family: "Segoe UI", sans-serif;
        font-size: 14px;
        letter-spacing: 0.4em;
        padding: 16px 28px;
        text-transform: uppercase;
        white-space: nowrap;
    }
    @media (max-width: 767px) {
        .jpn-upcoming-event-banner {
            align-items: stretch;
            flex-direction: column;
            gap: 18px;
        }
        .jpn-event-banner-divider {
            display: none;
        }
        .jpn-event-banner-meta,
        .jpn-event-banner-date {
            flex-basis: auto;
        }
        .jpn-event-banner-button {
            display: inline-flex;
            justify-content: center;
            width: 100%;
        }
    }
    </style>
    <?php
    return ob_get_clean();
}
add_shortcode('jpn_upcoming_event_banner', __NAMESPACE__ . '\\upcoming_event_banner_shortcode');

function event_venue_shortcode() {
    $event_id = get_the_ID();
    if (!$event_id) {
        return '';
    }

    return esc_html(get_event_banner_venue($event_id, get_the_title($event_id)));
}
add_shortcode('jpn_event_venue', __NAMESPACE__ . '\\event_venue_shortcode');

function event_time_range_shortcode() {
    $event_id = get_the_ID();
    if (!$event_id) {
        return '';
    }

    $start = (int) get_post_meta($event_id, 'start_date_timestamp', true);
    $end = (int) get_post_meta($event_id, 'end_date_timestamp', true);
    if ($start <= 0) {
        return '';
    }

    $start_time = wp_date('g:i A', $start);
    if ($end > $start) {
        return esc_html($start_time . ' - ' . wp_date('g:i A', $end));
    }

    return esc_html($start_time);
}
add_shortcode('jpn_event_time_range', __NAMESPACE__ . '\\event_time_range_shortcode');
