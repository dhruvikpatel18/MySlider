<?php
/*
Plugin Name: MySlider
Description: This plugin adds a customizable slider to your WordPress website.
Version: 1.0
Author: Dhruvik Malaviya
Author URI: https://github.com/dhruvikpatel18
License: GPL2
*/

/**
 * Enqueue scripts and styles for the admin side
 */
function myslider_admin_enqueue_scripts() {
    wp_enqueue_style('myslider-style', plugin_dir_url(__FILE__) . 'css/myslider-style.css', array(), '1.0.0');
    wp_enqueue_script('myslider-script', plugin_dir_url(__FILE__) . 'js/myslider-script.js', array('jquery'), '1.0.0', true);
	// Localize script to pass admin ajax URL to the script
	wp_localize_script('myslider-script', 'myslider_ajax_object', array('ajax_url' => admin_url('admin-ajax.php')));
	// Localize AJAX URL and nonce
    wp_localize_script('myslider-script', 'myslider_ajax', array(
        'ajax_url' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('myslider_delete_slider_nonce')
    ));
	wp_enqueue_script('jquery-ui-sortable');

}
add_action('admin_enqueue_scripts', 'myslider_admin_enqueue_scripts');

/**
 * Enqueue scripts and styles for the front-end
 */
function myslider_frontend_enqueue_scripts() {

	// Enqueue jQuery
    wp_enqueue_script('jquery');

	// Enqueue Slick slider CSS
    wp_enqueue_style('slick-slider-css', plugin_dir_url(__FILE__) . 'assets/slick/slick.css');

    // Enqueue Slick slider theme CSS if needed
    wp_enqueue_style('slick-slider-theme-css', plugin_dir_url(__FILE__) . 'assets/slick/slick-theme.css');

    // Enqueue Slick slider JavaScript
    wp_enqueue_script('slick-slider-js', plugin_dir_url(__FILE__) . 'assets/slick/slick.min.js', array('jquery'), '1.8.1', true);

	//Enqueue Font Awesome
    wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css');

    // Enqueue custom styles
    wp_enqueue_style('myslider-custom-style', plugin_dir_url(__FILE__) . 'css/myslider-style.css');
}
add_action('wp_enqueue_scripts', 'myslider_frontend_enqueue_scripts');

/**
 * Activate Plugin
 *
 * Performs tasks when the plugin is activated.
 */
function myslider_activate() {
    myslider_add_settings_page();
}
register_activation_hook( __FILE__, 'myslider_activate' );

/**
 * Register Slider Post Type
 *
 * Registers a custom post type for the slider.
 */
function myslider_register_slider_post_type() {
    $labels = array(
        'name'               => 'My Slider',
        'singular_name'      => 'Slider',
        'menu_name'          => 'My Slider',
        'name_admin_bar'     => 'Slider',
        'add_new'            => 'Add New Slider',
        'add_new_item'       => 'Add New Slider',
        'new_item'           => 'New Slider',
        'edit_item'          => 'Edit Slider',
        'view_item'          => 'View Slider',
        'all_items'          => 'All Sliders',
        'search_items'       => 'Search Sliders',
        'parent_item_colon'  => 'Parent Sliders:',
        'not_found'          => 'No sliders found.',
        'not_found_in_trash' => 'No sliders found in Trash.'
	
    );

    $args = array(
        'labels'             => $labels,
        'public'             => true,
        'publicly_queryable' => true,
        'show_ui'            => true,
        'show_in_menu'       => true,
        'query_var'          => true,
        'rewrite'            => array( 'slug' => 'slider' ),
        'capability_type'    => 'post',
        'has_archive'        => true,
        'hierarchical'       => false,
		'menu_icon'          => 'dashicons-images-alt2',
        'menu_position'      => null,
        'supports'           => array( 'title', 'thumbnail' )
    );

    register_post_type( 'slider', $args );
}
add_action( 'init', 'myslider_register_slider_post_type' );

/**
 * Add Slider Custom Fields
 *
 * Adds meta boxes for custom fields in the slider post type.
 */
