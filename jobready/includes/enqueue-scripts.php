<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Check if JobReady widget is present on the current page
 */
function jobready_is_widget_on_page() {
    // Safety check: Elementor must be loaded
    if ( ! did_action( 'elementor/loaded' ) || ! class_exists( '\Elementor\Plugin' ) ) {
        return false;
    }
    
    $elementor = \Elementor\Plugin::$instance;
    
    // Check if we're in Elementor editor
    if ( isset( $elementor->editor ) && $elementor->editor->is_edit_mode() ) {
        return true;
    }
    
    // Check if we're in Elementor preview
    if ( isset( $elementor->preview ) && $elementor->preview->is_preview_mode() ) {
        return true;
    }
    
    // Check if widget exists in post content (for Elementor pages)
    global $post;
    if ( $post && is_object( $post ) ) {
        $elementor_data = get_post_meta( $post->ID, '_elementor_data', true );
        if ( $elementor_data && is_string( $elementor_data ) ) {
            $decoded = json_decode( $elementor_data, true );
            if ( is_array( $decoded ) ) {
                return jobready_search_elementor_data( $decoded, 'widgetType', 'jobready_widget' );
            }
        }
    }
    
    // Fallback: Check page content for widget class (less reliable but covers edge cases)
    if ( $post && has_shortcode( $post->post_content, 'jobready' ) ) {
        return true;
    }
    
    return false;
}

/**
 * Recursively search Elementor data structure for widget
 */
function jobready_search_elementor_data( $data, $key, $value ) {
    if ( ! is_array( $data ) ) {
        return false;
    }
    
    foreach ( $data as $item ) {
        if ( isset( $item[ $key ] ) && $item[ $key ] === $value ) {
            return true;
        }
        if ( isset( $item['elements'] ) && is_array( $item['elements'] ) ) {
            if ( jobready_search_elementor_data( $item['elements'], $key, $value ) ) {
                return true;
            }
        }
    }
    
    return false;
}

/**
 * Enqueue assets only when widget is present
 */
function jobready_enqueue_assets() {
    // Always load in Elementor editor/preview (safety checks inside)
    if ( did_action( 'elementor/loaded' ) && class_exists( '\Elementor\Plugin' ) ) {
        $elementor = \Elementor\Plugin::$instance;
        if ( ( isset( $elementor->editor ) && $elementor->editor->is_edit_mode() ) ||
             ( isset( $elementor->preview ) && $elementor->preview->is_preview_mode() ) ) {
            jobready_do_enqueue_assets();
            return;
        }
    }
    
    // Check if widget is on page before enqueueing
    if ( ! jobready_is_widget_on_page() ) {
        return;
    }
    
    jobready_do_enqueue_assets();
}

/**
 * Actually enqueue the assets
 */
function jobready_do_enqueue_assets() {
    wp_enqueue_script( 'jquery' );
    
    // Use filemtime for cache busting
    $css_version = file_exists( JOBREADY_PATH . 'assets/css/styles.css' ) 
        ? filemtime( JOBREADY_PATH . 'assets/css/styles.css' ) 
        : '1.2.0';
    
    $js_version = file_exists( JOBREADY_PATH . 'assets/js/script.js' ) 
        ? filemtime( JOBREADY_PATH . 'assets/js/script.js' ) 
        : '1.2.0';
    
    // Enqueue CSS (loaded only when widget is present)
    wp_enqueue_style(
        'jobready-styles',
        JOBREADY_URL . 'assets/css/styles.css',
        array(),
        $css_version,
        'all'
    );

    // Defer JavaScript loading (load after page is interactive)
    wp_enqueue_script(
        'jobready-script',
        JOBREADY_URL . 'assets/js/script.js',
        array( 'jquery' ),
        $js_version,
        true
    );
    
    // Add defer/async attributes for better performance
    add_filter( 'script_loader_tag', function( $tag, $handle ) {
        if ( $handle === 'jobready-script' && strpos( $tag, 'defer' ) === false ) {
            return str_replace( ' src', ' defer src', $tag );
        }
        return $tag;
    }, 10, 2 );
    
    // Note: REST API data is localized via leads-endpoint.php hook
}

// Only enqueue on frontend when widget is present
add_action( 'wp_enqueue_scripts', 'jobready_enqueue_assets', 20 ); // Priority 20 to run after Elementor

// Always load in Elementor editor
add_action( 'elementor/editor/after_enqueue_scripts', 'jobready_do_enqueue_assets' );
add_action( 'elementor/frontend/after_enqueue_scripts', 'jobready_enqueue_assets' );
