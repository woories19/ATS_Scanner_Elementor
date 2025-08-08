<?php
/**
 * Plugin Name: JobReady - An Elementor Widget By Mazin Digital
 * Description: Your personal resume assistant. Upload, scan, and get instant feedback on how job-ready your resume really is.
 * Version: 0.4
 * Author: <a href="https://mazindigital.com">Mazin Digital</a> | <a href="https://github.com/woories19">GitHub</a>
*/

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'JOBREADY_PATH', plugin_dir_path( __FILE__ ) );
define( 'JOBREADY_URL', plugin_dir_url( __FILE__ ) );

// --------------------
// Safe Include Helper
// --------------------
function jobready_safe_include( $file_path, $description ) {
    if ( file_exists( $file_path ) ) {
        require_once $file_path;
    } else {
        error_log( "[JobReady] ERROR: Missing file - {$description} ({$file_path})" );
    }
}

// --------------------
// Load Settings & Scripts (with checks)
// --------------------
jobready_safe_include( JOBREADY_PATH . 'includes/settings-page.php', 'Settings Page' );
jobready_safe_include( JOBREADY_PATH . 'includes/enqueue-scripts.php', 'Enqueue Scripts' );

// --------------------
// Elementor Dependency Check
// --------------------
function jobready_init() {
    // 1. Elementor presence
    if ( ! did_action( 'elementor/loaded' ) ) {
        add_action( 'admin_notices', function() {
            echo '<div class="notice notice-error"><p><strong>JobReady</strong> requires Elementor to be installed and activated.</p></div>';
        });
        error_log( "[JobReady] ERROR: Elementor not loaded" );
        return;
    }

    // 2. Elementor version check
    $required_version = '3.0.0';
    $current_version  = ELEMENTOR_VERSION;

    if ( version_compare( $current_version, $required_version, '<' ) ) {
        add_action( 'admin_notices', function() use ( $required_version, $current_version ) {
            echo '<div class="notice notice-error"><p><strong>JobReady</strong> requires Elementor version ' . esc_html( $required_version ) . ' or higher. Current version: ' . esc_html( $current_version ) . '.</p></div>';
        });
        error_log( "[JobReady] ERROR: Elementor version too low. Required: {$required_version}, Current: {$current_version}" );
        return;
    }

    // 3. Register widget
    add_action( 'elementor/widgets/register', 'jobready_register_widget' );
}
add_action( 'plugins_loaded', 'jobready_init' );

// --------------------
// Register Elementor Widget
// --------------------
function jobready_register_widget( $widgets_manager ) {
    $widget_file = JOBREADY_PATH . 'includes/widget-jobready.php';

    if ( file_exists( $widget_file ) ) {
        require_once $widget_file;

        if ( class_exists( 'JobReady_Widget' ) ) {
            $widgets_manager->register( new \JobReady_Widget() );
            error_log( "[JobReady] SUCCESS: JobReady_Widget registered" );
        } else {
            error_log( "[JobReady] ERROR: JobReady_Widget class not found after including widget-jobready.php" );
        }
    } else {
        error_log( "[JobReady] ERROR: widget-jobready.php file not found in includes/" );
    }
}