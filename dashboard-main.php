<?php namespace jpn_structure; 













add_action('acf/save_post', __NAMESPACE__ . '\\auto_populate_whatsapp_when_options_saved',20);
add_action('wp_ajax_export_todays_events', __NAMESPACE__ . '\\export_todays_events');
add_action('wp_ajax_export_tomorrows_events', __NAMESPACE__ . '\\export_tomorrows_events');
add_action('wp_ajax_export_weeks_events', __NAMESPACE__ . '\\export_weeks_events');

// ADD HOST ROLE 
function add_host_role() {
    // Only add the role if it doesn't already exist.
    if ( ! get_role( 'host' ) ) {
        // Get contributor capabilities.
        $contributor = get_role( 'contributor' );
        if ( $contributor ) {
            add_role( 'host', __( 'Host' ), $contributor->capabilities );
        }
    }
}
add_action( 'init', __NAMESPACE__ . '\\add_host_role' );




/**
 * Set the default user role to "host".
 *
 * This filter overrides the "default_role" option so that whenever WordPress
 * retrieves the default user role (for example, on the New User page), it returns "host".
 */
function set_default_user_role_to_host( $default_role ) {
    return 'host';
}
add_filter( 'pre_option_default_role', __NAMESPACE__ . '\set_default_user_role_to_host' );







/**
 * Change the Author meta box title to "Host" for posts of type "event".
 *
 * This function removes the default Author meta box and re-adds it with a custom title.
 * It only runs on posts of type "event".
 */
function custom_event_author_box() {
    global $post;

    // Ensure we have a valid post object and that its post type is "event"
    if ( isset( $post ) && 'event' === $post->post_type ) {
        // Remove the default Author meta box.
        remove_meta_box( 'authordiv', 'event', 'normal' );
        
        // Re-add the meta box with the new title "Host".
        // 'post_author_meta_box' is the default callback function used to render the box.
        add_meta_box( 'authordiv', __( 'Host' ), 'post_author_meta_box', 'event', 'normal', 'default' );
    }
}
// Hook the function into the add_meta_boxes action so it runs when meta boxes are registered.
add_action( 'add_meta_boxes', __NAMESPACE__ . '\\custom_event_author_box' );

/**
 * Append the Featured Image URL below the Featured Image meta box on "event" posts.
 *
 * This function checks if the current post is of type "event" and if a featured image exists.
 * If so, it outputs a JavaScript snippet that appends a clickable link (with target="_blank")
 * showing the featured image URL right below the featured image meta box.
 */
