<?php
/**
 * JobReady Email Test Script
 * 
 * This script tests the email functionality directly
 * Place this file in your WordPress root directory and access it via browser
 * 
 * Usage: https://yourdomain.com/test-email.php
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

// Test email functionality
if (isset($_POST['test_email'])) {
    $test_email = sanitize_email($_POST['test_email']);
    $subject = 'JobReady Email Test';
    $body = '<p>This is a test email from JobReady to verify email functionality is working.</p>';
    $headers = array('Content-Type: text/html; charset=UTF-8');
    
    $sent = wp_mail($test_email, $subject, $body, $headers);
    
    if ($sent) {
        $message = "✅ Test email sent successfully to: $test_email";
        $message_class = "success";
    } else {
        $message = "❌ Test email failed to send to: $test_email";
        $message_class = "error";
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>JobReady Email Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .test-section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .success { background: #d4edda; border-color: #c3e6cb; color: #155724; }
        .error { background: #f8d7da; border-color: #f5c6cb; color: #721c24; }
        .info { background: #d1ecf1; border-color: #bee5eb; }
        button { padding: 10px 15px; margin: 5px; cursor: pointer; }
        input[type="email"] { padding: 8px; width: 300px; margin: 5px; }
        .result { margin-top: 10px; }
    </style>
</head>
<body>
    <h1>JobReady Email Test</h1>
    
    <div class="test-section info">
        <h3>Email Configuration Check</h3>
        <p><strong>WordPress Email Function:</strong> <?php echo function_exists('wp_mail') ? 'Available' : 'Not Available'; ?></p>
        <p><strong>SMTP Configuration:</strong> Check if you have an SMTP plugin installed</p>
        <p><strong>Server Email:</strong> <?php echo get_option('admin_email', 'Not set'); ?></p>
    </div>

    <?php if (isset($message)): ?>
    <div class="test-section <?php echo $message_class; ?>">
        <h3>Test Result</h3>
        <p><?php echo esc_html($message); ?></p>
    </div>
    <?php endif; ?>

    <div class="test-section">
        <h3>Test Email Functionality</h3>
        <p>Enter an email address to test if WordPress email is working:</p>
        <form method="post">
            <input type="email" name="test_email" placeholder="test@example.com" required />
            <button type="submit" name="test_email_submit">Send Test Email</button>
        </form>
    </div>

    <div class="test-section">
        <h3>Email Troubleshooting</h3>
        <ul>
            <li><strong>If email fails:</strong> Install and configure an SMTP plugin like "WP Mail SMTP"</li>
            <li><strong>Check spam folder:</strong> Test emails might go to spam</li>
            <li><strong>Server configuration:</strong> Some hosting providers block outgoing emails</li>
            <li><strong>SMTP settings:</strong> Use Gmail, SendGrid, or your hosting provider's SMTP</li>
        </ul>
    </div>

    <div class="test-section">
        <h3>Recommended SMTP Setup</h3>
        <p>For reliable email delivery, install "WP Mail SMTP" plugin and configure it with:</p>
        <ul>
            <li><strong>Gmail SMTP:</strong> smtp.gmail.com, port 587</li>
            <li><strong>SendGrid:</strong> smtp.sendgrid.net, port 587</li>
            <li><strong>Hosting Provider:</strong> Check your hosting provider's SMTP settings</li>
        </ul>
    </div>
</body>
</html>
