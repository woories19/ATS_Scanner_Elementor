<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * JobReady Leads REST API Endpoints
 */

// Register REST routes for leads
function jobready_register_leads_rest_routes() {
    // Store a new lead
    register_rest_route( 'jobready/v1', '/leads', array(
        'methods'  => 'POST',
        'callback' => 'jobready_handle_store_lead',
        'permission_callback' => '__return_true',
    ) );
    
    // Get leads (admin only)
    register_rest_route( 'jobready/v1', '/leads', array(
        'methods'  => 'GET',
        'callback' => 'jobready_handle_get_leads',
        'permission_callback' => function() {
            return current_user_can( 'manage_options' );
        },
    ) );
    
    // Delete a lead (admin only)
    register_rest_route( 'jobready/v1', '/leads/(?P<id>\d+)', array(
        'methods'  => 'DELETE',
        'callback' => 'jobready_handle_delete_lead',
        'permission_callback' => function() {
            return current_user_can( 'manage_options' );
        },
    ) );
    
    // Get lead statistics (admin only)
    register_rest_route( 'jobready/v1', '/leads/stats', array(
        'methods'  => 'GET',
        'callback' => 'jobready_handle_get_lead_stats',
        'permission_callback' => function() {
            return current_user_can( 'manage_options' );
        },
    ) );
}
add_action( 'rest_api_init', 'jobready_register_leads_rest_routes' );

// Handle storing a new lead
function jobready_handle_store_lead( WP_REST_Request $request ) {
    // Debug logging
    error_log('[JobReady] Leads endpoint called');
    error_log('[JobReady] Request headers: ' . print_r($request->get_headers(), true));
    error_log('[JobReady] Request params: ' . print_r($request->get_params(), true));
    
    // Get and validate parameters
    $name = sanitize_text_field( $request->get_param( 'name' ) );
    $email = sanitize_email( $request->get_param( 'email' ) );
    $ats_score = intval( $request->get_param( 'ats_score' ) );
    $job_fit_score = intval( $request->get_param( 'job_fit_score' ) );
    $pdf_url = esc_url_raw( $request->get_param( 'pdf_url' ) );
    $resume_filename = sanitize_text_field( $request->get_param( 'resume_filename' ) );
    $job_description = sanitize_textarea_field( $request->get_param( 'job_description' ) );
    $consent_given = $request->get_param( 'consent_given' );
    
    error_log('[JobReady] Parsed lead params - Name: ' . $name . ', Email: ' . $email . ', ATS: ' . $ats_score . ', Fit: ' . $job_fit_score);
    
    // Validate required fields
    if ( empty( $email ) || ! is_email( $email ) ) {
        error_log('[JobReady] Invalid email: ' . $email);
        return new WP_REST_Response( array( 'error' => 'Valid email is required' ), 400 );
    }
    
    if ( empty( $name ) ) {
        error_log('[JobReady] Missing name');
        return new WP_REST_Response( array( 'error' => 'Name is required' ), 400 );
    }
    
    if ( empty( $pdf_url ) ) {
        error_log('[JobReady] Missing PDF URL');
        return new WP_REST_Response( array( 'error' => 'PDF URL is required' ), 400 );
    }
    
    if ( empty( $resume_filename ) ) {
        error_log('[JobReady] Missing resume filename');
        return new WP_REST_Response( array( 'error' => 'Resume filename is required' ), 400 );
    }
    
    if ( empty( $job_description ) ) {
        error_log('[JobReady] Missing job description');
        return new WP_REST_Response( array( 'error' => 'Job description is required' ), 400 );
    }
    
    // Validate scores
    if ( $ats_score < 0 || $ats_score > 100 ) {
        error_log('[JobReady] Invalid ATS score: ' . $ats_score);
        return new WP_REST_Response( array( 'error' => 'ATS score must be between 0 and 100' ), 400 );
    }
    
    if ( $job_fit_score < 0 || $job_fit_score > 100 ) {
        error_log('[JobReady] Invalid job fit score: ' . $job_fit_score);
        return new WP_REST_Response( array( 'error' => 'Job fit score must be between 0 and 100' ), 400 );
    }
    
    // Prepare lead data
    $lead_data = array(
        'name' => $name,
        'email' => $email,
        'ats_score' => $ats_score,
        'job_fit_score' => $job_fit_score,
        'pdf_url' => $pdf_url,
        'resume_filename' => $resume_filename,
        'job_description' => $job_description,
        'consent_given' => $consent_given !== 'false' ? 1 : 0
    );
    
    error_log('[JobReady] Attempting to store lead data');
    
    // Store the lead
    $lead_id = jobready_store_lead( $lead_data );
    
    if ( is_wp_error( $lead_id ) ) {
        jobready_log( 'Failed to store lead: ' . $lead_id->get_error_message() );
        error_log('[JobReady] Lead storage failed: ' . $lead_id->get_error_message());
        return new WP_REST_Response( array( 'error' => $lead_id->get_error_message() ), 500 );
    }
    
    error_log('[JobReady] Lead stored successfully with ID: ' . $lead_id);
    
    // Attempt to mirror to Google Sheets if configured (non-blocking)
    jobready_mirror_to_google_sheets( $lead_data );
    
    jobready_log( "Lead stored successfully with ID: $lead_id" );
    
    return new WP_REST_Response( array( 
        'status' => 'success',
        'lead_id' => $lead_id,
        'message' => 'Lead stored successfully'
    ), 201 );
}

