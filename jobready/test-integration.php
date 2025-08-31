<?php
/**
 * JobReady Integration Test Script
 * 
 * This script helps test the WordPress integration endpoints
 * Place this file in your WordPress root directory and access it via browser
 * 
 * Usage: https://yourdomain.com/test-integration.php
 */

// Prevent direct access if not in WordPress context
if (!defined('ABSPATH')) {
    // If not in WordPress, try to bootstrap it
    $wp_load = dirname(__FILE__) . '/wp-load.php';
    if (file_exists($wp_load)) {
        require_once($wp_load);
    } else {
        die('WordPress not found. Please place this file in your WordPress root directory.');
    }
}

// Check if user can manage options
if (!current_user_can('manage_options')) {
    wp_die('You do not have sufficient permissions to access this page.');
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>JobReady Integration Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .success { background: #d4edda; border-color: #c3e6cb; }
        .error { background: #f8d7da; border-color: #f5c6cb; }
        .info { background: #d1ecf1; border-color: #bee5eb; }
        button { padding: 10px 15px; margin: 5px; cursor: pointer; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 3px; overflow-x: auto; }
        .result { margin-top: 10px; }
    </style>
</head>
<body>
    <h1>JobReady Integration Test</h1>
    
    <div class="test-section info">
        <h3>Configuration Check</h3>
        <p><strong>API URL:</strong> <?php echo esc_html(get_option('jobready_api_url', 'Not set')); ?></p>
        <p><strong>Webhook Token:</strong> <?php echo esc_html(get_option('jobready_webhook_token', 'Not set')); ?></p>
        <p><strong>Google Sheets URL:</strong> <?php echo esc_html(get_option('jobready_google_sheets_url', 'Not set')); ?></p>
        <p><strong>REST API Base:</strong> <?php echo esc_html(rest_url('jobready/v1/')); ?></p>
    </div>

    <div class="test-section">
        <h3>Test Lead Storage</h3>
        <p>Test storing a lead in the WordPress database:</p>
        <button onclick="testLeadStorage()">Test Lead Storage</button>
        <div id="lead-result" class="result"></div>
    </div>

    <div class="test-section">
        <h3>Test Email Dispatch</h3>
        <p>Test the email dispatch endpoint:</p>
        <button onclick="testEmailDispatch()">Test Email Dispatch</button>
        <div id="email-result" class="result"></div>
    </div>

    <div class="test-section">
        <h3>Test Database Connection</h3>
        <p>Check if the leads table exists and is accessible:</p>
        <button onclick="testDatabase()">Test Database</button>
        <div id="db-result" class="result"></div>
    </div>

    <div class="test-section">
        <h3>View Recent Leads</h3>
        <p>Show the 5 most recent leads:</p>
        <button onclick="viewRecentLeads()">View Recent Leads</button>
        <div id="leads-result" class="result"></div>
    </div>

    <script>
        function testLeadStorage() {
            const resultDiv = document.getElementById('lead-result');
            resultDiv.innerHTML = '<p>Testing...</p>';
            
            const testData = {
                name: 'Test User',
                email: 'test@example.com',
                ats_score: 85,
                job_fit_score: 78,
                pdf_url: 'https://example.com/test.pdf',
                resume_filename: 'test_resume.pdf',
                job_description: 'Test job description for integration testing.',
                consent_given: true
            };
            
            fetch('<?php echo esc_url(rest_url('jobready/v1/leads')); ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': '<?php echo wp_create_nonce('wp_rest'); ?>'
                },
                body: JSON.stringify(testData)
            })
            .then(response => response.json())
            .then(data => {
                resultDiv.innerHTML = '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
                resultDiv.className = 'result success';
            })
            .catch(error => {
                resultDiv.innerHTML = '<pre>Error: ' + error.message + '</pre>';
                resultDiv.className = 'result error';
            });
        }

        function testEmailDispatch() {
            const resultDiv = document.getElementById('email-result');
            resultDiv.innerHTML = '<p>Testing...</p>';
            
            const testData = {
                name: 'Test User',
                email: 'test@example.com',
                ats_score: 85,
                job_fit_score: 78,
                pdf_url: 'https://example.com/test.pdf'
            };
            
            const headers = {
                'Content-Type': 'application/json',
                'X-WP-Nonce': '<?php echo wp_create_nonce('wp_rest'); ?>'
            };
            
            // Add token if configured
            const token = '<?php echo esc_js(get_option('jobready_webhook_token', '')); ?>';
            if (token) {
                headers['X-JobReady-Token'] = token;
            }
            
            fetch('<?php echo esc_url(rest_url('jobready/v1/send-report')); ?>', {
                method: 'POST',
                headers: headers,
                body: JSON.stringify(testData)
            })
            .then(response => response.json())
            .then(data => {
                resultDiv.innerHTML = '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
                resultDiv.className = 'result success';
            })
            .catch(error => {
                resultDiv.innerHTML = '<pre>Error: ' + error.message + '</pre>';
                resultDiv.className = 'result error';
            });
        }

        function testDatabase() {
            const resultDiv = document.getElementById('db-result');
            resultDiv.innerHTML = '<p>Testing...</p>';
            
            fetch('<?php echo esc_url(rest_url('jobready/v1/leads/stats')); ?>', {
                method: 'GET',
                headers: {
                    'X-WP-Nonce': '<?php echo wp_create_nonce('wp_rest'); ?>'
                }
            })
            .then(response => response.json())
            .then(data => {
                resultDiv.innerHTML = '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
                resultDiv.className = 'result success';
            })
            .catch(error => {
                resultDiv.innerHTML = '<pre>Error: ' + error.message + '</pre>';
                resultDiv.className = 'result error';
            });
        }

        function viewRecentLeads() {
            const resultDiv = document.getElementById('leads-result');
            resultDiv.innerHTML = '<p>Loading...</p>';
            
            fetch('<?php echo esc_url(rest_url('jobready/v1/leads?per_page=5')); ?>', {
                method: 'GET',
                headers: {
                    'X-WP-Nonce': '<?php echo wp_create_nonce('wp_rest'); ?>'
                }
            })
            .then(response => response.json())
            .then(data => {
                resultDiv.innerHTML = '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
                resultDiv.className = 'result success';
            })
            .catch(error => {
                resultDiv.innerHTML = '<pre>Error: ' + error.message + '</pre>';
                resultDiv.className = 'result error';
            });
        }
    </script>
</body>
</html>
