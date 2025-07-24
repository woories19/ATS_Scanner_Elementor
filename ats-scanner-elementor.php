<?php
/**
 * Plugin Name: JobReady by Mazin Digital
 * Description: Your personal resume assistant. Upload, scan, and get instant feedback on how job-ready your resume really is.
 * Version: 1.0.7
 * Author: github.com/woories19
 */

if (!defined('ABSPATH')) exit;

final class ATS_Scanner_Elementor {
    const VERSION = '1.0.0';
    const MINIMUM_ELEMENTOR_VERSION = '3.0.0';
    const MINIMUM_PHP_VERSION = '7.4';

    public function __construct() {
        add_action('plugins_loaded', [$this, 'init']);
    }

    public function init() {
        // Check for required Elementor version
        if (!did_action('elementor/loaded')) {
            add_action('admin_notices', [$this, 'admin_notice_missing_elementor']);
            return;
        }

        // Add Plugin actions
        add_action('elementor/widgets/register', [$this, 'register_widgets']);
    }

    public function register_widgets($widgets_manager) {
        require_once(__DIR__ . '/includes/widgets/ats-scanner-widget.php');
        $widgets_manager->register(new \ATS_Scanner_Widget());
    }
}

new ATS_Scanner_Elementor();