function add_featured_image_url_to_event() {
    global $post;

    // Check if we're on an "event" post and have a valid post object.
    if ( isset( $post ) && 'event' === $post->post_type ) {
        // Get the URL for the featured image in its full size.
        $thumbnail_url = get_the_post_thumbnail_url( $post->ID, 'full' );
        
        // Only add the URL if a featured image exists.
        if ( $thumbnail_url ) {
            // Output JavaScript that appends the featured image URL as a clickable link
            // below the Featured Image meta box (with id "postimagediv").
            echo '<script type="text/javascript">
            jQuery(document).ready(function($) {
                $("#postimagediv").append(\'<p style="margin-top:10px;">Featured Image URL: <a href="' . esc_url( $thumbnail_url ) . '" target="_blank">' . esc_url( $thumbnail_url ) . '</a></p>\');
            });
            </script>';
        }
    }
}
// Hook into the admin footer for the post edit screen to output our JavaScript.
add_action( 'admin_footer-post.php', __NAMESPACE__ . '\\add_featured_image_url_to_event' );

















// REMOVE USER.PHP ANNOYING FEATURES
function hide_custom_user_profile_sections() {
    // Only target profile pages
    $screen = get_current_screen();
    if ( 'profile' !== $screen->base && 'user-edit' !== $screen->base ) {
        return;
    }
    ?>
    <style>
        /* Hide Admin Color Scheme row */
        tr.user-admin-color-wrap { display: none; }
        /* Hide Language row */
        tr.user-language-wrap { display: none; }
        /* Hide Elementor - AI section (adjust the selector if necessary) */
        tr.elementor-ai-section { display: none; }
        /* Hide Application Passwords row (Wordfence or built-in application passwords) */
        tr.application-passwords { display: none; }
        /* Hide Rank Math meta box */
        #setting-panel-container-rank_math_metabox { display: none; }
        /* Hide Keyboard Shortcuts row (adjust selector as needed) */
        tr.user-keyboard-shortcuts { display: none; }
    </style>
    <script>
        (function($){
            $(document).ready(function(){
                // Hide the entire Personal Options section by finding the heading and then its following table.
                $("h2").filter(function() {
                    return $(this).text() === "Personal Options";
                }).each(function(){
                    $(this).next("table.form-table[role='presentation']").hide();
                });
            });
        })(jQuery);
    </script>
    <?php
}
add_action('admin_head-user-edit.php', __NAMESPACE__ . '\\hide_custom_user_profile_sections');
add_action('admin_head-profile.php', __NAMESPACE__ . '\\hide_custom_user_profile_sections');

// REMOVE RANK MATH FROM EVENT POST TYPE
/*
add_filter( 'rank_math/metabox/disable_for_post_type', function( $disabled, $post_type ) {
    if ( 'event' === $post_type ) {
        return true;
    }
    return $disabled;
}, 10, 2 );
*/
function remove_rank_math_meta_box_from_post_types() {
    remove_meta_box( 'rank_math_metabox', 'event', 'normal' );
    remove_meta_box( 'rank_math_metabox', 'area', 'normal' );
    //remove_meta_box( 'rank_math_metabox', 'event', 'normal' );
    //remove_meta_box( 'rank_math_metabox', 'event', 'normal' );
}
add_action( 'add_meta_boxes', __NAMESPACE__ . '\\remove_rank_math_meta_box_from_post_types', 100 );

/*

function custom_allow_media_upload_types( $mimes ) {
    // Allow PNG uploads explicitly.
    $mimes['png'] = 'image/png';
    return $mimes;
}
add_filter( 'upload_mimes', __NAMESPACE__ . '\\custom_allow_media_upload_types' );

// Override the stricter file type check.
function allow_all_upload_types( $data, $file, $filename, $mimes ) {
    // Use wp_check_filetype() to get the proper file type info.
    $wp_filetype = wp_check_filetype( $filename, $mimes );
    $data['ext']             = $wp_filetype['ext'];
    $data['type']            = $wp_filetype['type'];
    $data['proper_filename'] = $filename;
    return $data;
}
add_filter( 'wp_check_filetype_and_ext', __NAMESPACE__ . '\\allow_all_upload_types', 10, 4 );
*/
//add_action('acf/save_post', __NAMESPACE__.'\acf_overwrite_notifications_dashboard_update', 20);
//make sure to make only active on a specific page.
//add_action('admin_footer', __NAMESPACE__.'\ajax_notifications_button_process');
//add_action('admin_enqueue_scripts', __NAMESPACE__.'\zushislist_enqueue_admin_scripts');
//add_action('admin_footer', __NAMESPACE__.'\zushislist_admin_footer_script');
//add_action('wp_ajax_process_updates', __NAMESPACE__.'\handle_process_updates');
//add_action('wp_ajax_nopriv_process_updates', __NAMESPACE__.'\handle_process_updates');
//add_action('admin_footer', __NAMESPACE__.'\zushislist_admin_footer_script2');

//add_action('admin_footer', __NAMESPACE__.'\zushislist_admin_footer_script_featured_images_download');
//add_action('admin_footer', __NAMESPACE__.'\zushislist_display_featured_images_container');
//add_action('admin_footer', __NAMESPACE__.'\zushislist_admin_footer_script_all_events_with_expired_highlighted');


//add_action('wp_ajax_download_images_as_zip', 'handle_download_images_as_zip');
function get_active_events($num_days = 7, $area_slug = null) {
    // Use WordPress's local time.
    $current_time   = current_time('timestamp');
    // Get the beginning of today (local time).
    $start_of_today = strtotime('today', $current_time);
    // Set the future cutoff as current time plus the given number of days.
    $future_time    = $current_time + ($num_days * DAY_IN_SECONDS);

    // Create an OR condition for the end date.
    // This allows events with no end date, an empty end date, or an end date that is after or equal to today.
    $end_date_condition = array(
        'relation' => 'OR',
        array(
            'key'     => 'end_date_timestamp',
            'compare' => 'NOT EXISTS'
        ),
        array(
            'key'     => 'end_date_timestamp',
            'value'   => '',
            'compare' => '='
        ),
        array(
            'key'     => 'end_date_timestamp',
            'value'   => $start_of_today,
            'compare' => '>=',
            'type'    => 'NUMERIC',
        )
    );

    // Base meta query:
    // 1) Enforce that the event's start date is within today and the future cutoff.
    // 2) Apply the end date condition.
    $meta_query = array(
        'relation' => 'AND',
        array(
            'key'     => 'start_date_timestamp',
            'value'   => $start_of_today,
            'compare' => '>=',
            'type'    => 'NUMERIC',
        ),
        array(
            'key'     => 'start_date_timestamp',
            'value'   => $future_time,
            'compare' => '<=',
            'type'    => 'NUMERIC',
        ),
        $end_date_condition,
    );

    // If an area slug is provided, filter events by the ACF "area" field.
    if (!empty($area_slug)) {
        $area_post = get_page_by_path($area_slug, OBJECT, 'area');
        if (!$area_post) {
            return array();
        }
        $meta_query[] = array(
            'key'     => 'area',
            'value'   => $area_post->ID,
            'compare' => '=',
            'type'    => 'NUMERIC',
        );
    }

    // Build the query arguments.
    $args = array(
        'post_type'      => 'event',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'orderby'        => 'meta_value_num',
        'order'          => 'ASC',
        'meta_key'       => 'start_date_timestamp',
        'meta_query'     => $meta_query,
    );

    $query = new \WP_Query($args);
    return $query->posts;
}





/**
 * Build HTML output from get_active_events().
 * Adjust $num_days as appropriate to your needs.
 *
 * @param int $num_days How many days from now to consider an event "active."
 * @return string HTML for a box listing the active events.
 */
function convert_events_to_html($num_days = 7) {
    // Call your get_active_events() function
    $events = get_active_events($num_days);

    $html  = '<div id="active-events-postbox" class="postbox">';
    $html .= '  <div class="postbox-header">';
    $html .= '    <h2 class="hndle ui-sortable-handle">' . __('Active Events', 'textdomain') . '</h2>';
    $html .= '  </div>';
    $html .= '  <div class="inside">';

    if (!empty($events)) {
        $html .= '<ul>';
        foreach ($events as $event) {
            // Each $event is a WP_Post object
            $event_id  = $event->ID;
            $edit_link = get_edit_post_link($event_id);
            $view_link = get_permalink($event_id);

            $html .= '<li>' 
                  .  esc_html($event->post_title)
                  . ' - <a href="' . esc_url($view_link) . '" target="_blank">View</a>'
                  . ' - <a href="#" class="remove-event" data-event-id="' . esc_attr($event_id) . '">Remove</a>'
                  . ' - <a href="' . esc_url($edit_link) . '" target="_blank">Edit</a>'
                  . '</li>';
        }
        $html .= '</ul>';
    } else {
        $html .= '<p>No active events found.</p>';
    }

    $html .= '  </div>';
    $html .= '</div>';

    return $html;
}






function auto_populate_whatsapp_when_options_saved($post_id) {
   // write_log("Hook fired, post_id: " . $post_id, true);

    // Only run if we're saving the "options" page
    //write_log("OPTIONS POST_ID IS :".$post_id,true);
    if ($post_id !== 'options') return; 
    
   // write_log("save options clickc", true);


    $active_events = get_active_events(7);
    write_log("entering create_active_events_array",true);
    $events_text = create_active_events_array($active_events);	
    



$header = get_field('whatsapp_notification_header', 'option');
$footer = get_field('whatsapp_notification_footer', 'option');

//write_log("header:".$header."::footer".$footer,true);
//write_log("active E BODY 2".$events_text["body"] ,true);
//write_log($events_text["body"],true);

// Update the ACF field with the generated string.
if (!empty($events_text)) {
 update_field('whatsapp_notification_body', $events_text["body"], 'option');
 update_field('whatsapp_notification_output',$events_text["message"], 'option');
}
 
return $events_text;



/*	
    write_log("active_events_texts: ". $events_text["message"]);
    write_log("active_events_texts: ". $events_text["message_telegram"][0]);
        write_log("active_events_texts: ". $events_text["message_telegram"][1]);

*/
    //	write_log("active_events_string: ".$active_events_string);
    
    
   // write_log("TESTING 101: ".$events_text["message"],true);
    //return; 
    
//send_post_request_to_zapier($events_text["message"],$events_text["message_telegram"]);
    
    //wp_send_json_success(array('message' => 'Updates processed successfully.'));

}


/**
 * Build a text snippet for a single event (a WP_Post object)
 * by reading relevant ACF fields directly with get_field().
 */
if (!function_exists(__NAMESPACE__ . '\\create_event_text')) {
    function create_event_text($event_obj) {
        // 1) Build the title line with host and area appended in parentheses if available.
        $text = '';
        if (!empty($event_obj->post_title)) {
            // Start with the event title using a megaphone emoji.
            $text .= "📣 " . $event_obj->post_title;
            
            // Retrieve the host (author) information.
            $host = get_userdata($event_obj->post_author);
            $host_text = '';
            if ($host && !empty($host->display_name)) {
                $host_text = $host->display_name;
            }
            
            // Retrieve the "area" ACF field.
            $area_field = get_field('area', $event_obj->ID);
            $area_text = '';
            if (!empty($area_field)) {
                if (is_array($area_field)) {
                    $area_names = [];
                    foreach ($area_field as $area) {
                        if (is_numeric($area)) {
                            // If numeric, assume it's a term ID and fetch the term name.
                            $term = get_term_by('id', $area, 'area');
                            if ($term && !is_wp_error($term)) {
                                $area_names[] = $term->name;
                            } else {
                                $area_names[] = $area;
                            }
                        } elseif (is_object($area) && isset($area->name)) {
                            $area_names[] = $area->name;
                        } else {
                            $area_names[] = $area;
                        }
                    }
                    $area_text = implode(", ", $area_names);
                } else {
                    if (is_numeric($area_field)) {
                        // If the field is numeric, assume it's a term ID.
                        $term = get_term_by('id', $area_field, 'area');
                        if ($term && !is_wp_error($term)) {
                            $area_text = $term->name;
                        } else {
                            $area_text = $area_field;
                        }
                    } elseif (is_object($area_field) && isset($area_field->name)) {
                        $area_text = $area_field->name;
                    } else {
                        $area_text = $area_field;
                    }
                }
            }
            
            // Avoid redundancy: if host contains the area (case-insensitive), don't display the area.
            if (!empty($host_text) && !empty($area_text)) {
                if (stripos($host_text, $area_text) !== false) {
                    $area_text = '';
                }
            }
            
            // If either host or area exists, append them in parentheses.
            if ($host_text || $area_text) {
                $text .= " (";
                if ($host_text) {
                    $text .= $host_text;
                }
                if ($host_text && $area_text) {
                    $text .= ", ";
                }
                if ($area_text) {
                    $text .= $area_text;
                }
                $text .= ")";
            }
            $text .= "\n";
        }

        // 2) Start and end dates
        $start_date = get_field('start_date', $event_obj->ID);
        $end_date   = get_field('end_date', $event_obj->ID);

        if ($start_date) {
            // Convert the start date to a timestamp.
            $start_timestamp = strtotime($start_date);
            // Format the start date (e.g. "March 13") and start time (e.g. "8:00 pm").
            $formatted_start_date = date('F j', $start_timestamp);
            $formatted_start_time = date('g:i a', $start_timestamp);

            // Build the date portion.
            $text .= "🗓️ " . $formatted_start_date . ", " . $formatted_start_time;

            if ($end_date) {
                $end_timestamp = strtotime($end_date);
                // If both dates are on the same day, display only the end time.
                if (date('Y-m-d', $start_timestamp) === date('Y-m-d', $end_timestamp)) {
                    $formatted_end_time = date('g:i a', $end_timestamp);
                    $text .= "-" . $formatted_end_time;
                } else {
                    // Otherwise, display the full end date (without year) and end time.
                    $formatted_end_date = date('F j', $end_timestamp);
                    $formatted_end_time = date('g:i a', $end_timestamp);
                    $text .= "-" . $formatted_end_date . ", " . $formatted_end_time;
                }
            }
            $text .= "\n";
        }

        // 3) Location
        $location = get_field('location', $event_obj->ID);
        if ($location) {
            $text .= "📍 " . $location . "\n";
        }

        // 4) Link: If there is an ACF "link" field, use it; otherwise, use the post permalink.
        $link = get_field('link', $event_obj->ID);
        if ($link) {
            $text .= "Learn more: " . $link . "\n";
        } else {
            $permalink = get_permalink($event_obj->ID);
            if ($permalink) {
                $text .= "Learn more: " . $permalink . "\n";
            }
        }

        // Extra line break at the end.
        $text .= "\n";

        return $text;
    }
}

function create_active_events_array($events) {
    $active_events_array = array();
    $active_events_array["message"] = "";
    $active_events_array["message_telegram"] = array();

    $header = get_field('whatsapp_notification_header', 'option');
    $footer = get_field('whatsapp_notification_footer', 'option');

    $events_text = array();
    
    $active_events_array["body"] = "";
    foreach ($events as $event) {
        $event_text = create_event_text($event);
        $events_text[] = $event_text;
        $active_events_array["body"] .= $event_text;
    }
    
    $active_events_array["message"] = split_messages_with_limit($events_text, 1000000, $header, $footer);
    $active_events_array["message"] = $active_events_array["message"][0];
    $active_events_array["message_telegram"] = split_messages_with_limit($events_text, 4000, $header, $footer);

    return $active_events_array;
}










add_action('admin_enqueue_scripts', __NAMESPACE__ . '\\jpn_enqueue_events_stats_box');
function jpn_enqueue_events_stats_box($hook) {
    // Only load on the notifications dashboard options page.
    if ($hook !== 'toplevel_page_notifications-dashboard') {
        return;
    }
    
    // Register and enqueue a dummy script so we can add inline JavaScript.
    wp_register_script('jpn-events-stats-inline', '', array('jquery'), '1.0', true);
    wp_enqueue_script('jpn-events-stats-inline');



      // Call your existing get_active_events()
      $events_today     = get_active_events(1);
      $events_48_hours  = get_active_events(2);
      $events_this_week = get_active_events(7);
  
      // Calculate counts
      $count_today     = count($events_today);
      $count_48_hours  = count($events_48_hours);
      $count_this_week = count($events_this_week);
  
      // get events in miami-beach
          $mb_events_this_week = get_active_events(7,"miami-beach");
          $mb_count_this_week = count($mb_events_this_week);
      

      // Use a heredoc for cleaner HTML
      $html = <<<HTML
      <div id="jpn-events-stats" class="postbox" style="margin:10px;">
          <h2 class="hndle"><span>Events Stats</span></h2>
          <div class="inside" style="padding:10px;">
              <div class="event-stats">
                  <p><strong>Events Today:</strong> $count_today</p>
                  <p><strong>Events within 48 Hours:</strong> $count_48_hours</p>
                  <p><strong>Events This Week:</strong> $count_this_week</p>
                  <p><h2>Events in Miami Beach</h2></p>
                  <p><strong>Events This Week:</strong> $mb_count_this_week</p>
                  
                 
              </div>
          </div>
      </div>
      HTML;

  

        // 2) Generate the HTML from our function
        $events_stats_html = $html;
    
        // 3) Safely encode as JSON so it won't break JS if it contains quotes or newlines
        //    This also helps if you ever have to include HTML that has quotes or special chars.
        $encoded_html = wp_json_encode($events_stats_html);

        

    // Use heredoc syntax to avoid escape characters.
    $inline_script = <<<JS
jQuery(document).ready(function($) {
    // Create the meta box using a template literal for clean HTML.
  // Our HTML string is a valid JSON string, including quotes
  var metaBoxStatsHtml = $encoded_html;
    
    // Convert that string into a jQuery element
    var metaBoxStats = $(metaBoxStatsHtml);

    // Append the meta box to the side panel if available, or fallback to main content.
    if ($('#postbox-container-1').length) {
        $('#postbox-container-1').append(metaBoxStats);
    } else {
        $('#wpbody-content').prepend(metaBoxStats);
    }
});
JS;
    
    wp_add_inline_script('jpn-events-stats-inline', $inline_script);
}





/**
 * Enqueue an inline script with our meta box injection and AJAX logic.
 * Only loads on the notifications dashboard options page.
 */
add_action('admin_enqueue_scripts', __NAMESPACE__ . '\\jpn_enqueue_export_events_script');
function jpn_enqueue_export_events_script($hook) {
    if ($hook !== 'toplevel_page_notifications-dashboard') {
        return;
    }
    
    // Register and enqueue a dummy script so we can attach inline JS.
    wp_register_script('jpn-export-inline', '', array('jquery'), '1.0', true);
    wp_enqueue_script('jpn-export-inline');
    
    $ajax_vars = array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce'   => wp_create_nonce('jpn_export_nonce'),
    );
    
    $json = json_encode($ajax_vars);
    
    // Using HEREDOC so you can write clean JS with template literals.
    $inline_script = <<<JS
var jpnAjax = {$json};

function triggerDownload(url, filename) {
        var a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
    }
    


jQuery(document).ready(function($) {
    var metaBox = $(`
        <div id="jpn-export-events" class="postbox" style="margin:10px;">
            <h2 class="hndle"><span>Export events</span></h2>
            <div class="inside" style="padding:10px;">
                <button id="export-today" class="button button-primary" style="margin:5px;">Export Today's Events</button>
                <button id="export-tomorrow" class="button button-primary" style="margin:5px;">Export Tomorrow's Events</button>
                <button id="export-week" class="button button-primary" style="margin:5px;">Export Week's Events</button>
            </div>
        </div>
    `);
    
    if ($('#postbox-container-1').length) {
        $('#postbox-container-1').append(metaBox);
    } else {
        $('#wpbody-content').prepend(metaBox);
    }
    
    $('#export-today').on('click', function(e) {
        e.preventDefault();
        $.ajax({
            url: jpnAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'export_todays_events',
                nonce: jpnAjax.nonce
            },
            success: function(response) {
                alert("Download your ZIP here: " + response.data.zip_url);
                triggerDownload(response.data.zip_url, 'export_today.zip');
            },
            error: function() {
                alert('Error processing request');
            }
        });
    });
    
    $('#export-tomorrow').on('click', function(e) {
        e.preventDefault();
        $.ajax({
            url: jpnAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'export_tomorrows_events',
                nonce: jpnAjax.nonce
            },
            success: function(response) {
                alert("Download your ZIP here: " + response.data.zip_url);
                triggerDownload(response.data.zip_url, 'export_tomorrow.zip');

            },
            error: function() {
                alert('Error processing request');
            }
        });
    });
    
    $('#export-week').on('click', function(e) {
        e.preventDefault();
        $.ajax({
            url: jpnAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'export_weeks_events',
                nonce: jpnAjax.nonce
            },
            success: function(response) {
                alert("Download your ZIP here: " + response.data.zip_url);
                triggerDownload(response.data.zip_url, 'export_week.zip');

            },
            error: function() {
                alert('Error processing request');
            }
        });
    });
});
JS;
    
    wp_add_inline_script('jpn-export-inline', $inline_script);
}

