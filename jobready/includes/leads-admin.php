<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * JobReady Leads Admin Interface
 */

// Add leads admin menu
function jobready_add_leads_admin_menu() {
    add_submenu_page(
        'jobready-settings',
        'JobReady Leads',
        'Leads',
        'manage_options',
        'jobready-leads',
        'jobready_leads_admin_page'
    );
}
add_action( 'admin_menu', 'jobready_add_leads_admin_menu' );

// Enqueue admin scripts and styles
function jobready_enqueue_leads_admin_assets( $hook ) {
    if ( $hook !== 'jobready_page_jobready-leads' ) {
        return;
    }
    
    wp_enqueue_script( 'jquery' );
    wp_enqueue_script( 'jquery-ui-datepicker' );
    wp_enqueue_style( 'jquery-ui', 'https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css' );
    
    wp_enqueue_script( 
        'jobready-leads-admin', 
        JOBREADY_URL . 'assets/js/leads-admin.js', 
        array( 'jquery' ), 
        '1.0.0', 
        true 
    );
    
    wp_localize_script( 'jobready-leads-admin', 'jobreadyLeadsAdmin', array(
        'ajaxUrl' => admin_url( 'admin-ajax.php' ),
        'restUrl' => rest_url( 'jobready/v1/leads' ),
        'nonce' => wp_create_nonce( 'wp_rest' ),
        'strings' => array(
            'confirmDelete' => 'Are you sure you want to delete this lead?',
            'deleteSuccess' => 'Lead deleted successfully',
            'deleteError' => 'Failed to delete lead',
            'loading' => 'Loading...',
            'noLeads' => 'No leads found',
            'exportSuccess' => 'Export completed successfully',
            'exportError' => 'Export failed'
        )
    ) );
}
add_action( 'admin_enqueue_scripts', 'jobready_enqueue_leads_admin_assets' );

// Handle CSV export
function jobready_handle_csv_export() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'Unauthorized' );
    }
    
    // Get filter parameters
    $search = sanitize_text_field( $_GET['search'] ?? '' );
    $email = sanitize_email( $_GET['email'] ?? '' );
    $date_from = sanitize_text_field( $_GET['date_from'] ?? '' );
    $date_to = sanitize_text_field( $_GET['date_to'] ?? '' );
    
    $args = array(
        'search' => $search,
        'email' => $email,
        'date_from' => $date_from,
        'date_to' => $date_to
    );
    
    jobready_export_leads_csv( $args );
}
add_action( 'admin_post_jobready_export_csv', 'jobready_handle_csv_export' );