function myslider_add_slider_custom_fields() {
    add_meta_box(
        'myslider_slider_meta_box',
        'Slider Images Settings',
        'myslider_render_slider_meta_box',
        'slider',
        'normal',
        'high'
    );
	add_meta_box(
        'myslider_settings_meta_box',
        'Slider Fields Settings',
        'myslider_render_settings_meta_box',
        'slider',
        'normal',
        'high'
    );
}
add_action( 'add_meta_boxes', 'myslider_add_slider_custom_fields' );

/**
 * Save Slider Custom Fields
 *
 * Saves custom fields data when the slider is saved.
 */
function myslider_save_slider_custom_fields( $post_id ) {

	// Check if this is an autosave
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    // Check if this is a revision
    if ( wp_is_post_revision( $post_id ) ) {
        return;
    }

    // Check post type
    if ( 'slider' !== get_post_type( $post_id ) ) {
        return;
    }
    if ( isset( $_POST['myslider_image_url'] ) ) {
        $image_urls = array_map( 'esc_url_raw', $_POST['myslider_image_url'] );
        update_post_meta( $post_id, '_myslider_image_url', $image_urls );
    }
    if ( isset( $_POST['myslider_tooltip_text'] ) ) {
        $tooltip_texts = array_map( 'sanitize_text_field', $_POST['myslider_tooltip_text'] );
        update_post_meta( $post_id, '_myslider_tooltip_text', $tooltip_texts );
    }
    if ( isset( $_POST['myslider_autoplay'] ) ) {
        update_post_meta( $post_id, 'myslider_autoplay', sanitize_text_field( $_POST['myslider_autoplay'] ) );
    }
    if ( isset( $_POST['myslider_autoplay_speed'] ) ) {
        update_post_meta( $post_id, 'myslider_autoplay_speed', absint( $_POST['myslider_autoplay_speed'] ) );
    }
    if ( isset( $_POST['myslider_stop_on_hover'] ) ) {
        update_post_meta( $post_id, 'myslider_stop_on_hover', sanitize_text_field( $_POST['myslider_stop_on_hover'] ) );
    }
    if ( isset( $_POST['myslider_show_next_prev_buttons'] ) ) {
        update_post_meta( $post_id, 'myslider_show_next_prev_buttons', sanitize_text_field( $_POST['myslider_show_next_prev_buttons'] ) );
    }
    if ( isset( $_POST['myslider_show_pagination'] ) ) {
        update_post_meta( $post_id, 'myslider_show_pagination', sanitize_text_field( $_POST['myslider_show_pagination'] ) );
    }
}
add_action( 'save_post_slider', 'myslider_save_slider_custom_fields' );

/**
 * AJAX handler to save slider data
 */
function myslider_save_slider_data() {
    if (isset($_POST['post_id']) && isset($_POST['image_data']) && isset($_POST['tooltip_data'])) {
        $post_id = intval($_POST['post_id']);
        $image_data = array_map('esc_url_raw', $_POST['image_data']);
        $tooltip_data = array_map('sanitize_text_field', $_POST['tooltip_data']);

        // Save slider images and custom fields
        update_post_meta($post_id, '_myslider_image_url', $image_data);
        update_post_meta($post_id, '_myslider_tooltip_text', $tooltip_data);

        // Send success response
        wp_send_json_success('Slider data saved successfully.');
    } else {
        // Send error response if required data is missing
        wp_send_json_error('Invalid request.');
    }
}
add_action('wp_ajax_save_slider_data', 'myslider_save_slider_data');

// Add AJAX action for deleting slider
/**
 * AJAX handler to delete slider
 */
function myslider_delete_slider() {
    // Verify nonce
    if ( ! isset($_POST['nonce']) || ! wp_verify_nonce($_POST['nonce'], 'myslider_delete_slider_nonce')) {
        wp_send_json_error('Invalid nonce');
    }

    // Check if slider ID is provided
    if (isset($_POST['slider_id'])) {
        // Delete the slider
        $slider_id = intval($_POST['slider_id']);
        wp_delete_post($slider_id);

        // Return success message
        wp_send_json_success('Slider deleted successfully');
    } else {
        // If slider ID is not provided, return error
        wp_send_json_error('Slider ID not provided');
    }
}
add_action('wp_ajax_delete_slider', 'myslider_delete_slider');