/**
 * Create a ZIP archive containing the featured images for a set of events.
 * Returns the full file path to the zip archive on success or false on failure.
 */
function create_featured_images_zip($events) {
    // Get uploads directory info.
    $upload_dir = wp_upload_dir();
    $tmp_dir = $upload_dir['basedir'] . '/exported_events';
    if (!file_exists($tmp_dir)) {
        wp_mkdir_p($tmp_dir);
    }
    
    // Create a unique filename.
    $zip_filename = $tmp_dir . '/export_' . time() . '.zip';
    $zip = new \ZipArchive();
    if ($zip->open($zip_filename, \ZipArchive::CREATE) !== true) {
        return false;
    }
    
    foreach ($events as $event) {
        // Get the featured image URL.
        $image_url = get_the_post_thumbnail_url($event->ID, 'full');
        if (!$image_url) {
            continue;
        }
        // Convert the URL to a local file path.
        $image_path = str_replace(site_url(), ABSPATH, $image_url);
        if (!file_exists($image_path)) {
            continue;
        }
        // Add the image file to the ZIP using its basename.
        $zip->addFile($image_path, basename($image_path));
    }
    $zip->close();
    return $zip_filename;
}

/**
 * AJAX handler for exporting today's events.
 * Retrieves events using get_active_events(1), creates a ZIP of their featured images, and returns the download URL.
 */
