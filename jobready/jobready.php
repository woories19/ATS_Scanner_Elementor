<?php
/*
 * Plugin Name: JobReady - Mazin Digital
 * Plugin URI:  https://github.com/woories19/ATS_Scanner_Elementor/tree/plugin_only
 * Description: Your personal resume assistant. Upload, scan, and get instant feedback on how job-ready your resume really is.
 * Author: GitHub
 * Author URI: https://github.com/woories19
 * Version: 0.8.9.8
 * Requires Plugins: elementor
 * Elementor tested up to: 3.32
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Constants
define( 'JOBREADY_PATH', plugin_dir_path( __FILE__ ) );
define( 'JOBREADY_URL', plugin_dir_url( __FILE__ ) );
define( 'JOBREADY_LOG_DIR', JOBREADY_PATH . 'assets/logs/' );
define( 'JOBREADY_LOG_FILE', JOBREADY_LOG_DIR . 'error.log' );

function jobready_log( $message ) {
    $time = gmdate( 'Y-m-d H:i:s' );
    $entry = "[$time] $message\n";
    if ( ! file_exists( JOBREADY_LOG_DIR ) ) {
        @mkdir( JOBREADY_LOG_DIR, 0755, true );
        if ( ! file_exists( JOBREADY_LOG_DIR ) ) {
            error_log( "[JobReady] Could not create log dir: " . JOBREADY_LOG_DIR );
            error_log( "[JobReady] $entry" );
            return;
        }
    }
    @file_put_contents( JOBREADY_LOG_FILE, $entry, FILE_APPEND | LOCK_EX );
}

jobready_log( 'Boot: JobReady plugin initializing.' );

// Create database table on plugin activation
register_activation_hook( __FILE__, 'jobready_create_leads_table' );

$includes = [
    'includes/settings-page.php',
    'includes/enqueue-scripts.php',
    'includes/rest-endpoint.php',
    'includes/database.php',
    'includes/leads-endpoint.php',
    'includes/leads-admin.php',
];

foreach ( $includes as $inc ) {
    $path = JOBREADY_PATH . $inc;
    if ( file_exists( $path ) ) {
        require_once $path;
        jobready_log( "Included: $inc" );
    } else {
        jobready_log( "Missing include file: $inc" );
    }
}

function jobready_init() {
    jobready_log( 'jobready_init called.' );

    if ( ! did_action( 'elementor/loaded' ) ) {
        add_action( 'admin_notices', function() {
            echo '<div class="notice notice-warning"><p><strong>JobReady:</strong> Elementor is not active. The JobReady widget requires Elementor.</p></div>';
        } );
        jobready_log( 'Elementor not loaded. Widget will not be registered.' );
        return;
    }

    if ( defined( 'ELEMENTOR_VERSION' ) ) {
        $required = '3.0.0';
        if ( version_compare( ELEMENTOR_VERSION, $required, '<' ) ) {
            add_action( 'admin_notices', function() use ( $required ) {
                echo '<div class="notice notice-error"><p><strong>JobReady:</strong> Requires Elementor >= ' . esc_html( $required ) . '.</p></div>';
            } );
            jobready_log( "Elementor version too low: " . ELEMENTOR_VERSION );
            return;
        }
    }

    add_action( 'elementor/widgets/register', function( $widgets_manager ) {
        $widget_upload = JOBREADY_PATH . 'includes/widget-jobready.php';
        if ( file_exists( $widget_upload ) ) {
            require_once $widget_upload;
            if ( class_exists( 'JobReady_Widget' ) ) {
                try { $widgets_manager->register( new \JobReady_Widget() ); } catch ( Exception $e ) {
                    jobready_log( 'Exception registering JobReady_Widget: ' . $e->getMessage() );
                }
            }
        }
    } );
}
add_action( 'plugins_loaded', 'jobready_init' );