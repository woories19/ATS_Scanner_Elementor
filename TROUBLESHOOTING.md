# JobReady Integration Troubleshooting Guide

This guide helps you diagnose and fix issues with the WordPress plugin and Python webapp integration.

## Common Issues and Solutions

### 1. **Leads Not Appearing in WordPress Admin**

**Symptoms:**
- User completes analysis but no entry appears in JobReady → Leads
- No error messages shown to user

**Diagnosis:**
1. Check WordPress error logs (`wp-content/debug.log`)
2. Look for `[JobReady]` entries in the logs
3. Test the REST API endpoint manually

**Solutions:**
1. **Database Table Missing:**
   ```php
   // Deactivate and reactivate the plugin to recreate the table
   // Or manually run this SQL:
   CREATE TABLE wp_jobready_leads (
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
       PRIMARY KEY (id)
   );
   ```

2. **REST API Not Working:**
   - Check if WordPress REST API is enabled
   - Verify nonce validation
   - Check CORS settings

3. **JavaScript Errors:**
   - Open browser console (F12)
   - Look for JavaScript errors
   - Check if `jobreadyRest` object is available

### 2. **Email Not Being Sent**

**Symptoms:**
- User doesn't receive PDF report email
- No error messages shown

**Diagnosis:**
1. Check WordPress error logs for email-related errors
2. Verify email configuration in WordPress
3. Test email endpoint manually

**Solutions:**
1. **WordPress Email Configuration:**
   - Install and configure an SMTP plugin (WP Mail SMTP)
   - Check if `wp_mail()` function works
   - Verify email settings in WordPress admin

2. **Token Authentication:**
   - Set a webhook token in JobReady Settings
   - Ensure the token is being sent in the request header
   - Check token validation in the email endpoint

3. **PDF Download Issues:**
   - Verify the PDF URL is accessible
   - Check if `download_url()` function is available
   - Ensure proper file permissions

### 3. **Python API Connection Issues**

**Symptoms:**
- "Network error" or timeout messages
- Analysis fails to complete

**Diagnosis:**
1. Check if PythonAnywhere app is running
2. Verify API URL configuration
3. Test API endpoint directly

**Solutions:**
1. **API URL Configuration:**
   - Set correct API URL in JobReady Settings
   - Ensure no trailing slash in the URL
   - Test the URL in browser

2. **CORS Issues:**
   - Update `ALLOWED_ORIGINS` in `flask_app.py`
   - Add your domain to the allowed origins list

3. **PythonAnywhere Issues:**
   - Check if the webapp is running
   - Verify the domain configuration
   - Check PythonAnywhere error logs

### 4. **Integration Status Not Showing**

**Symptoms:**
- Thank you page doesn't show integration status
- No feedback about WordPress/email success

**Solutions:**
1. **URL Parameters:**
   - Check if `wp` and `email` parameters are being passed
   - Verify the thank you page template is updated

2. **Template Issues:**
   - Ensure the thank you template is being used
   - Check if the page is using the correct template

## Testing and Debugging

### 1. **Use the Test Script**

Place `test-integration.php` in your WordPress root directory and access it via browser:
```
https://yourdomain.com/test-integration.php
```

This script will help you:
- Check configuration settings
- Test lead storage
- Test email dispatch
- Verify database connectivity
- View recent leads

### 2. **Enable Debug Logging**

Add this to your `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

Then check `wp-content/debug.log` for detailed error messages.

### 3. **Browser Console Debugging**

Open browser developer tools (F12) and check:
- Console for JavaScript errors
- Network tab for failed requests
- Application tab for localStorage/sessionStorage issues

### 4. **Manual API Testing**

Test the REST endpoints manually using curl or Postman:

**Test Lead Storage:**
```bash
curl -X POST https://yourdomain.com/wp-json/jobready/v1/leads \
  -H "Content-Type: application/json" \
  -H "X-WP-Nonce: YOUR_NONCE" \
  -d '{
    "name": "Test User",
    "email": "test@example.com",
    "ats_score": 85,
    "job_fit_score": 78,
    "pdf_url": "https://example.com/test.pdf",
    "resume_filename": "test.pdf",
    "job_description": "Test job description"
  }'
```

**Test Email Dispatch:**
```bash
curl -X POST https://yourdomain.com/wp-json/jobready/v1/send-report \
  -H "Content-Type: application/json" \
  -H "X-WP-Nonce: YOUR_NONCE" \
  -H "X-JobReady-Token: YOUR_TOKEN" \
  -d '{
    "name": "Test User",
    "email": "test@example.com",
    "ats_score": 85,
    "job_fit_score": 78,
    "pdf_url": "https://example.com/test.pdf"
  }'
```

## Configuration Checklist

### WordPress Settings
- [ ] API URL configured in JobReady Settings
- [ ] Webhook token set (optional but recommended)
- [ ] Google Sheets URL configured (optional)
- [ ] WordPress REST API enabled
- [ ] Debug logging enabled

### PythonAnywhere Settings
- [ ] Webapp is running and accessible
- [ ] CORS origins include your domain
- [ ] OpenAI API key configured
- [ ] Upload and logs directories exist with proper permissions

### Email Configuration
- [ ] WordPress email is working (test with wp_mail)
- [ ] SMTP plugin installed and configured (recommended)
- [ ] Webhook token matches between WordPress and Python

## Common Error Messages

### "API URL not configured"
- Set the API URL in JobReady Settings

### "Token mismatch - unauthorized"
- Set a webhook token in JobReady Settings
- Ensure the token is being sent in the request header

### "Failed to store lead"
- Check database table exists
- Verify database permissions
- Check WordPress error logs

### "Email send failed"
- Configure WordPress email properly
- Install SMTP plugin
- Check email server settings

### "Network error"
- Verify PythonAnywhere app is running
- Check API URL configuration
- Test API endpoint directly

## Getting Help

If you're still experiencing issues:

1. **Collect Debug Information:**
   - WordPress error logs
   - Browser console errors
   - Network request failures
   - Test script results

2. **Check Configuration:**
   - All settings in JobReady Settings
   - PythonAnywhere configuration
   - Email server settings

3. **Test Each Component:**
   - Test Python API independently
   - Test WordPress REST endpoints
   - Test email functionality

4. **Contact Support:**
   - Provide detailed error messages
   - Include configuration details
   - Share test script results