function export_todays_events() {
    check_ajax_referer('jpn_export_nonce', 'nonce');
    $events = get_active_events(1);
    $zip_file = create_featured_images_zip($events);
    if (!$zip_file) {
        wp_send_json_error(array('message' => 'Could not create zip file.'));
    }
    $upload_dir = wp_upload_dir();
    $zip_url = $upload_dir['baseurl'] . '/exported_events/' . basename($zip_file);
    wp_send_json_success(array('zip_url' => $zip_url));
}

/**
 * AJAX handler for exporting tomorrow's events.
 * Retrieves events using get_active_events(2), creates a ZIP of their featured images, and returns the download URL.
 */
function export_tomorrows_events() {
    check_ajax_referer('jpn_export_nonce', 'nonce');
    $events = get_active_events(2);
    $zip_file = create_featured_images_zip($events);
    if (!$zip_file) {
        wp_send_json_error(array('message' => 'Could not create zip file.'));
    }
    $upload_dir = wp_upload_dir();
    $zip_url = $upload_dir['baseurl'] . '/exported_events/' . basename($zip_file);
    wp_send_json_success(array('zip_url' => $zip_url));
}

/**
 * AJAX handler for exporting week's events.
 * Retrieves events using get_active_events(7), creates a ZIP of their featured images, and returns the download URL.
 */