/**
 * Render Slider Settings Meta Box
 *
 * Renders the settings meta box in the slider edit screen.
 */
function myslider_render_settings_meta_box( $post ) {
    $autoplay = get_post_meta( $post->ID, 'myslider_autoplay', true );
    $autoplay_speed = get_post_meta( $post->ID, 'myslider_autoplay_speed', true );
    $stop_on_hover = get_post_meta( $post->ID, 'myslider_stop_on_hover', true );
    $show_next_prev_buttons = get_post_meta( $post->ID, 'myslider_show_next_prev_buttons', true );
    $show_pagination = get_post_meta( $post->ID, 'myslider_show_pagination', true );
    ?>
    <div class="wrap">
        <h2>Slider Settings</h2>
        <p>
            <label for="myslider_autoplay">Autoplay:</label>
            <select name="myslider_autoplay" id="myslider_autoplay">
                <option value="yes" <?php selected( $autoplay, 'yes' ); ?>>Yes</option>
                <option value="no" <?php selected( $autoplay, 'no' ); ?>>No</option>
            </select>
        </p>
        <p>
            <label for="myslider_autoplay_speed">Autoplay Speed (milliseconds):</label>
            <input type="number" name="myslider_autoplay_speed" id="myslider_autoplay_speed" value="<?php echo esc_attr( $autoplay_speed ? $autoplay_speed : '5000' ); ?>">
        </p>
        <p>
            <label for="myslider_stop_on_hover">Stop on Hover:</label>
            <select name="myslider_stop_on_hover" id="myslider_stop_on_hover">
                <option value="yes" <?php selected( $stop_on_hover, 'yes' ); ?>>Yes</option>
                <option value="no" <?php selected( $stop_on_hover, 'no' ); ?>>No</option>
            </select>
        </p>
        <p>
            <label for="myslider_show_next_prev_buttons">Next / Prev Buttons:</label>
            <select name="myslider_show_next_prev_buttons" id="myslider_show_next_prev_buttons">
                <option value="yes" <?php selected( $show_next_prev_buttons, 'yes' ); ?>>Yes</option>
                <option value="no" <?php selected( $show_next_prev_buttons, 'no' ); ?>>No</option>
            </select>
        </p>
        <p>
            <label for="myslider_show_pagination">Pagination:</label>
            <select name="myslider_show_pagination" id="myslider_show_pagination">
                <option value="yes" <?php selected( $show_pagination, 'yes' ); ?>>Yes</option>
                <option value="no" <?php selected( $show_pagination, 'no' ); ?>>No</option>
            </select>
        </p>
    </div>
    <?php
}

/**
 * Render Slider Meta Box
 *
 * Renders the image and tooltip text meta box in the slider edit screen.
 */
function myslider_render_slider_meta_box( $post ) {
    $image_urls = get_post_meta( $post->ID, '_myslider_image_url', true );
    $tooltip_texts = get_post_meta( $post->ID, '_myslider_tooltip_text', true );

    if ( ! is_array( $image_urls ) ) {
        $image_urls = array();
    }
    ?>
    <div class="wrap">
        <h2>Add Slider</h2>
        <div id="slider-images-container">
            <?php if ( ! empty( $image_urls ) ) :
                foreach ( $image_urls as $key => $image_url ) :
                    ?>
                    <div class="slider-image-row">
                        <label for="myslider_image_url">Image URL:</label>
                        <input type="text" class="image-url" name="slider_image_url[]" value="<?php echo esc_attr( $image_url ); ?>" placeholder="Image URL">
                        <label for="myslider_tooltip_text">Tooltip Text:</label>
                        <input type="text" class="tooltip-text" name="slider_tooltip_text[]" value="<?php echo isset( $tooltip_texts[ $key ] ) ? esc_attr( $tooltip_texts[ $key ] ) : ''; ?>" placeholder="Tooltip Text">
                        <button type="button" class="remove-image">Remove</button>
                        <div class="image-preview">
                            <?php if ( ! empty( $image_url ) ) : ?>
                                <img src="<?php echo esc_url( $image_url ); ?>" alt="<?php echo isset( $tooltip_texts[ $key ] ) ? esc_attr( $tooltip_texts[ $key ] ) : ''; ?>">
                            <?php endif; ?>
                        </div>
                    </div>
                <?php
                endforeach;
            else : ?>
                <div class="slider-image-row">
                    <label for="myslider_image_url">Image URL:</label>
                    <input type="text" class="image-url" name="slider_image_url[]" placeholder="Image URL">
                    <label for="myslider_tooltip_text">Tooltip Text:</label>
                    <input type="text" class="tooltip-text" name="slider_tooltip_text[]" placeholder="Tooltip Text">
                    <button type="button" class="remove-image">Remove</button>
                    <div class="image-preview"></div>
                </div>
            <?php endif; ?>
        </div>
        <button type="button" id="add-image">Add More</button>
    </div>
	
    <?php
}

