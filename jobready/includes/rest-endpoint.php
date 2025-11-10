<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Render email HTML from a plain .html template with {{placeholders}}
 *
 * @param string $template_path
 * @param array  $vars
 * @return string
 */
function jobready_render_email_template( $template_path, array $vars ) {
    if ( ! file_exists( $template_path ) ) {
        return '';
    }
    $html = (string) file_get_contents( $template_path );
    // Build replacements for {{key}}
    $replacements = array();
    foreach ( $vars as $key => $value ) {
        // Ensure scalar strings for replacement
        if ( is_scalar( $value ) ) {
            $replacements['{{' . $key . '}}'] = (string) $value;
        }
    }
    return strtr( $html, $replacements );
}

function jobready_register_rest_routes() {
    register_rest_route( 'jobready/v1', '/send-report', array(
        'methods'  => 'POST',
        'callback' => 'jobready_handle_send_report',
        'permission_callback' => '__return_true',
    ) );

    // Public download proxy that forces attachment download using a signed link
    register_rest_route( 'jobready/v1', '/download', array(
        'methods'  => 'GET',
        'callback' => 'jobready_handle_download_proxy',
        'permission_callback' => '__return_true',
    ) );
}
add_action( 'rest_api_init', 'jobready_register_rest_routes' );

// Add fallback email handler for admin-ajax.php
add_action( 'wp_ajax_jobready_send_email_fallback', 'jobready_handle_email_fallback' );
add_action( 'wp_ajax_nopriv_jobready_send_email_fallback', 'jobready_handle_email_fallback' );