function export_weeks_events() {
    check_ajax_referer('jpn_export_nonce', 'nonce');
    $events = get_active_events(7);
    $zip_file = create_featured_images_zip($events);
    if (!$zip_file) {
        wp_send_json_error(array('message' => 'Could not create zip file.'));
    }
    $upload_dir = wp_upload_dir();
    $zip_url = $upload_dir['baseurl'] . '/exported_events/' . basename($zip_file);
    wp_send_json_success(array('zip_url' => $zip_url));
}
























//OLD FUNCTIONS 









/*
function acf_overwrite_notifications_dashboard_update($post_id) {
    // Check if this is the correct options page
    if($post_id === 'options' && isset($_GET['page']) && $_GET['page'] === 'notifications-dashboard') {
			
$active_events = get_active_tribe_events();

update_acf_active_events_text($active_events);

			
//send_get_request_to_zapier($active_events_string);

	}
}

*/ 













/*

function sort_events_by_start_time($event1, $event2) {
    $start1 = isset($event1['event_start_timestamp']) ? $event1['event_start_timestamp'] : 0;
    $start2 = isset($event2['event_start_timestamp']) ? $event2['event_start_timestamp'] : 0;

    // Compare the start timestamps
    if ($start1 == $start2) {
        return 0;
    }
    return ($start1 < $start2) ? -1 : 1;
}

// Sort the events array
usort($events, 'sort_events_by_start_time');

// Now $events is sorted starting with the most recent upcoming events.


function get_active_tribe_events() {
    // Define the query arguments
$args = array(
    'post_type'      => 'event',
    'posts_per_page' => -1,

    'tax_query'      => array(
        array(
            'taxonomy' => 'event-status',
            'field'    => 'slug',
            'terms'    => 'active',
        ),
    ),
);

	

   // 'meta_key'       => 'start_date_timestamp',
 //   'orderby'        => 'meta_value_num',
//    'order'          => 'ASC',


    $events = array();

    $query = new WP_Query($args);

    // Check if any posts were returned
    $counter = 0;
    if ($query->have_posts()) {
        while ($query->have_posts()) {
            $query->the_post();

            // Debug each post
            $event_status_terms = wp_get_post_terms(get_the_ID(), 'event-status', array("fields" => "slugs"));
            $is_active = in_array('active', $event_status_terms);
           write_log("Checking post ID " . get_the_ID() . ": " . ($is_active ? "Active" : "Not Active"));

            if ($is_active) {
                $counter++;
                // Populate your array with specific post data
                
				    $location = get_field("location");
				

//write_log("TIME DEBUG for: ".get_the_title());
				
// The input times are already in 'America/New_York' time, no need for conversion
$timezone_ny = new DateTimeZone('America/New_York');
//write_log("Timezone for input times: " . $timezone_ny->getName());

// Create DateTime objects for start and end dates with the correct timezone
$start_date = new DateTime(get_field("_EventStartDate"), $timezone_ny);
$end_date = new DateTime(get_field("_EventEndDate"), $timezone_ny);

// Since the times are already in the correct timezone, there's no need to convert them.
// Logging the dates and times for debugging purposes
//write_log("Event Start Date (America/New_York): " . $start_date->format('Y-m-d, g:ia'));
//write_log("Event End Date (America/New_York): " . $end_date->format('Y-m-d, g:ia'));

				// After converting timezones
$formatted_start_date = $start_date->format('Y-m-d, g:ia');
$formatted_end_date = $end_date->format('Y-m-d, g:ia');

// Log the formatted dates
//write_log("Formatted Start Date: " . $formatted_start_date);
//write_log("Formatted End Date: " . $formatted_end_date);
				
				

				
				$events[] = array(
    'ID'                => get_the_ID(),
    'title'             => get_the_title(),
    // Use 'sign_up_link' if available; fallback to '_EventURL' otherwise
    'event_url'         => get_field("sign_up_link") ? get_field("sign_up_link") : get_field("_EventURL"), 
    // Use 'event_cost' as is; it appears unchanged
    'event_cost'        => get_field("_EventCost"), 
    // Use 'start_time' if available; fallback to '_EventStartDate' otherwise
    'event_start'       => get_field("start_time") ? get_field("start_time") : get_field("_EventStartDate"), 
	'event_start_timestamp'       => get_field("start_time_timestamp"), 
    'event_end_timestamp'       => get_field("end_time_timestamp"), 
    // Use 'end_time' if available; fallback to '_EventEndDate' otherwise
    'event_end'         => get_field("end_time") ? get_field("end_time") : get_field("_EventEndDate"), 
    'permalink'         => get_the_permalink(),
    // Use 'location_full' if available; seems like 'location' has no direct new equivalent, so adapt as needed
    'location'          => get_field("location_full") ? get_field("location_full") : $location['label'],
    'event_start_display' => $formatted_start_date, 
    'event_end_display'  => $formatted_end_date
);

				
				
				
				
            }
        }

        // Reset post data to avoid conflicts with other queries
        wp_reset_postdata();
       // write_log("There are " . $counter . " active events.");
        
        return $events;
    } else {
        // Log no posts found
        write_log("No active tribe events found.");
        
        // Return an empty array or false if no posts found
        return array();
    }
}






*/




















