<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function jobready_enqueue_assets() {
    // Debug logging
    error_log('[JobReady] Enqueue function called');
    
    wp_enqueue_script('jquery');
    
    wp_enqueue_style(
        'jobready-styles',
        JOBREADY_URL . 'assets/css/styles.css',
        array(),
        '1.1.5',
        'all'
    );

    wp_enqueue_script(
        'jobready-script',
        JOBREADY_URL . 'assets/js/script.js',
        array('jquery'),
        '1.1.5',
        true
    );

    // Debug logging
    error_log('[JobReady] Enqueuing assets');
    error_log('[JobReady] Script and styles enqueued successfully');
}

// Load on ALL frontend pages, not just when widget is present
add_action( 'wp_enqueue_scripts', 'jobready_enqueue_assets' );
add_action( 'elementor/editor/after_enqueue_scripts', 'jobready_enqueue_assets' );

// Also load on admin pages for testing
add_action( 'admin_enqueue_scripts', 'jobready_enqueue_assets' );