function jobready_handle_email_fallback() {
    // Debug logging
    error_log('[JobReady] Fallback email handler called');
    error_log('[JobReady] POST data: ' . print_r($_POST, true));
    
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'wp_rest')) {
        error_log('[JobReady] Fallback email - nonce verification failed');
        wp_die('Nonce verification failed');
    }
    
    $name = sanitize_text_field($_POST['name']);
    $email = sanitize_email($_POST['email']);
    $ats_score = intval($_POST['ats_score']);
    $fit_score = intval($_POST['job_fit_score']);
    $pdf_url_raw = sanitize_text_field($_POST['pdf_url']);
    
    error_log('[JobReady] Fallback email params - Name: ' . $name . ', Email: ' . $email . ', ATS: ' . $ats_score . ', Fit: ' . $fit_score . ', PDF: ' . $pdf_url_raw);
    
    if (empty($email) || !is_email($email)) {
        error_log('[JobReady] Fallback email - Invalid email: ' . $email);
        wp_send_json_error('Invalid email address');
        return;
    }
    
    if (empty($pdf_url_raw)) {
        error_log('[JobReady] Fallback email - Missing PDF URL');
        wp_send_json_error('Missing PDF URL');
        return;
    }
    
    $pdf_url = esc_url_raw(jobready_make_absolute_url($pdf_url_raw));
    error_log('[JobReady] Fallback email - Processed PDF URL: ' . $pdf_url);
    
    $subject = sprintf('Your JobReady Report (ATS %d | Fit %d)', $ats_score, $fit_score);
    
    // Build HTML body using external HTML template if available
    $template_path = defined('JOBREADY_PATH') ? JOBREADY_PATH . 'assets/html/email-report-template.html' : '';
    error_log('[JobReady] Fallback email - template path: ' . $template_path);
    if ( function_exists('jobready_log') ) { jobready_log('Fallback email - template path: ' . $template_path); }
    // Signed download URL via WordPress proxy
    $download_url = jobready_generate_signed_download_url( $pdf_url );

    $vars = array(
        'name' => esc_html( $name ? $name : 'there' ),
        'email' => esc_html( $email ),
        'ats_score' => intval( $ats_score ),
        'fit_score' => intval( $fit_score ),
        'pdf_url' => esc_url( $pdf_url ),
        'download_url' => esc_url( $download_url ),
        'subject' => esc_html( $subject ),
        'site_name' => esc_html( get_bloginfo('name') ),
        'site_url' => esc_url( home_url('/') ),
    );
    $body = $template_path ? jobready_render_email_template( $template_path, $vars ) : '';
    error_log('[JobReady] Fallback email - template exists: ' . ( $template_path && file_exists($template_path) ? 'yes' : 'no' ) . ', rendered length: ' . strlen($body));
    if ( function_exists('jobready_log') ) { jobready_log('Fallback email - template exists: ' . ( $template_path && file_exists($template_path) ? 'yes' : 'no' ) . ', rendered length: ' . strlen($body)); }
    if ($body === '') {
        $body = '';
        $body .= '<p>Hi ' . esc_html($name ? $name : 'there') . ',</p>';
        $body .= '<p>Thanks for using <strong>JobReady</strong>. Your resume has been analyzed.</p>';
        $body .= '<ul>';
        $body .= '<li><strong>ATS Score:</strong> ' . intval($ats_score) . '%</li>';
        $body .= '<li><strong>Job Fit Score:</strong> ' . intval($fit_score) . '%</li>';
        $body .= '</ul>';
        $body .= '<p>You can download your full PDF report here:<br/>';
        $body .= '<a href="' . esc_url($download_url) . '" target="_blank" rel="noopener">' . esc_html($download_url) . '</a></p>';
        $body .= '<p>We also attached the report for your convenience.</p>';
        $body .= '<p>— Mazin Digital</p>';
    }
    
    $headers = array('Content-Type: text/html; charset=UTF-8');
    
    // Attempt to download the PDF and attach
    $attachments = array();
    $tmp_file = '';
    if (function_exists('download_url')) {
        include_once ABSPATH . 'wp-admin/includes/file.php';
        error_log('[JobReady] Fallback email - Attempting to download PDF from: ' . $pdf_url);
        $tmp_file = download_url($pdf_url, 20);
        if (!is_wp_error($tmp_file) && file_exists($tmp_file)) {
            $attachments[] = $tmp_file;
            error_log('[JobReady] Fallback email - PDF attachment downloaded successfully: ' . $tmp_file);
        } else {
            error_log('[JobReady] Fallback email - PDF attachment download failed: ' . print_r($tmp_file, true));
        }
    } else {
        error_log('[JobReady] Fallback email - download_url function not available');
    }
    
    error_log('[JobReady] Fallback email - About to call wp_mail - Email: ' . $email . ', Subject: ' . $subject . ', Attachments: ' . count($attachments));
    $sent = wp_mail($email, $subject, $body, $headers, $attachments);
    
    error_log('[JobReady] Fallback email - wp_mail result: ' . ($sent ? 'SUCCESS' : 'FAILED'));
    
    if (!empty($tmp_file) && file_exists($tmp_file)) {
        @unlink($tmp_file);
        error_log('[JobReady] Fallback email - Temporary file cleaned up: ' . $tmp_file);
    }
    
    if (!$sent) {
        error_log('[JobReady] Fallback email - Email send failed');
        wp_send_json_error('Email send failed');
        return;
    }
    
    error_log('[JobReady] Fallback email - Email sent successfully');
    wp_send_json_success('Email sent successfully');
}

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
    
    // Only require token if it's configured AND provided
    if ( $expected !== '' && $token_header !== '' && ! hash_equals( $expected, $token_header ) ) {
        error_log('[JobReady] Token mismatch - unauthorized');
        return new WP_REST_Response( array( 'error' => 'Unauthorized' ), 401 );
    }
    
    // If token is configured but not provided, log a warning but continue
    if ( $expected !== '' && $token_header === '' ) {
        error_log('[JobReady] Warning: Token configured but not provided in request');
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

    // Build signed download URL that proxies through WordPress (hides origin and forces download)
    $download_url = jobready_generate_signed_download_url( $pdf_url );

    // Build HTML body using external HTML template if available
    $template_path = defined('JOBREADY_PATH') ? JOBREADY_PATH . 'assets/html/email-report-template.html' : '';
    error_log('[JobReady] REST email - template path: ' . $template_path);
    if ( function_exists('jobready_log') ) { jobready_log('REST email - template path: ' . $template_path); }
    $vars = array(
        'name' => esc_html( $name ? $name : 'there' ),
        'email' => esc_html( $email ),
        'ats_score' => intval( $ats_score ),
        'fit_score' => intval( $fit_score ),
        'pdf_url' => esc_url( $pdf_url ),
        'download_url' => esc_url( $download_url ),
        'subject' => esc_html( $subject ),
        'site_name' => esc_html( get_bloginfo( 'name' ) ),
        'site_url' => esc_url( home_url( '/' ) ),
    );
    $body = $template_path ? jobready_render_email_template( $template_path, $vars ) : '';
    error_log('[JobReady] REST email - template exists: ' . ( $template_path && file_exists($template_path) ? 'yes' : 'no' ) . ', rendered length: ' . strlen($body));
    if ( function_exists('jobready_log') ) { jobready_log('REST email - template exists: ' . ( $template_path && file_exists($template_path) ? 'yes' : 'no' ) . ', rendered length: ' . strlen($body)); }
    if ( $body === '' ) {
        $body  = '';
        $body .= '<p>Hi ' . esc_html( $name ? $name : 'there' ) . ',</p>';
        $body .= '<p>Thanks for using <strong>JobReady</strong>. Your resume has been analyzed.</p>';
        $body .= '<ul>';
        $body .= '<li><strong>ATS Score:</strong> ' . intval( $ats_score ) . '%</li>';
        $body .= '<li><strong>Job Fit Score:</strong> ' . intval( $fit_score ) . '%</li>';
        $body .= '</ul>';
        $body .= '<p>You can download your full PDF report here:<br/>';
        $body .= '<a href="' . esc_url( $download_url ) . '" target="_blank" rel="noopener">' . esc_html( $download_url ) . '</a></p>';
        $body .= '<p>We also attached the report for your convenience.</p>';
        $body .= '<p>— Mazin Digital</p>';
    }

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

// ------------------------
// Signed download proxy
// ------------------------
function jobready_get_download_secret() {
    $opt = (string) get_option( 'jobready_webhook_token', '' );
    if ( $opt !== '' ) return $opt;
    if ( defined( 'AUTH_SALT' ) && AUTH_SALT ) return AUTH_SALT;
    return wp_salt( 'auth' );
}

function jobready_generate_signed_download_url( $raw_pdf_url ) {
    $u  = rawurlencode( rtrim( (string) base64_encode( $raw_pdf_url ), '=' ) );
    $ts = time();
    $sig = hash_hmac( 'sha256', $u . '|' . $ts, jobready_get_download_secret() );
    return add_query_arg( array( 'u' => $u, 'ts' => $ts, 'sig' => $sig ), rest_url( 'jobready/v1/download' ) );
}

function jobready_handle_download_proxy( WP_REST_Request $request ) {
    $u   = (string) $request->get_param( 'u' );
    $ts  = intval( $request->get_param( 'ts' ) );
    $sig = (string) $request->get_param( 'sig' );

    if ( $u === '' || $ts <= 0 || $sig === '' ) {
        return new WP_REST_Response( array( 'error' => 'Bad request' ), 400 );
    }

    // Expire after 12 hours
    if ( time() - $ts > 43200 ) {
        return new WP_REST_Response( array( 'error' => 'Link expired' ), 410 );
    }

    $calc = hash_hmac( 'sha256', $u . '|' . $ts, jobready_get_download_secret() );
    if ( ! hash_equals( $calc, $sig ) ) {
        return new WP_REST_Response( array( 'error' => 'Invalid signature' ), 401 );
    }

    // Decode URL-safe base64 (tolerate missing padding)
    $padded = $u;
    $padlen = 4 - ( strlen( $padded ) % 4 );
    if ( $padlen > 0 && $padlen < 4 ) $padded .= str_repeat( '=', $padlen );
    $raw_url = base64_decode( rawurldecode( $padded ) );
    if ( ! $raw_url ) {
        return new WP_REST_Response( array( 'error' => 'Invalid URL' ), 400 );
    }

    $pdf_url = jobready_make_absolute_url( $raw_url );

    // Fetch the file server-side
    $resp = wp_remote_get( $pdf_url, array( 'timeout' => 20 ) );
    if ( is_wp_error( $resp ) ) {
        return new WP_REST_Response( array( 'error' => 'Fetch failed' ), 502 );
    }
    $code = wp_remote_retrieve_response_code( $resp );
    if ( $code < 200 || $code >= 300 ) {
        return new WP_REST_Response( array( 'error' => 'Upstream error', 'status' => $code ), 502 );
    }

    $body = wp_remote_retrieve_body( $resp );
    if ( $body === '' ) {
        return new WP_REST_Response( array( 'error' => 'Empty body' ), 502 );
    }

    // Derive filename
    $path = wp_parse_url( $pdf_url, PHP_URL_PATH );
    $filename = 'jobready-report.pdf';
    if ( $path ) {
        $basename = basename( $path );
        if ( $basename ) $filename = $basename;
    }

    // Send as attachment
    nocache_headers();
    header( 'Content-Type: application/pdf' );
    header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( $filename ) . '"' );
    header( 'X-Content-Type-Options: nosniff' );
    echo $body;
    exit;
}
