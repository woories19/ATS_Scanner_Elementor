<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * JobReady Lead Storage Database Operations
 */

// Create the leads table on plugin activation
function jobready_create_leads_table() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'jobready_leads';
    $charset_collate = $wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        name varchar(100) NOT NULL,
        email varchar(100) NOT NULL,
        ats_score int(3) NOT NULL,
        job_fit_score int(3) NOT NULL,
        pdf_url text NOT NULL,
        resume_filename varchar(255) NOT NULL,
        job_description text NOT NULL,
        consent_given tinyint(1) DEFAULT 1,
        ip_address varchar(45) DEFAULT '',
        user_agent text DEFAULT '',
        created_at datetime DEFAULT CURRENT_TIMESTAMP,
        updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY email (email),
        KEY created_at (created_at),
        KEY ats_score (ats_score),
        KEY job_fit_score (job_fit_score)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    
    // Log the table creation
    jobready_log('Leads table creation attempted');
    
    // Check if table was created successfully
    $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") == $table_name;
    if ($table_exists) {
        jobready_log('Leads table created successfully');
    } else {
        jobready_log('Failed to create leads table');
    }
}

// Store a new lead
function jobready_store_lead($data) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'jobready_leads';
    
    // Sanitize and validate data
    $lead_data = array(
        'name' => sanitize_text_field($data['name']),
        'email' => sanitize_email($data['email']),
        'ats_score' => intval($data['ats_score']),
        'job_fit_score' => intval($data['job_fit_score']),
        'pdf_url' => esc_url_raw($data['pdf_url']),
        'resume_filename' => sanitize_text_field($data['resume_filename']),
        'job_description' => sanitize_textarea_field($data['job_description']),
        'consent_given' => isset($data['consent_given']) ? intval($data['consent_given']) : 1,
        'ip_address' => jobready_get_client_ip(),
        'user_agent' => sanitize_text_field($_SERVER['HTTP_USER_AGENT'] ?? ''),
        'created_at' => current_time('mysql'),
        'updated_at' => current_time('mysql')
    );
    
    // Validate required fields
    if (empty($lead_data['email']) || !is_email($lead_data['email'])) {
        return new WP_Error('invalid_email', 'Invalid email address');
    }
    
    if (empty($lead_data['name'])) {
        return new WP_Error('invalid_name', 'Name is required');
    }
    
    // Insert the lead
    $result = $wpdb->insert($table_name, $lead_data);
    
    if ($result === false) {
        jobready_log('Failed to insert lead: ' . $wpdb->last_error);
        return new WP_Error('insert_failed', 'Failed to store lead data');
    }
    
    $lead_id = $wpdb->insert_id;
    jobready_log("Lead stored successfully with ID: $lead_id");
    
    return $lead_id;
}

// Get leads with pagination and filtering
function jobready_get_leads($args = array()) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'jobready_leads';
    
    // Default arguments
    $defaults = array(
        'per_page' => 20,
        'page' => 1,
        'search' => '',
        'email' => '',
        'date_from' => '',
        'date_to' => '',
        'orderby' => 'created_at',
        'order' => 'DESC'
    );
    
    $args = wp_parse_args($args, $defaults);
    
    // Build WHERE clause
    $where_clauses = array();
    $where_values = array();
    
    if (!empty($args['search'])) {
        $where_clauses[] = '(name LIKE %s OR email LIKE %s OR job_description LIKE %s)';
        $search_term = '%' . $wpdb->esc_like($args['search']) . '%';
        $where_values[] = $search_term;
        $where_values[] = $search_term;
        $where_values[] = $search_term;
    }
    
    if (!empty($args['email'])) {
        $where_clauses[] = 'email = %s';
        $where_values[] = $args['email'];
    }
    
    if (!empty($args['date_from'])) {
        $where_clauses[] = 'DATE(created_at) >= %s';
        $where_values[] = $args['date_from'];
    }
    
    if (!empty($args['date_to'])) {
        $where_clauses[] = 'DATE(created_at) <= %s';
        $where_values[] = $args['date_to'];
    }
    
    $where_sql = '';
    if (!empty($where_clauses)) {
        $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
    }
    
    // Build ORDER BY clause
    $allowed_orderby = array('name', 'email', 'ats_score', 'job_fit_score', 'created_at');
    $orderby = in_array($args['orderby'], $allowed_orderby) ? $args['orderby'] : 'created_at';
    $order = strtoupper($args['order']) === 'ASC' ? 'ASC' : 'DESC';
    
    // Get total count
    $count_sql = "SELECT COUNT(*) FROM $table_name $where_sql";
    if (!empty($where_values)) {
        $count_sql = $wpdb->prepare($count_sql, $where_values);
    }
    $total = $wpdb->get_var($count_sql);
    
    // Calculate pagination
    $offset = ($args['page'] - 1) * $args['per_page'];
    
    // Get leads
    $leads_sql = "SELECT * FROM $table_name $where_sql ORDER BY $orderby $order LIMIT %d OFFSET %d";
    $leads_values = array_merge($where_values, array($args['per_page'], $offset));
    $leads_sql = $wpdb->prepare($leads_sql, $leads_values);
    
    $leads = $wpdb->get_results($leads_sql);
    
    return array(
        'leads' => $leads,
        'total' => intval($total),
        'pages' => ceil($total / $args['per_page']),
        'current_page' => $args['page']
    );
}