// Handle getting leads (admin only)
function jobready_handle_get_leads( WP_REST_Request $request ) {
    // Get query parameters
    $per_page = intval( $request->get_param( 'per_page' ) ) ?: 20;
    $page = intval( $request->get_param( 'page' ) ) ?: 1;
    $search = sanitize_text_field( $request->get_param( 'search' ) );
    $email = sanitize_email( $request->get_param( 'email' ) );
    $date_from = sanitize_text_field( $request->get_param( 'date_from' ) );
    $date_to = sanitize_text_field( $request->get_param( 'date_to' ) );
    $orderby = sanitize_text_field( $request->get_param( 'orderby' ) );
    $order = sanitize_text_field( $request->get_param( 'order' ) );
    
    // Build arguments for getting leads
    $args = array(
        'per_page' => min( $per_page, 100 ), // Cap at 100
        'page' => max( $page, 1 ),
        'search' => $search,
        'email' => $email,
        'date_from' => $date_from,
        'date_to' => $date_to,
        'orderby' => $orderby,
        'order' => $order
    );
    
    // Get leads
    $leads_data = jobready_get_leads( $args );
    
    return new WP_REST_Response( $leads_data, 200 );
}

// Handle deleting a lead (admin only)
function jobready_handle_delete_lead( WP_REST_Request $request ) {
    $lead_id = intval( $request->get_param( 'id' ) );
    
    if ( $lead_id <= 0 ) {
        return new WP_REST_Response( array( 'error' => 'Invalid lead ID' ), 400 );
    }
    
    $result = jobready_delete_lead( $lead_id );
    
    if ( ! $result ) {
        return new WP_REST_Response( array( 'error' => 'Failed to delete lead' ), 500 );
    }
    
    return new WP_REST_Response( array( 
        'status' => 'success',
        'message' => 'Lead deleted successfully'
    ), 200 );
}

// Handle getting lead statistics (admin only)
function jobready_handle_get_lead_stats( WP_REST_Request $request ) {
    $stats = jobready_get_lead_stats();
    
    return new WP_REST_Response( $stats, 200 );
}

// Mirror lead data to Google Sheets (non-blocking)
function jobready_mirror_to_google_sheets( $lead_data ) {
    $google_sheets_url = get_option( 'jobready_google_sheets_url', '' );
    
    if ( empty( $google_sheets_url ) ) {
        return; // No Google Sheets URL configured
    }
    
    // Prepare data for Google Sheets
    $sheets_data = array(
        'timestamp' => current_time( 'Y-m-d H:i:s' ),
        'name' => $lead_data['name'],
        'email' => $lead_data['email'],
        'ats_score' => $lead_data['ats_score'],
        'job_fit_score' => $lead_data['job_fit_score'],
        'resume_filename' => $lead_data['resume_filename'],
        'pdf_url' => $lead_data['pdf_url'],
        'consent_given' => $lead_data['consent_given'] ? 'Yes' : 'No'
    );
    
    // Make non-blocking request to Google Sheets
    wp_remote_post( $google_sheets_url, array(
        'body' => json_encode( $sheets_data ),
        'headers' => array(
            'Content-Type' => 'application/json',
        ),
        'timeout' => 5, // Short timeout
        'blocking' => false, // Non-blocking
    ) );
    
    jobready_log( 'Attempted to mirror lead to Google Sheets: ' . $lead_data['email'] );
}

// Add REST API nonce and URL to frontend
function jobready_add_rest_data_to_frontend() {
    if ( ! is_admin() ) {
        $token = get_option( 'jobready_webhook_token', '' );
        $email_url = rest_url( 'jobready/v1/send-report' );
        $rest_url = rest_url( 'jobready/v1/leads' );
        $nonce = wp_create_nonce( 'wp_rest' );
        
        // Debug logging
        error_log('[JobReady] Setting up jobreadyRest object');
        error_log('[JobReady] REST URL: ' . $rest_url);
        error_log('[JobReady] Email URL: ' . $email_url);
        error_log('[JobReady] Nonce: ' . $nonce);
        error_log('[JobReady] Token: ' . $token);
        
        wp_localize_script( 'jobready-script', 'jobreadyRest', array(
            'restUrl' => $rest_url,
            'emailUrl' => esc_url_raw( $email_url ),
            'nonce' => $nonce,
            'token' => $token,
        ) );
        
        error_log('[JobReady] jobreadyRest object configured successfully');
    }
}
add_action( 'wp_enqueue_scripts', 'jobready_add_rest_data_to_frontend' );
