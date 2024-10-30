<?php
/*
Plugin Name: WP Shortcodes Finder
Description: A plugin to find and display all shortcodes used in posts and pages, including their status, in a WordPress-styled table.
Version: 1.5
Author: Shubham Ralli
*/

// Exit if accessed directly
if ( !defined( 'ABSPATH' ) ) exit;

// Enqueue AJAX script
function sf_enqueue_scripts() {
    wp_enqueue_script( 'sf-ajax-script', plugin_dir_url( __FILE__ ) . 'ajax-script.js', array('jquery'), null, true );
    wp_localize_script( 'sf-ajax-script', 'sf_ajax_object', array( 'ajax_url' => admin_url( 'admin-ajax.php' ) ) );
}
add_action( 'admin_enqueue_scripts', 'sf_enqueue_scripts' );

// Add admin menu for the WP Shortcodes Finder under Tools
function sf_add_admin_menu() {
    add_management_page(
        'WP Shortcodes Finder', 
        'WP Shortcodes Finder', 
        'manage_options', 
        'wp-shortcodes-finder', 
        'sf_display_admin_page'
    );
}
add_action( 'admin_menu', 'sf_add_admin_menu' );

// Display the admin page content
function sf_display_admin_page() {
    ?>
    <div class="wrap">
        <h1>WP Shortcodes Finder</h1>
        <form id="sf_shortcode_form" method="POST" action="">
            <?php sf_display_shortcode_options(); ?>
            <input type="submit" name="sf_find_shortcode" class="button button-primary" value="Find Shortcode">
        </form>
        <!-- Loading spinner (hidden by default) -->
        <div id="sf_loading" style="display: none;">
            <p>Loading...</p> <!-- You can replace this with a spinner image if preferred -->
        </div>
        <div id="sf_results"></div> <!-- Container to display results -->
    </div>
    <?php
}


// Display available shortcodes in a dropdown
function sf_display_shortcode_options() {
    global $shortcode_tags;

    echo '<label for="sf_shortcode">Select a Shortcode:</label>';
    echo '<select name="sf_shortcode" id="sf_shortcode" required>';
    echo '<option value="">-- Select Shortcode --</option>';

    // List all registered shortcodes
    foreach ( $shortcode_tags as $tag => $callback ) {
        echo '<option value="' . esc_attr( $tag ) . '">' . esc_html( $tag ) . '</option>';
    }
    echo '</select>';
}

// Handle AJAX request to get shortcode usage
function sf_ajax_shortcode_usage() {
    if ( !isset($_POST['shortcode']) || empty($_POST['shortcode']) ) {
        wp_send_json_error('No shortcode provided.');
        return;
    }

    $shortcode = sanitize_text_field( $_POST['shortcode'] );
    global $wpdb;

    // Query to get all posts and pages
    $query = "
        SELECT ID, post_title, post_content, post_type, post_status
        FROM {$wpdb->posts}
        WHERE post_status IN ('publish', 'draft', 'private', 'trash') 
        AND (post_type = 'post' OR post_type = 'page')
    ";
    $results = $wpdb->get_results( $query );

    ob_start(); // Start output buffering

    echo '<h2>Shortcode Usage: [' . esc_html( $shortcode ) . ']</h2>';

    if ( !empty( $results ) ) {
        echo '<table class="wp-list-table widefat fixed striped">';
        echo '<thead>';
        echo '<tr>';
        echo '<th scope="col">Type</th>';
        echo '<th scope="col">Title</th>';
        echo '<th scope="col">Shortcode Usage</th>';
        echo '<th scope="col">Status</th>';
        echo '<th scope="col">Actions</th>'; // New column for actions
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';

        foreach ( $results as $post ) {
            if ( has_shortcode( $post->post_content, $shortcode ) ) {
                // Set post type label
                $post_type_label = ($post->post_type === 'post') ? 'Post' : 'Page';

                // Set post status label
                switch ( $post->post_status ) {
                    case 'publish':
                        $status_label = 'Published';
                        break;
                    case 'draft':
                        $status_label = 'Draft';
                        break;
                    case 'private':
                        $status_label = 'Private';
                        break;
                    case 'trash':
                        $status_label = 'Trash';
                        break;
                    default:
                        $status_label = ucfirst( $post->post_status );
                        break;
                }

                echo '<tr>';
                echo '<td>' . esc_html( $post_type_label ) . '</td>';
                echo '<td><a href="' . get_permalink( $post->ID ) . '" target="_blank">' . esc_html( $post->post_title ) . '</a></td>';
                echo '<td>' . sf_extract_shortcode_data( $shortcode, $post->post_content ) . '</td>';
                echo '<td>' . esc_html( $status_label ) . '</td>';
                echo '<td><a href="' . get_permalink( $post->ID ) . '" target="_blank" class="button">View</a></td>';
                echo '</tr>';
            }
        }

        echo '</tbody>';
        echo '</table>';
    } else {
        echo '<p>No posts or pages found with this shortcode.</p>';
    }

    $output = ob_get_clean(); // Get buffered content
    wp_send_json_success($output); // Send the response back to the AJAX call
}

// Extract the actual shortcode usage from post content
function sf_extract_shortcode_data( $shortcode, $content ) {
    preg_match_all( '/' . get_shortcode_regex( array( $shortcode ) ) . '/', $content, $matches );

    if ( !empty( $matches[0] ) ) {
        return esc_html( $matches[0][0] );
    }

    return 'Not found';
}

// Register AJAX actions
add_action('wp_ajax_sf_get_shortcode_usage', 'sf_ajax_shortcode_usage');
add_action('wp_ajax_nopriv_sf_get_shortcode_usage', 'sf_ajax_shortcode_usage'); // Optional, if needed for non-logged-in users