// Get a single lead by ID
function jobready_get_lead($lead_id) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'jobready_leads';
    
    // Debug logging
    jobready_log('Getting lead with ID: ' . $lead_id);
    jobready_log('Table name: ' . $table_name);
    
    // Validate lead ID
    $lead_id = intval($lead_id);
    if ($lead_id <= 0) {
        jobready_log('Invalid lead ID: ' . $lead_id);
        return false;
    }
    
    // Get the lead
    $sql = $wpdb->prepare("SELECT * FROM $table_name WHERE id = %d", $lead_id);
    jobready_log('SQL query: ' . $sql);
    
    $lead = $wpdb->get_row($sql);
    
    if (!$lead) {
        jobready_log('No lead found with ID: ' . $lead_id);
        return false;
    }
    
    jobready_log('Lead found: ' . print_r($lead, true));
    return $lead;
}

// Export leads to CSV
function jobready_export_leads_csv($args = array()) {
    $leads_data = jobready_get_leads(array_merge($args, array('per_page' => 1000))); // Get more for export
    
    if (empty($leads_data['leads'])) {
        return false;
    }
    
    $filename = 'jobready-leads-' . date('Y-m-d-H-i-s') . '.csv';
    
    // Set headers for CSV download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
    
    // Create output stream
    $output = fopen('php://output', 'w');
    
    // Add CSV headers
    fputcsv($output, array(
        'ID',
        'Name',
        'Email',
        'ATS Score',
        'Job Fit Score',
        'PDF URL',
        'Resume Filename',
        'Job Description',
        'Consent Given',
        'IP Address',
        'User Agent',
        'Created At',
        'Updated At'
    ));
    
    // Add data rows
    foreach ($leads_data['leads'] as $lead) {
        fputcsv($output, array(
            $lead->id,
            $lead->name,
            $lead->email,
            $lead->ats_score,
            $lead->job_fit_score,
            $lead->pdf_url,
            $lead->resume_filename,
            $lead->job_description,
            $lead->consent_given ? 'Yes' : 'No',
            $lead->ip_address,
            $lead->user_agent,
            $lead->created_at,
            $lead->updated_at
        ));
    }
    
    fclose($output);
    exit;
}

// Get client IP address
function jobready_get_client_ip() {
    $ip_keys = array('HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR');
    
    foreach ($ip_keys as $key) {
        if (array_key_exists($key, $_SERVER) === true) {
            foreach (explode(',', $_SERVER[$key]) as $ip) {
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                    return $ip;
                }
            }
        }
    }
    
    return $_SERVER['REMOTE_ADDR'] ?? '';
}

// Delete a lead
function jobready_delete_lead($lead_id) {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'jobready_leads';
    
    $result = $wpdb->delete($table_name, array('id' => intval($lead_id)), array('%d'));
    
    if ($result === false) {
        jobready_log('Failed to delete lead ID: ' . $lead_id);
        return false;
    }
    
    jobready_log("Lead deleted successfully with ID: $lead_id");
    return true;
}

// Get lead statistics
function jobready_get_lead_stats() {
    global $wpdb;
    
    $table_name = $wpdb->prefix . 'jobready_leads';
    
    $stats = array(
        'total_leads' => 0,
        'total_this_month' => 0,
        'total_this_week' => 0,
        'total_today' => 0,
        'avg_ats_score' => 0,
        'avg_job_fit_score' => 0,
        'top_ats_score' => 0,
        'top_job_fit_score' => 0
    );
    
    // Total leads
    $stats['total_leads'] = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");
    
    // This month
    $stats['total_this_month'] = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE YEAR(created_at) = %d AND MONTH(created_at) = %d",
            date('Y'),
            date('n')
        )
    );
    
    // This week
    $stats['total_this_week'] = $wpdb->get_var(
        "SELECT COUNT(*) FROM $table_name WHERE YEARWEEK(created_at) = YEARWEEK(NOW())"
    );
    
    // Today
    $stats['total_today'] = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT COUNT(*) FROM $table_name WHERE DATE(created_at) = %s",
            date('Y-m-d')
        )
    );
    
    // Average scores
    $avg_scores = $wpdb->get_row("SELECT AVG(ats_score) as avg_ats, AVG(job_fit_score) as avg_fit FROM $table_name");
    if ($avg_scores) {
        $stats['avg_ats_score'] = round($avg_scores->avg_ats, 1);
        $stats['avg_job_fit_score'] = round($avg_scores->avg_fit, 1);
    }
    
    // Top scores
    $top_scores = $wpdb->get_row("SELECT MAX(ats_score) as top_ats, MAX(job_fit_score) as top_fit FROM $table_name");
    if ($top_scores) {
        $stats['top_ats_score'] = intval($top_scores->top_ats);
        $stats['top_job_fit_score'] = intval($top_scores->top_fit);
    }
    
    return $stats;
}