/*
function split_messages_with_limit($events, $limit, $header, $footer) {
    $messages = array();
    $message_count = 0;

$current_message = $message_count == 0 ? $header : ""; // Start with the header for the first message

foreach ($events as $event_text) {
    // For the first message, check including the footer; for others, just the event text
    $potential_message = $message_count == 0 ? $current_message . $event_text . $footer : $current_message . $event_text;

    if (strlen($potential_message) > $limit) {
        if ($message_count == 0) {
            // For the first message, append the footer before adding it to the messages array
            $current_message .= $footer;
        }

        // Add the current message to the messages array
        $messages[] = $current_message;

        // Reset current_message for the next round
        $current_message = $message_count == 0 ? $header . $event_text : $event_text;
        
        // Increment message_count after splitting
        $message_count++;
    } else {
        // If not exceeding the limit, just append the event_text
        $current_message = $potential_message;
    }
}

// Don't forget to add the last message if it's not empty, considering it might not end with a footer
if (!empty($current_message)) {
    if ($message_count == 0) {
        // If there was only one message and it's the first, append the footer
        $current_message .= $footer;
    }
    $messages[] = $current_message;
}



    // Add part numbers, if more than one message
    if (count($messages) > 1) {
        foreach ($messages as $index => &$message) {
            $part_info = "Part " . ($index + 1) . "/" . count($messages) . "\n";
            $message = $part_info . $message;
        }
    }

    return $messages;
}

*/








function split_messages_with_limit($events, $limit, $header, $footer) {
    $messages = [];
    $current_message = $header; // Start the first message with the header
    $total_length = strlen($header) + strlen($footer); // Include header and footer in total length initially

    foreach ($events as $event_text) {
        $event_length = strlen($event_text);

        // Check if adding the current event exceeds the limit
        if ($total_length + $event_length > $limit) {
            // If this isn't the first message, append only the current message without header
            // For the first iteration, current_message already includes the header
            $messages[] = $current_message;
            $current_message = $event_text; // Start new message with current event text
            $total_length = $event_length; // Reset total length for the new message
        } else {
            // Append the event text to the current message and update total length
            $current_message .= $event_text;
            $total_length += $event_length;
        }
    }

    // Check if there is a message to add after finishing the loop
    if (!empty($current_message)) {
        if (count($messages) === 0) {
            // If this is the only message, add both header and footer
            $current_message .= $footer;
        }
        $messages[] = $current_message; // Add the last message
    }

    // Append the footer only to the last message if there are multiple messages
    if (count($messages) > 1) {
        $messages[count($messages) - 1] .= $footer;
    }

    // Add part numbers if there are multiple messages
    if (count($messages) > 1) {
        foreach ($messages as $index => &$message) {
            $part_info = "Part " . ($index + 1) . "/" . count($messages) . ":\n";
            // Prepend part info only after ensuring all parts have been assembled
            $message = $part_info . $message;
        }
    }

    return $messages;
}























// Helper function to convert HTML to Markdown
function convert_html_to_markdown($html) {
    // Replace <p> tags with newline characters
    $markdown = str_replace(['<p>', '</p>'], ['', "\n\n"], $html);
    // Replace <b> tags with * for bold in markdown
    $markdown = str_replace(['<b>', '</b>'], ['*', '*'], $markdown);

    // Add more conversions as needed

    return $markdown;
}





/*
function send_post_request_to_zapier($message,$messages_telegram) {
	
	//write_log("send_post_request_to_zapier(message): ".$message);
    $url = 'https://hooks.zapier.com/hooks/catch/186940/3xjjxxt/'; // The webhook URL
  //  write_log("POST REQUEST: ". $url);
 
	foreach($messages_telegram as $message_telegram)
	{
 $message_full = convert_html_to_markdown($message);
	    $message = html_entity_decode($message, ENT_QUOTES, 'UTF-8');
}
	
	 $message_full = convert_html_to_markdown($message);
	    $message = html_entity_decode($message, ENT_QUOTES, 'UTF-8');

	
	   $body = json_encode([
        [
            "message" => $message
        ]
    ]);
	
    $response = wp_remote_post($url, array(
        'body' => $body,
        'timeout' => '45', // Set timeout
        'redirection' => '5', // Number of max redirections
        'httpversion' => '1.1', // Use HTTP 1.1
        'blocking' => true, // Wait for the response
        'headers' => array(
            'Content-Type' => 'application/json', // Ensure the content type is set to JSON
        ),
        'cookies' => array(),
    ));

    if (is_wp_error($response)) {
        $error_message = $response->get_error_message();
        echo "Something went wrong: $error_message";
        write_log("POST REQUEST RESPONSE: ERROR, ". $error_message);
    } else {
        echo 'Response:<pre>';
        write_log("POST REQUEST RESPONSE: ");
        write_log($response);
        print_r($response);
        echo '</pre>';
    }
}
*/






function send_single_post_request($url, $message_key, $message_content,$is_telegram ="false") {

	
	    // Prepare and send the full message
    $message_content = convert_html_to_markdown($message_content);
    $message_content = html_entity_decode($message_content, ENT_QUOTES, 'UTF-8');
	
	

	//write_log("KEY: ".$message_key,true);
	//	write_log("message_content: ".$message_content,true);
	

		   $body = json_encode([
        [
          "Message" => $message_content,
			"Message_Telegram"=> $is_telegram,
        ]
    ]);

	
  $response = wp_remote_post($url, array(
        'body' => $body,
        'timeout' => '45', // Set timeout
        'redirection' => '5', // Number of max redirections
        'httpversion' => '1.1', // Use HTTP 1.1
        'blocking' => true, // Wait for the response
        'headers' => array(
            'Content-Type' => 'application/json', // Ensure the content type is set to JSON
        ),
        'cookies' => array(),
    ));

    if (is_wp_error($response)) {
        $error_message = $response->get_error_message();
      //  write_log("POST REQUEST RESPONSE: ERROR, " . $error_message,true);
    } else {
      //  write_log("POST REQUEST RESPONSE: SUCCESS",true);
       // write_log($response);
    }
}

function send_post_request_to_zapier($message, $messages_telegram) {
    $url = 'https://hooks.zapier.com/hooks/catch/186940/3xjjxxt/';


    send_single_post_request($url, "Message", $message,"false");
  /*  sleep(5); // Delay the next request by 5 seconds

    // Prepare and send each Telegram message
    foreach ($messages_telegram as $message_telegram) {
  
        send_single_post_request($url, "Message", $message_telegram, "true");
		    sleep(10); // Delay the next request by 1 second

    }*/ 
}






