/**
 * Add Settings Page
 *
 * Adds settings pages to the WordPress admin menu.
 */
function myslider_add_settings_page() {
    add_menu_page(
        'MySlider Settings',
        'Slider Settings',
        'manage_options',
        'myslider-settings',
        'myslider_render_settings_page',
        'dashicons-admin-generic'
    );

    add_submenu_page(
        'myslider-settings',
        'Add New Slider',
        'Add New Slider',
        'manage_options',
        'add-slider',
        'myslider_render_add_slider_page'
    );
}
add_action( 'admin_menu', 'myslider_add_settings_page' );

/**
 * Render Settings Page
 *
 * Renders the settings page content.
 */
function myslider_render_settings_page() {
    echo '<div class="wrap">';
    echo '<h2>Slider Listing</h2>';

    // Query to retrieve all sliders
    $args = array(
        'post_type'      => 'slider',
        'posts_per_page' => -1,
    );
    $sliders_query = new WP_Query($args);

    // Check if there are sliders
    if ($sliders_query->have_posts()) {
        echo '<table class="widefat">';
        echo '<thead>';
        echo '<tr>';
		echo '<th>ID</th>';
        echo '<th>Slider Name</th>';
        echo '<th>Number of Images</th>';
        echo '<th>Autoplay</th>';
        echo '<th>Autoplay Speed</th>';
        echo '<th>Status</th>';
        echo '</tr>';
        echo '</thead>';
        echo '<tbody>';

        // Loop through each slider
        while ($sliders_query->have_posts()) {
			$sliders_query->the_post();
			$slider_id = get_the_ID();
			$slider_name = get_the_title();
			
			// Initialize $slider_images as an empty array
			$slider_images = array();
			
			// Get the meta value for _myslider_image_url key
			$slider_images = get_post_meta($slider_id, '_myslider_image_url', true);
			
			// Check if $slider_images is not empty and is an array
			$num_images = is_array($slider_images) ? count($slider_images) : 0;
			
			$autoplay = get_post_meta($slider_id, 'myslider_autoplay', true);
			$autoplay_speed = get_post_meta($slider_id, 'myslider_autoplay_speed', true);
			$status = get_post_status();

            echo '<tr>';
			echo '<td>' . $slider_id . '</td>'; // Display slider ID
            echo '<td><a href="' . get_edit_post_link($slider_id) . '">' . $slider_name . '</a></td>';
            echo '<td>' . $num_images . '</td>';
            echo '<td>' . ($autoplay == 'yes' ? 'Yes' : 'No') . '</td>';
            echo '<td>' . $autoplay_speed . '</td>';
            echo '<td>' . $status . '</td>'; // Display status
			echo '<td><a href="' . get_edit_post_link($slider_id) . '">Edit</a></td>'; // Edit link
    		echo '<td><a href="#" class="delete-slider" data-slider-id="' . $slider_id . '">Delete</a></td>'; // Delete link
            echo '</tr>';
        }
        echo '</tbody>';
        echo '</table>';

        // Restore original post data
        wp_reset_postdata();
    } else {
        echo '<p>No sliders found.</p>';
    }

    echo '</div>'; // .wrap
}

