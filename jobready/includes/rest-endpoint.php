<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function jobready_register_rest_routes() {
    register_rest_route( 'jobready/v1', '/send-report', array(
        'methods'  => 'POST',
        'callback' => 'jobready_handle_send_report',
        'permission_callback' => '__return_true',
    ) );
}
add_action( 'rest_api_init', 'jobready_register_rest_routes' );

function jobready_make_absolute_url( $url ) {
    $u = (string) $url;
    if ( $u === '' ) return $u;
    if ( strpos( $u, 'http://' ) === 0 || strpos( $u, 'https://' ) === 0 ) return $u;
    // Build from site_url origin
    $site = site_url( '/' );
    return rtrim( $site, '/' ) . '/' . ltrim( $u, '/' );
}

function jobready_handle_send_report( WP_REST_Request $request ) {
    // Debug logging
    error_log('[JobReady] Email endpoint called');
    error_log('[JobReady] Request headers: ' . print_r($request->get_headers(), true));
    error_log('[JobReady] Request params: ' . print_r($request->get_params(), true));
    
    $token_header = (string) $request->get_header( 'X-JobReady-Token' );
    $expected     = (string) get_option( 'jobready_webhook_token', '' );
    
    error_log('[JobReady] Token header: ' . $token_header);
    error_log('[JobReady] Expected token: ' . $expected);
    
    // Only require token if it's configured
    if ( $expected !== '' && ! hash_equals( $expected, $token_header ) ) {
        error_log('[JobReady] Token mismatch - unauthorized');
        return new WP_REST_Response( array( 'error' => 'Unauthorized' ), 401 );
    }

    $name        = sanitize_text_field( (string) $request->get_param( 'name' ) );
    $email       = sanitize_email( (string) $request->get_param( 'email' ) );
    $ats_score   = intval( $request->get_param( 'ats_score' ) );
    $fit_score   = intval( $request->get_param( 'job_fit_score' ) );
    $pdf_url_raw = (string) $request->get_param( 'pdf_url' );

    error_log('[JobReady] Parsed email params - Name: ' . $name . ', Email: ' . $email . ', ATS: ' . $ats_score . ', Fit: ' . $fit_score . ', PDF: ' . $pdf_url_raw);

    if ( empty( $email ) || ! is_email( $email ) ) {
        error_log('[JobReady] Invalid email: ' . $email);
        return new WP_REST_Response( array( 'error' => 'Invalid email address' ), 400 );
    }
    
    if ( empty( $pdf_url_raw ) ) {
        error_log('[JobReady] Missing PDF URL');
        return new WP_REST_Response( array( 'error' => 'Missing PDF URL' ), 400 );
    }

    $pdf_url = esc_url_raw( jobready_make_absolute_url( $pdf_url_raw ) );
    error_log('[JobReady] Processed PDF URL: ' . $pdf_url);

    $subject = sprintf( 'Your JobReady Report (ATS %d | Fit %d)', $ats_score, $fit_score );

    // Build HTML body
    $body  = '';
    $body .= '<p>Hi ' . esc_html( $name ? $name : 'there' ) . ',</p>';
    $body .= '<p>Thanks for using <strong>JobReady</strong>. Your resume has been analyzed.</p>';
    $body .= '<ul>';
    $body .= '<li><strong>ATS Score:</strong> ' . intval( $ats_score ) . '%</li>';
    $body .= '<li><strong>Job Fit Score:</strong> ' . intval( $fit_score ) . '%</li>';
    $body .= '</ul>';
    $body .= '<p>You can download your full PDF report here:<br/>';
    $body .= '<a href="' . esc_url( $pdf_url ) . '" target="_blank" rel="noopener">' . esc_html( $pdf_url ) . '</a></p>';
    $body .= '<p>We also attached the report for your convenience.</p>';
    $body .= '<p>— Mazin Digital</p>';

    $headers = array( 'Content-Type: text/html; charset=UTF-8' );

    // Attempt to download the PDF and attach
    $attachments = array();
    $tmp_file = '';
    if ( function_exists( 'download_url' ) ) {
        include_once ABSPATH . 'wp-admin/includes/file.php';
        error_log('[JobReady] Attempting to download PDF from: ' . $pdf_url);
        $tmp_file = download_url( $pdf_url, 20 );
        if ( ! is_wp_error( $tmp_file ) && file_exists( $tmp_file ) ) {
            $attachments[] = $tmp_file;
            error_log('[JobReady] PDF attachment downloaded successfully: ' . $tmp_file);
        } else {
            error_log('[JobReady] PDF attachment download failed: ' . print_r($tmp_file, true));
        }
    } else {
        error_log('[JobReady] download_url function not available');
    }

    error_log('[JobReady] About to call wp_mail - Email: ' . $email . ', Subject: ' . $subject . ', Attachments: ' . count($attachments));
    $sent = wp_mail( $email, $subject, $body, $headers, $attachments );

    error_log('[JobReady] wp_mail result: ' . ($sent ? 'SUCCESS' : 'FAILED'));

    if ( ! empty( $tmp_file ) && file_exists( $tmp_file ) ) {
        @unlink( $tmp_file );
        error_log('[JobReady] Temporary file cleaned up: ' . $tmp_file);
    }

    if ( ! $sent ) {
        error_log('[JobReady] Email send failed - returning 500');
        return new WP_REST_Response( array( 'error' => 'Email send failed' ), 500 );
    }

    error_log('[JobReady] Email sent successfully - returning 200');
    return new WP_REST_Response( array( 'status' => 'sent' ), 200 );
}
