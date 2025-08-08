<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function jobready_enqueue_assets() {
    wp_enqueue_style(
        'jobready-styles',
        JOBREADY_URL . 'assets/css/styles.css',
        array(),
        '1.0.0'
    );

    wp_enqueue_script(
        'jobready-script',
        JOBREADY_URL . 'assets/js/script.js',
        array('jquery'),
        '1.0.0',
        true
    );

    wp_localize_script(
        'jobready-script',
        'jobreadyData',
        array(
            'apiUrl' => get_option('jobready_api_url', '')
        )
    );
}
add_action( 'wp_enqueue_scripts', 'jobready_enqueue_assets' );
