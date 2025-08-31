<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

function jobready_enqueue_assets() {
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

    $token = get_option( 'jobready_webhook_token', '' );
    $rest_url = rest_url( 'jobready/v1/leads' );
    $email_url = rest_url( 'jobready/v1/send-report' );
    $nonce = wp_create_nonce( 'wp_rest' );

    // Debug logging
    error_log('[JobReady] Enqueuing assets');
    error_log('[JobReady] REST URL: ' . $rest_url);
    error_log('[JobReady] Email URL: ' . $email_url);
    error_log('[JobReady] Nonce: ' . $nonce);
    error_log('[JobReady] Token: ' . $token);

    wp_localize_script(
        'jobready-script',
        'jobreadyRest',
        array(
            'restUrl' => esc_url_raw( $rest_url ),
            'emailUrl' => esc_url_raw( $email_url ),
            'nonce'   => $nonce,
            'token'   => $token,
        )
    );
}
add_action( 'wp_enqueue_scripts', 'jobready_enqueue_assets' );
add_action( 'elementor/editor/after_enqueue_scripts', 'jobready_enqueue_assets' );
