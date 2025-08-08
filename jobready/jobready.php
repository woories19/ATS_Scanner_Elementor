<?php
/**
 * Plugin Name: JobReady - An Elementor Widget By Mazin Digital
 * Description: Your personal resume assistant. Upload, scan, and get instant feedback on how job-ready your resume really is.
 * Version: 0.4
 * Author: <a href="https://mazindigital.com">Mazin Digital</a> | <a href="https://github.com/woories19">GitHub</a>
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// Constants
define( 'JOBREADY_PATH', plugin_dir_path( __FILE__ ) );
define( 'JOBREADY_URL', plugin_dir_url( __FILE__ ) );
define( 'JOBREADY_LOG_DIR', JOBREADY_PATH . 'assets/logs/' );
define( 'JOBREADY_LOG_FILE', JOBREADY_LOG_DIR . 'error.log' );

// Simple logger (append with timestamp). Attempts to create logs folder.
function jobready_log( $message ) {
    // Normalize message
    $time = gmdate( 'Y-m-d H:i:s' );
    $entry = "[$time] $message\n";

    // Try to create logs dir if missing
    if ( ! file_exists( JOBREADY_LOG_DIR ) ) {
        @mkdir( JOBREADY_LOG_DIR, 0755, true );
        if ( ! file_exists( JOBREADY_LOG_DIR ) ) {
            // Fallback to WP error log
            error_log( "[JobReady] Could not create log dir: " . JOBREADY_LOG_DIR );
            error_log( "[JobReady] $entry" );
            return;
        }
    }

    // Append to file
    @file_put_contents( JOBREADY_LOG_FILE, $entry, FILE_APPEND | LOCK_EX );
}

// Boot sequence: load includes if present
jobready_log( 'Boot: JobReady plugin initializing.' );

$includes = [
    'includes/settings-page.php',
    'includes/enqueue-scripts.php',
    // widget is loaded later inside jobready_init after Elementor checks
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

// Initialize plugin only after plugins_loaded
function jobready_init() {
    jobready_log( 'jobready_init called.' );

    // Check Elementor
    if ( ! did_action( 'elementor/loaded' ) ) {
        add_action( 'admin_notices', function() {
            echo '<div class="notice notice-warning"><p><strong>JobReady:</strong> Elementor is not active. The JobReady widget requires Elementor.</p></div>';
        } );
        jobready_log( 'Elementor not loaded. Widget will not be registered.' );
        return;
    }

    // Version check for Elementor if constant exists
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

    // Register widget registration hook
    add_action( 'elementor/widgets/register', 'jobready_register_widget_safe' );
    jobready_log( 'Registered elementor/widgets/register hook.' );
}
add_action( 'plugins_loaded', 'jobready_init' );

// Safe widget registration function
function jobready_register_widget_safe( $widgets_manager ) {
    $widget_file = JOBREADY_PATH . 'includes/widget-jobready.php';
    if ( ! file_exists( $widget_file ) ) {
        jobready_log( 'widget-jobready.php not found when attempting to register widget.' );
        return;
    }

    require_once $widget_file;

    if ( class_exists( 'JobReady_Widget' ) ) {
        try {
            $widgets_manager->register( new \JobReady_Widget() );
            jobready_log( 'JobReady_Widget registered successfully.' );
        } catch ( Exception $e ) {
            jobready_log( 'Exception registering widget: ' . $e->getMessage() );
        }
    } else {
        jobready_log( 'JobReady_Widget class does not exist after include.' );
    }
}