/*
function DELETE_convert_events_to_html() {
    $args = [
        'post_type' => 'event',
        'posts_per_page' => -1, // Get all matching events
        'tax_query' => [
            [
                'taxonomy' => 'event-status',
                'field' => 'slug',
                'terms' => 'active',
            ],
        ],
    ];

    $query = new \WP_Query($args);
    $html = '<div id="active-events-postbox" class="postbox"><div class="postbox-header"><h2 class="hndle ui-sortable-handle">'. __('Active Events', 'textdomain') .'</h2></div><div class="inside">';

    if ($query->have_posts()) {
        $html .= '<ul>';
        while ($query->have_posts()) {
            $query->the_post();
            $event_id = get_the_ID();
            $edit_link = get_edit_post_link($event_id);
            $view_link = get_permalink($event_id);
            $html .= '<li>' . get_the_title() . ' - <a href="' . esc_url($view_link) . '" target="_blank">View</a> - <a href="#" class="remove-event" data-event-id="' . esc_attr($event_id) . '">Remove</a> - <a href="' . esc_url($edit_link) . '" target="_blank">Edit</a></li>';
        }
        $html .= '</ul>';
    } else {
        $html .= '<p>No active events found.</p>';
    }
    $html .= '</div></div>';

    wp_reset_postdata();
    return $html;
}
*/
function zushislist_enqueue_admin_scripts($hook_suffix) {
    // Only enqueue script on the specific admin page
    if ($hook_suffix !== 'toplevel_page_notifications-dashboard') {
        return;
    }

    wp_enqueue_script('zushislist-admin-js', get_template_directory_uri() . '/js/admin-notifications-dashboard.js', ['jquery'], null, true);

    // Localize script for PHP values
    wp_localize_script('zushislist-admin-js', 'zushislistData', [
        'activeEventsHtml' => convert_events_to_html(),
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('remove-event-status'),
    ]);
}




function zushislist_admin_footer_script() {
    // Only run this script on the notifications-dashboard page
    if (get_current_screen()->id !== 'toplevel_page_notifications-dashboard') {
        return;
    }

    // Prepare the active events HTML
    $activeEventsHtml = convert_events_to_html();
    $ajaxUrl = admin_url('admin-ajax.php');
    $nonce = wp_create_nonce('remove-event-status');
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        // Directly inject the active events HTML
        $('#submitdiv').after(<?php echo json_encode($activeEventsHtml); ?>);

        // Handle click event for "Remove" links
        $('.remove-event').on('click', function(e) {
            e.preventDefault();
            const eventId = $(this).data('event-id');
            
            $.ajax({
                url: '<?php echo $ajaxUrl; ?>',
                type: 'POST',
                data: {
                    action: 'remove_event_status',
                    event_id: eventId,
                    nonce: '<?php echo $nonce; ?>'
                },
                success: function(response) {
                    if (response.success) {
                        window.open(response.data.redirect_url, '_blank');
                        location.reload();
                    }
                }
            });
        });
    });
    </script>
    <?php
}






























/* Process button and functionality */

function ajax_notifications_button_process() {
    // Target the notifications-dashboard page
    if (get_current_screen()->id !== 'toplevel_page_notifications-dashboard') {
        return;
    }
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        // Add "Process" button next to the "Update" button
        $('<input type="button" class="button button-secondary" value="Process" id="process-updates" style="margin-right:10px;">').insertBefore('#publish');
        
        // Handle the click event on the "Process" button
        $('#process-updates').click(function() {
            $.ajax({
                url: ajaxurl,
                type: 'GET',
                data: {
                    action: 'process_updates',
                    // Add any additional data here if needed
                },
                success: function(response) {
                    // Handle the response from the server
                    alert(response.data.message);
                }
            });
        });
    });
    </script>
    <?php
}











/*

function zushislist_admin_footer_script2() {
    if (get_current_screen()->id !== 'toplevel_page_notifications-dashboard') {
        return;
    }

    // Simulating event data and message stats calculation
    // Assuming $events is already populated or fetched elsewhere
    $events =  get_active_tribe_events(); // This should be your actual events array
    $events_stats = create_active_events_array($events); // Simulated function call

    // Construct the stats message based on calculated values
    $numberOfEvents = count($events);
    $totalMessageLength = strlen(implode('', $events_stats["message"]));
    $splitForTelegram = count($events_stats["message_telegram"]) > 1 ? 'True' : 'False';
    $messagePartsInfo = "";
    foreach ($events_stats["message_telegram"] as $index => $part) {
        $partNumber = $index + 1;
        $partLength = strlen($part);
        $messagePartsInfo .= "Message part $partNumber/2, character length: $partLength characters<br>";
    }

    // Prepare the HTML for Active Events Stats with dynamic content
    $activeEventsStatsHtml = "<div id='active-events-stats-postbox' class='postbox'>
                                <div class='postbox-header'>
                                    <h2 class='hndle ui-sortable-handle'>" . __('Active Events Stats', 'textdomain') . "</h2>
                                </div>
                                <div class='inside'>
                                    <p>Number of events: $numberOfEvents</p>
                                    <p>Message character length: $totalMessageLength characters</p>
                                    <p>Split for Telegram: $splitForTelegram</p>
                                    <p>$messagePartsInfo</p>
                                </div>
                              </div>";
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        // Directly inject the Active Events Stats HTML
        $('#submitdiv').after(<?php echo json_encode($activeEventsStatsHtml); ?>);
    });
    </script>
    <?php
}
add_action('admin_footer', 'zushislist_admin_footer_script2');






*/





 
 