/**
 * Shortcode for MySlider
 *
 * Defines a shortcode for embedding the slider in posts/pages.
 */
function myslider_shortcode($atts ) {
  
	// Parse shortcode attributes
    $atts = shortcode_atts( array(
        'slider_id' => '' // Default value is empty
    ), $atts );

    // Set up query arguments
    $args = array(
        'post_type'      => 'slider',
        'posts_per_page' => 1, // Only fetch one post
        'orderby'        => 'date',
        'order'          => 'DESC',
    );

    // If specific slider ID is provided, modify query arguments accordingly
    if ( ! empty( $atts['slider_id'] ) ) {
        $args['p'] = $atts['slider_id'];
    }

    $query = new WP_Query( $args );

    $output = '';

    if ( $query->have_posts() ) {
        $output .= '<div class="mySlider">';
        $autoplay = '';
        $autoplay_speed = '';
        $stop_on_hover = '';
        $show_next_prev_buttons = '';
        $show_pagination = '';

        while ( $query->have_posts() ) {
            $query->the_post();
            $slider_images = get_post_meta( get_the_ID(), '_myslider_image_url', true );
			$tooltip_texts = get_post_meta( get_the_ID(), '_myslider_tooltip_text', true );

            if ( ! empty( $slider_images ) ) {
                foreach ($slider_images as $key => $image_url) {
                    $output .= '<div class="slider">';
                    $output .= '<img src="' . esc_url($image_url) . '" alt="">';
					if (isset($tooltip_texts[$key])) {
                        $output .= '<div class="tooltip">' . esc_html($tooltip_texts[$key]) . '</div>';
                    }
                    $output .= '</div>';
                }
            } else {
                $output .= '<p>No images found.</p>';
            }

            // Retrieve settings outside the loop
            $autoplay = get_post_meta( get_the_ID(), 'myslider_autoplay', true );
            $autoplay_speed = get_post_meta( get_the_ID(), 'myslider_autoplay_speed', true );
            $stop_on_hover = get_post_meta( get_the_ID(), 'myslider_stop_on_hover', true );
            $show_next_prev_buttons = get_post_meta( get_the_ID(), 'myslider_show_next_prev_buttons', true );
            $show_pagination = get_post_meta( get_the_ID(), 'myslider_show_pagination', true );
        }

        $output .= '</div>';
        
        // Add jQuery initialization script outside the loop
        $output .= '<script>
                        jQuery(document).ready(function($) {
                            $(".mySlider").slick({
                                infinite: true,
                                autoplay: ' . ($autoplay == 'yes' ? 'true' : 'false') . ',
                                autoplaySpeed: ' . intval($autoplay_speed) . ',
                                pauseOnHover: ' . ($stop_on_hover == 'yes' ? 'true' : 'false') . ',
                                arrows: ' . ($show_next_prev_buttons == 'yes' ? 'true' : 'false') . ', 
                                dots: ' . ($show_pagination == 'yes' ? 'true' : 'false'). ',
								prevArrow: \'<div class="custom-prev"><span class="fa fa-arrow-circle-left"></span><span class="sr-only">Prev</span></div>\',
                        		nextArrow: \'<div class="custom-next"><span class="fa fa-arrow-circle-right"></span><span class="sr-only">Next</span></div>\'
								
                            });
                        });
                    </script>';
    } else {
        $output .= '<p>No sliders found.</p>';
    }
    
    wp_reset_postdata();

    return $output;
}

add_shortcode( 'MySlider', 'myslider_shortcode' );

/**
 * Redirect to Add Slider Page
 *
 * Redirects to the "Add Slider" page when the "Add Slider" link is clicked.
 */
function myslider_redirect_to_add_slider() {
    global $pagenow;

    if ( $pagenow == 'admin.php' && isset( $_GET['page'] ) && $_GET['page'] == 'add-slider' ) {
        wp_redirect( admin_url( 'post-new.php?post_type=slider' ) );
        exit;
    }
}
add_action( 'admin_init', 'myslider_redirect_to_add_slider' );