// Render the leads admin page
function jobready_leads_admin_page() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    
    // Get current filters
    $search = sanitize_text_field( $_GET['search'] ?? '' );
    $email = sanitize_email( $_GET['email'] ?? '' );
    $date_from = sanitize_text_field( $_GET['date_from'] ?? '' );
    $date_to = sanitize_text_field( $_GET['date_to'] ?? '' );
    $page = max( 1, intval( $_GET['paged'] ?? 1 ) );
    $orderby = sanitize_text_field( $_GET['orderby'] ?? 'created_at' );
    $order = sanitize_text_field( $_GET['order'] ?? 'DESC' );
    
    // Get leads data
    $args = array(
        'per_page' => 20,
        'page' => $page,
        'search' => $search,
        'email' => $email,
        'date_from' => $date_from,
        'date_to' => $date_to,
        'orderby' => $orderby,
        'order' => $order
    );
    
    $leads_data = jobready_get_leads( $args );
    $stats = jobready_get_lead_stats();
    
    ?>
    <div class="wrap">
        <h1>JobReady Leads</h1>
        
        <!-- Statistics Dashboard -->
        <div class="jobready-stats-dashboard">
            <div class="jobready-stat-box">
                <h3>Total Leads</h3>
                <div class="stat-number"><?php echo esc_html( $stats['total_leads'] ); ?></div>
            </div>
            <div class="jobready-stat-box">
                <h3>This Month</h3>
                <div class="stat-number"><?php echo esc_html( $stats['total_this_month'] ); ?></div>
            </div>
            <div class="jobready-stat-box">
                <h3>This Week</h3>
                <div class="stat-number"><?php echo esc_html( $stats['total_this_week'] ); ?></div>
            </div>
            <div class="jobready-stat-box">
                <h3>Today</h3>
                <div class="stat-number"><?php echo esc_html( $stats['total_today'] ); ?></div>
            </div>
            <div class="jobready-stat-box">
                <h3>Avg ATS Score</h3>
                <div class="stat-number"><?php echo esc_html( $stats['avg_ats_score'] ); ?>%</div>
            </div>
            <div class="jobready-stat-box">
                <h3>Avg Job Fit</h3>
                <div class="stat-number"><?php echo esc_html( $stats['avg_job_fit_score'] ); ?>%</div>
            </div>
        </div>
        
        <!-- Filters -->
        <div class="jobready-filters">
            <form method="get" action="">
                <input type="hidden" name="page" value="jobready-leads" />
                
                <div class="filter-row">
                    <div class="filter-group">
                        <label for="search">Search:</label>
                        <input type="text" id="search" name="search" value="<?php echo esc_attr( $search ); ?>" placeholder="Name, email, or job description..." />
                    </div>
                    
                    <div class="filter-group">
                        <label for="email">Email:</label>
                        <input type="email" id="email" name="email" value="<?php echo esc_attr( $email ); ?>" placeholder="Filter by email..." />
                    </div>
                    
                    <div class="filter-group">
                        <label for="date_from">Date From:</label>
                        <input type="date" id="date_from" name="date_from" value="<?php echo esc_attr( $date_from ); ?>" />
                    </div>
                    
                    <div class="filter-group">
                        <label for="date_to">Date To:</label>
                        <input type="date" id="date_to" name="date_to" value="<?php echo esc_attr( $date_to ); ?>" />
                    </div>
                    
                    <div class="filter-actions">
                        <button type="submit" class="button button-primary">Filter</button>
                        <a href="?page=jobready-leads" class="button">Reset</a>
                        <a href="<?php echo esc_url( admin_url( 'admin-post.php?action=jobready_export_csv&' . http_build_query( array_filter( $args ) ) ) ); ?>" class="button button-secondary">Export CSV</a>
                    </div>
                </div>
            </form>
        </div>
        
        <!-- Leads Table -->
        <div class="jobready-leads-table-container">
            <?php if ( empty( $leads_data['leads'] ) ): ?>
                <div class="jobready-no-leads">
                    <p>No leads found matching your criteria.</p>
                </div>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th scope="col" class="manage-column column-id sortable <?php echo $orderby === 'id' ? strtolower( $order ) : ''; ?>">
                                <a href="<?php echo esc_url( add_query_arg( array( 'orderby' => 'id', 'order' => $orderby === 'id' && $order === 'ASC' ? 'DESC' : 'ASC' ) ) ); ?>">
                                    <span>ID</span>
                                    <span class="sorting-indicator"></span>
                                </a>
                            </th>
                            <th scope="col" class="manage-column column-name sortable <?php echo $orderby === 'name' ? strtolower( $order ) : ''; ?>">
                                <a href="<?php echo esc_url( add_query_arg( array( 'orderby' => 'name', 'order' => $orderby === 'name' && $order === 'ASC' ? 'DESC' : 'ASC' ) ) ); ?>">
                                    <span>Name</span>
                                    <span class="sorting-indicator"></span>
                                </a>
                            </th>
                            <th scope="col" class="manage-column column-email">Email</th>
                            <th scope="col" class="manage-column column-phone">Phone</th>
                            <th scope="col" class="manage-column column-scores">Scores</th>
                            <th scope="col" class="manage-column column-resume">Resume</th>
                            <th scope="col" class="manage-column column-created sortable <?php echo $orderby === 'created_at' ? strtolower( $order ) : ''; ?>">
                                <a href="<?php echo esc_url( add_query_arg( array( 'orderby' => 'created_at', 'order' => $orderby === 'created_at' && $order === 'ASC' ? 'DESC' : 'ASC' ) ) ); ?>">
                                    <span>Created</span>
                                    <span class="sorting-indicator"></span>
                                </a>
                            </th>
                            <th scope="col" class="manage-column column-actions">Actions</th>
                        </tr>
                    </thead>
                    
                    <tbody>
                        <?php foreach ( $leads_data['leads'] as $lead ): ?>
                            <tr data-lead-id="<?php echo esc_attr( $lead->id ); ?>">
                                <td class="column-id"><?php echo esc_html( $lead->id ); ?></td>
                                <td class="column-name">
                                    <strong><?php echo esc_html( $lead->name ); ?></strong>
                                </td>
                                <td class="column-email">
                                    <a href="mailto:<?php echo esc_attr( $lead->email ); ?>"><?php echo esc_html( $lead->email ); ?></a>
                                </td>
                                <td class="column-phone">
                                    <?php echo esc_html( $lead->phone ?? '' ); ?>
                                </td>
                                <td class="column-scores">
                                    <div class="score-item">
                                        <span class="score-label">ATS:</span>
                                        <span class="score-value ats-score"><?php echo esc_html( $lead->ats_score ); ?>%</span>
                                    </div>
                                    <div class="score-item">
                                        <span class="score-label">Fit:</span>
                                        <span class="score-value fit-score"><?php echo esc_html( $lead->job_fit_score ); ?>%</span>
                                    </div>
                                </td>
                                <td class="column-resume">
                                    <div class="resume-info">
                                        <div class="filename"><?php echo esc_html( $lead->resume_filename ); ?></div>
                                        <?php
                                        // Use resume_url if available, otherwise construct from pdf_url or fallback to pdf_url
                                        $resume_url = !empty($lead->resume_url) ? $lead->resume_url : '';
                                        
                                        // Fallback: Try to construct from pdf_url if resume_url is empty
                                        if (empty($resume_url) && !empty($lead->pdf_url)) {
                                            // Extract base from PDF URL (remove _report.pdf)
                                            $resume_url = preg_replace('/_report\.pdf$/i', '', $lead->pdf_url);
                                            // Add original file extension
                                            $file_ext = strtolower(pathinfo($lead->resume_filename, PATHINFO_EXTENSION));
                                            if (!empty($file_ext)) {
                                                $resume_url .= '.' . $file_ext;
                                            } else {
                                                // Default to .pdf if extension can't be determined
                                                $resume_url = $lead->pdf_url; // Fallback to PDF URL if we can't construct
                                            }
                                        }
                                        
                                        // Final fallback: use PDF URL if resume URL is still empty
                                        if (empty($resume_url)) {
                                            $resume_url = $lead->pdf_url;
                                        }
                                        ?>
                                        <a href="<?php echo esc_url( $resume_url ); ?>" target="_blank" class="pdf-link">View Resume</a>
                                    </div>
                                </td>
                                <td class="column-created">
                                    <?php echo esc_html( date( 'M j, Y g:i A', strtotime( $lead->created_at ) ) ); ?>
                                </td>
                                <td class="column-actions">
                                    <button type="button" class="button button-small view-lead" data-lead-id="<?php echo esc_attr( $lead->id ); ?>">View</button>
                                    <button type="button" class="button button-small button-link-delete delete-lead" data-lead-id="<?php echo esc_attr( $lead->id ); ?>">Delete</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                
                <!-- Pagination -->
                <?php if ( $leads_data['pages'] > 1 ): ?>
                    <div class="tablenav">
                        <div class="tablenav-pages">
                            <?php
                            $pagination_args = array(
                                'base' => add_query_arg( 'paged', '%#%' ),
                                'format' => '',
                                'prev_text' => '&laquo;',
                                'next_text' => '&raquo;',
                                'total' => $leads_data['pages'],
                                'current' => $page
                            );
                            
                            echo paginate_links( $pagination_args );
                            ?>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
        
        <!-- Lead Detail Modal -->
        <div id="jobready-lead-modal" class="jobready-modal" style="display: none;">
            <div class="jobready-modal-content">
                <span class="jobready-modal-close">&times;</span>
                <div id="jobready-lead-modal-content">
                    <!-- Content will be loaded here -->
                </div>
            </div>
        </div>
    </div>
    
    <style>
        .jobready-stats-dashboard {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
            margin: 20px 0;
        }
        
        .jobready-stat-box {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 1px 1px rgba(0,0,0,.04);
        }
        
        .jobready-stat-box h3 {
            margin: 0 0 10px 0;
            font-size: 14px;
            color: #646970;
        }
        
        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #2271b1;
        }
        
        .jobready-filters {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 20px;
            margin: 20px 0;
        }
        
        .filter-row {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: end;
        }
        
        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .filter-group label {
            font-weight: 500;
            font-size: 12px;
            color: #646970;
        }
        
        .filter-group input {
            min-width: 150px;
        }
        
        .filter-actions {
            display: flex;
            gap: 10px;
            align-items: end;
        }
        
        .jobready-leads-table-container {
            margin-top: 20px;
        }
        
        .column-scores .score-item {
            margin-bottom: 5px;
        }
        
        .score-label {
            font-weight: 500;
            margin-right: 5px;
        }
        
        .score-value {
            font-weight: bold;
        }
        
        .ats-score {
            color: #2271b1;
        }
        
        .fit-score {
            color: #00a32a;
        }
        
        .column-resume .resume-info {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        
        .filename {
            font-size: 12px;
            color: #646970;
        }
        
        .pdf-link {
            font-size: 12px;
        }
        
        .jobready-modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        
        .jobready-modal-content {
            background-color: #fff;
            margin: 5% auto;
            padding: 20px;
            border-radius: 4px;
            width: 80%;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
            position: relative;
        }
        
        .jobready-modal-close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            line-height: 1;
        }
        
        .jobready-modal-close:hover {
            color: #000;
        }
        
        .jobready-no-leads {
            background: #fff;
            border: 1px solid #ccd0d4;
            border-radius: 4px;
            padding: 40px;
            text-align: center;
            color: #646970;
        }
        
        /* Lead details modal styles */
        .lead-details {
            margin: 20px 0;
        }
        
        .detail-row {
            display: flex;
            margin-bottom: 15px;
            align-items: flex-start;
        }
        
        .detail-row.full-width {
            flex-direction: column;
        }
        
        .detail-label {
            font-weight: 600;
            min-width: 120px;
            color: #646970;
            margin-right: 15px;
        }
        
        .detail-value {
            flex: 1;
            word-break: break-word;
        }
        
        .score-badge {
            display: inline-block;
            padding: 4px 8px;
            border-radius: 12px;
            font-weight: bold;
            font-size: 14px;
            color: white;
        }
        
        .score-badge.ats-score {
            background-color: #2271b1;
        }
        
        .score-badge.fit-score {
            background-color: #00a32a;
        }
        
        .job-description-content {
            background: #f6f7f7;
            padding: 15px;
            border-radius: 4px;
            border-left: 4px solid #2271b1;
            max-height: 200px;
            overflow-y: auto;
            white-space: pre-line;
        }
        
        .lead-actions {
            margin-top: 20px;
            padding-top: 20px;
            border-top: 1px solid #dcdcde;
            display: flex;
            gap: 10px;
        }
        
        /* Loading and error states */
        .jobready-loading {
            text-align: center;
            padding: 20px;
            color: #646970;
        }
        
        .jobready-error {
            color: #d63638;
            padding: 10px;
            background: #fcf0f1;
            border: 1px solid #d63638;
            border-radius: 4px;
        }
        
        /* Deleting state */
        tr.deleting {
            opacity: 0.5;
            pointer-events: none;
        }
        
        /* Notice styles */
        .jobready-notice {
            margin: 20px 0;
        }
    </style>
    <?php
}
