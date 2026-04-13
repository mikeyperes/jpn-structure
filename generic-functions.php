<?php namespace jpn_structure; 

// Use 'acf/save_post' and drop the extra parameters (just $post_id).
add_action('acf/save_post', __NAMESPACE__ . '\\update_event_timestamps', 10);
add_action('save_post', __NAMESPACE__ . '\\set_default_event_featured_image', 10, 3);


function set_default_event_featured_image($post_id, $post, $update) {
    // Only run for the "event" post type.
    if ('event' !== $post->post_type) {
        return;
    }
    
    // Avoid autosaves and revisions.
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
        return;
    }
    if (wp_is_post_revision($post_id)) {
        return;
    }
    
    // If there's no featured image, set the default one.
    if (!has_post_thumbnail($post_id)) {
        $default_image_id = 1354; // Attachment ID for "JPN Miami Dark No Text.png"
        set_post_thumbnail($post_id, $default_image_id);
    }
}




// Define the write_log function only if it isn't already defined
if (!function_exists(__NAMESPACE__ . '\\write_log')) {
    function write_log($log, $full_debug = false) {
        if (WP_DEBUG && WP_DEBUG_LOG && $full_debug) {
            // Get the backtrace
            $backtrace = debug_backtrace();
            
            // Extract the last function that called this one
            $caller = isset($backtrace[1]['function']) ? $backtrace[1]['function'] : 'N/A';
            
            // Extract the file and line number where the caller is located
            $caller_file = isset($backtrace[0]['file']) ? $backtrace[0]['file'] : 'N/A';
            $caller_line = isset($backtrace[0]['line']) ? $backtrace[0]['line'] : 'N/A';

            // Prepare the log message
            if (is_array($log) || is_object($log)) {
                // Capture both print_r and var_dump output
                ob_start();
                echo "==== print_r ====\n";
                print_r($log);
                echo "\n==== var_dump ====\n";
                var_dump($log);
                $dumped = ob_get_clean();

                $log_message = $dumped;
            } else {
                $log_message = $log;
            }
            
            // Include caller/location info
            $log_message .= "\n\n[Called by: $caller]\n[In file: $caller_file at line $caller_line]\n\n---\n";
            
            // Write to the log
            error_log($log_message);
        }
    }
} else write_log("⚠️ Warning: " . __NAMESPACE__ . "\\write_log function is already declared", true);


/**
 * 2. Our main function: check if it exists, otherwise define it.
 *    This function:
 *      - Checks if the saved post is of type 'event'.
 *      - Reads 'start_date' and 'end_date' from ACF.
 *      - Converts them to timestamps.
 *      - Updates 'start_date_timestamp' and 'end_date_timestamp'.
 *      - Also creates human-friendly display text in 'start_date_display' and 'end_date_display'.
 */
if (!function_exists(__NAMESPACE__ . '\\update_event_timestamps')) {
    function update_event_timestamps($post_id)
    {
        // 1) Fetch the post object, since $post is NOT provided by acf/save_post.
        $post = get_post($post_id);
        if (!$post) {
            return;
        }
    
        // 2) Ensure it's the 'event' post type.
        if ('event' !== $post->post_type) {
            return;
        }
    
        // 3) Make sure we don't run on autosave or if the user doesn't have permission.
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
            return;
        }
        if (!current_user_can('edit_post', $post_id)) {
            return;
        }
    
        // 4) Fetch ACF date fields.
        $start_date = get_field('start_date', $post_id);
        $end_date   = get_field('end_date', $post_id);
    
        // 5) If start_date exists, update its timestamp and create a friendly display.
        if (!empty($start_date)) {
            $start_date_timestamp = strtotime($start_date);
            update_field('start_date_timestamp', $start_date_timestamp, $post_id);
            
            // Create a human-friendly version.
            // Format: "March 23, 2025 at 10:00AM"
            $start_display = date('F j', $start_date_timestamp) . ' at ' . date('g:iA', $start_date_timestamp);
            update_field('start_date_display', $start_display, $post_id);
        }
    
        // 6) If end_date exists, update its timestamp and create a friendly display.
        if (!empty($end_date)) {
            $end_date_timestamp = strtotime($end_date);
            update_field('end_date_timestamp', $end_date_timestamp, $post_id);
            
            // Create a human-friendly version.
            $end_display = date('F j', $end_date_timestamp) . ' at ' . date('g:iA', $end_date_timestamp);
            update_field('end_date_display', $end_display, $post_id);
        }
    
        // Optional logging.
        write_log("Event Timestamps Updated for post ID: $post_id", true);
    }
} else {
    write_log("⚠️ Warning: " . __NAMESPACE__ . "\\update_event_timestamps function is already declared", true);
}