function zushislist_admin_footer_script2() {
    if (get_current_screen()->id !== 'toplevel_page_notifications-dashboard') {
        return;
    }

    // Assuming $events is already populated or fetched elsewhere
    $events =  get_active_events(); // This should be your actual events array
    
    // Find the earliest event
    $earliestEventEndTime = new DateTime('9999-12-31'); // Set a far future date
    $earliestEvent = null;
    foreach ($events as $event) {
        $eventEndTime = new DateTime($event['event_end']);
        if ($eventEndTime < $earliestEventEndTime) {
            $earliestEventEndTime = $eventEndTime;
            $earliestEvent = $event;
        }
    }

    $currentTime = new DateTime('now', new DateTimeZone('EST'));
    $eventIsPast = $earliestEventEndTime < $currentTime;

    // Your existing stats calculation
    $events_text = create_active_events_array($events); // Simulated function call
    $numberOfEvents = count($events);
    $totalMessageLength = strlen($events_text["message"]);
    $splitForTelegram = count($events_text["message_telegram"]) > 1 ? 'True' : 'False';
    $messagePartsInfo = "";
	$totalParts = count($events_text["message_telegram"]);
    foreach ($events_text["message_telegram"] as $index => $part) {
        $partNumber = $index + 1;
        $partLength = strlen($part);
        $messagePartsInfo .= "Message part $partNumber/$totalParts, character length: $partLength characters<br>";
    }

    // Construct the earliest event message
    $earliestEventMessage = $eventIsPast ? "<p style='color: red;'>" : "<p>";
    $earliestEventMessage .= "Earliest Event End Date: " . $earliestEvent['event_end_display'];
    if ($earliestEvent) {
        $editLink = admin_url('post.php?action=edit&post=' . $earliestEvent['ID']);
        $earliestEventMessage .= ", <a href='$editLink' target='_blank'>edit event</a>";
    }
    $earliestEventMessage .= "</p>";
    $earliestEventMessage .= "<p>Current Date: " . $currentTime->format('Y-m-d H:i:s') . " EST</p>";

	//$message = $events_stats["message"][0];
    // Prepare the HTML for Active Events Stats with dynamic content
    $activeEventsStatsHtml = "<div id='active-events-stats-postbox' class='postbox'>
                                <div class='postbox-header'>
                                    <h2 class='hndle ui-sortable-handle'>" . __('Active Events Stats', 'textdomain') . "</h2>
                                </div>
                                <div class='inside'>
                                    <p>Number of events: $numberOfEvents</p>
                                    <p>Message character length: $totalMessageLength characters</p>
                                    <p>Split for Telegram: $splitForTelegram</p>
                                    <p>$messagePartsInfo</p>
                                    $earliestEventMessage
								

                                </div>
                              </div>";
    ?>
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        // Directly inject the Active Events Stats HTML
        $('#submitdiv').after(<?php echo json_encode($activeEventsStatsHtml); ?>);
    });
    </script>
    <?php
}












function zushislist_admin_footer_script_all_events_with_expired_highlighted() {
    if (get_current_screen()->id !== 'toplevel_page_notifications-dashboard') {
        return;
    }

    // Assuming $events are fetched from somewhere within your application
    $events = get_active_tribe_events(); // Fetch your events array
    
    $currentTime = new DateTime('now', new DateTimeZone('America/New_York')); // Ensure timezone consistency for comparison
    $eventsHtml = "<div id='all-events-postbox' class='postbox'>
                        <div class='postbox-header'>
                            <h2 class='hndle ui-sortable-handle'>" . __('All Events (Expired in Red)', 'textdomain') . "</h2>
                        </div>
                        <div class='inside'>";

    foreach ($events as $event) {

              $eventEndDate = new DateTime($event['event_end'], new DateTimeZone('America/New_York'));
        $isExpired = $eventEndDate < $currentTime;
        $eventTitle = $event['title'];
        $viewLink = $event['event_url'] ? "<a href='" . esc_url($event['event_url']) . "' target='_blank'>View</a>" : '';
        $editLink = admin_url('post.php?action=edit&post=' . $event['ID']);
        $editLinkFormatted = "<a href='$editLink' target='_blank'>Edit</a>";
        $dateEndedText = "Date Ended: " . $event['event_end_display'];

        // Apply red text color only if the event is expired
        $textColor = $isExpired ? "color: red;" : "";

        $eventsHtml .= "<p style='$textColor'>$eventTitle - $viewLink - $editLinkFormatted<br>$dateEndedText</p>";
    }

    $eventsHtml .= "</div></div>";

    echo "<script type='text/javascript'>
            jQuery(document).ready(function($) {
                // Inject the All Events HTML into the document
                $('#active-events-stats-postbox').after(" . json_encode($eventsHtml) . ");
            });
          </script>";
}
















function zushislist_get_active_events_with_featured_images() {
    // Assuming you have a function to get all active events.
    // This should return an array of event objects or an array of arrays with event details.
    $events = get_active_tribe_events();

    // Container for events with their featured image.
    $eventsWithImages = [];

    foreach ($events as $event) {
        // Assuming each event has an ID accessible like this.
        $eventId = $event['ID'];

        // Get the featured image for each event. Assuming large size is what you want.
        $featuredImage = get_the_post_thumbnail_url($eventId, 'large');

        // Add event and its image to the container.
        // You can store more event details here as needed.
        $eventsWithImages[] = [
            'event' => $event,
            'image' => $featuredImage
        ];
    }

    return $eventsWithImages;
}

function zushislist_display_featured_images_container() {
    // Ensure this only runs on the specific admin page
    if (get_current_screen()->id !== 'toplevel_page_notifications-dashboard') {
        return;
    }

    // Get events with their images.
    $eventsWithImages = zushislist_get_active_events_with_featured_images();

    // Start the container HTML
    $html = "<div id='events-images-postbox' class='postbox'>
                <div class='postbox-header'>
                    <h2 class='hndle ui-sortable-handle'>IMAGES</h2>
                </div>
                <div class='inside'>";

    // Add images to the HTML
    
	$html .= '<button id="download-images">Download All Images</button>';
	
    foreach ($eventsWithImages as $eventWithImage) {
        $imageUrl = esc_url($eventWithImage['image']);
      //  $html .= "<a href='$imageUrl' target='_blank'><img src='$imageUrl' style='width:100px; height:auto;'></a>";
    }

    // Close the container HTML
    $html .= "</div></div>";

    // Enqueue necessary script for downloading images as a zip
    // This part assumes you have a JavaScript function defined elsewhere that handles the download.
    $html .= "<script type='text/javascript'>
                jQuery(document).ready(function($) {
                    // Your JavaScript code to handle the download all button click event
                    // This could involve collecting all the image URLs displayed above and then sending them to a server-side script to package as a ZIP file.
                });
              </script>";

    echo $html;
}





function zushislist_admin_footer_script_featured_images_download() {
    if (get_current_screen()->id !== 'toplevel_page_notifications-dashboard') {
        return;
    }

    // Echo the HTML for the download button, we'll move it using JavaScript
    echo "<div id='zushislist-download-container' style='display:none;'><button id='download-featured-images' class='button button-primary'>Download Featured Images</button></div>";

    // JavaScript to handle the button click and move the download button to the top
    ?>
    <script type="text/javascript">
jQuery(document).ready(function($) {
    $('#download-featured-images').on('click', function(event) {
        event.preventDefault();
        $(this).prop('disabled', true).text('Preparing Download...');

        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'download_images_as_zip',
            },
            success: function(response) {
                window.location.href = response;
                $('#download-featured-images').prop('disabled', false).text('Download Featured Images');
            },
            error: function() {
                alert('There was an error. Please try again.');
                $('#download-featured-images').prop('disabled', false).text('Download Featured Images');
            }
        });
    });

    $('#submitdiv').after($('#zushislist-download-container'));
    $('#zushislist-download-container').show();
});

    </script>
    <?php
}
