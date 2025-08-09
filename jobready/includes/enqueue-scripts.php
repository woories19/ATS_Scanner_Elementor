<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function jobready_enqueue_assets() {
    // Force jQuery first
    wp_enqueue_script('jquery');
    
    // Then styles
    wp_enqueue_style(
        'jobready-styles',
        JOBREADY_URL . 'assets/css/styles.css',
        array(),
        '1.1.3',
        'all'  // Add media type
    );

    // Then our script
    wp_enqueue_script(
        'jobready-script',
        JOBREADY_URL . 'assets/js/script.js',
        array('jquery'),
        '1.1.3',
        true
    );
}
add_action( 'wp_enqueue_scripts', 'jobready_enqueue_assets' );

// Ensure scripts also available in Elementor editor
add_action( 'elementor/editor/after_enqueue_scripts', 'jobready_enqueue_assets' );