/**
 * Test function that calls get_active_events(7) and logs results
 */
function test_get_active_events() {
    $num_days = 7;
    // Call our function
    $events = get_active_events($num_days);

    // Log how many events we found
    write_log("Testing get_active_events({$num_days}). Found ". count($events) ." event(s):", true);
    // Optionally log the actual events array
    write_log($events, true);
}


function send_get_request_to_zapier($message) {
    // Encode the message for use in a URL query string
   // $encoded_message = urlencode($message);

	//  $formatted_message = str_replace("\n", "\r\n", $message);
	
  $formatted_message = preg_replace("/<br\s*\/?>/i", "\n", $formatted_message);
    
	
	
    $encoded_message = urlencode($message);
	
	
    // Append the message as a query parameter to the URL
    $url = 'https://hooks.zapier.com/hooks/catch/186940/3xjjxxt/?message=' . $encoded_message; // The webhook URL with query parameter

    write_log("GET REQUEST: " . $url); // Log the request URL

    // Make the GET request
    $response = wp_remote_get($url, array(
        'timeout' => '45', // Set timeout
        'redirection' => '5', // Number of max redirections
        'httpversion' => '1.1', // Use HTTP 1.1
        'blocking' => true, // Wait for the response
        // 'headers' => array(), // Headers are optional for GET request
        'cookies' => array(),
    ));

    // Check for errors in the response
    if (is_wp_error($response)) {
        $error_message = $response->get_error_message();
        echo "Something went wrong: $error_message";
        write_log("GET REQUEST RESPONSE: ERROR, " . $error_message); // Log the error
  } else {
   //     echo 'Response:<pre>';
//write_log("GET REQUEST RESPONSE: ");
    //    write_log($response); // Log the successful response
        
        print_r($response); // Print the response for debugging
        echo '</pre>';
    }
}


/// OLD FUNCTIONS  
 

function handle_download_images_as_zip() {
    // Check user permissions
    if (!current_user_can('manage_options')) {
        wp_die('You do not have sufficient permissions to access this feature.');
    }

    $events = get_active_tribe_events(); // Ensure this function returns your events with the necessary data
    $uploadDir = wp_upload_dir();
    $zipFilePath = $uploadDir['path'] . '/featured_images_' . time() . '.zip';
    $zip = new ZipArchive();

    if ($zip->open($zipFilePath, ZipArchive::CREATE) === TRUE) {
        foreach ($events as $event) {
            $featuredImageId = get_post_thumbnail_id($event['ID']);
$featuredImage = wp_get_attachment_image_src($featuredImageId, 'large'); // Change 'medium' to 'large' or 'full'
            $imageUrl = $featuredImage[0];
            if ($imageUrl) {
                // Use file_get_contents to fetch the image from URL, then add it to the zip
                $imageData = file_get_contents($imageUrl);
                if ($imageData !== false) {
                    $filename = basename($imageUrl);
                    $zip->addFromString($filename, $imageData);
                }
            }
        }
        $zip->close();

        // Generate the URL for downloading the ZIP
        $zipFileUrl = $uploadDir['url'] . '/' . basename($zipFilePath);
        echo $zipFileUrl; // Send the URL back to the AJAX call for redirection
    } else {
        echo 'Failed to create ZIP file.';
    }
    
    wp_die(); // Terminate the AJAX request
}

?>