<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function jobready_enqueue_assets() {
    // CSS
    wp_enqueue_style(
        'jobready-styles',
        JOBREADY_URL . 'assets/css/styles.css',
        array(),
        '1.1.0'
    );

    // JS
    wp_enqueue_script(
        'jobready-script',
        JOBREADY_URL . 'assets/js/script.js',
        array( 'jquery' ),
        '1.1.0',
        true
    );

    // Localize global API URL setting
    $global_api = get_option( 'jobready_api_url', '' );
    wp_localize_script(
        'jobready-script',
        'jobreadySettings',
        array(
            'apiUrl' => untrailingslashit( $global_api )
        )
    );
}
add_action( 'wp_enqueue_scripts', 'jobready_enqueue_assets' );

// Ensure scripts also available in Elementor editor
add_action( 'elementor/editor/after_enqueue_scripts', 'jobready_enqueue_assets' );
