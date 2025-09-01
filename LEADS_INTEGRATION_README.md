# JobReady Lead Storage Integration

This document explains the new lead storage system implemented in JobReady v0.7.7+, which provides comprehensive lead management capabilities with optional Google Sheets integration.

## Overview

The lead storage system provides:
- **WordPress Database Storage**: Primary storage for all lead data
- **Admin Interface**: Complete lead management with search, filtering, and export
- **Google Sheets Mirroring**: Optional real-time data mirroring for marketing teams
- **REST API**: Programmatic access to lead data
- **CSV Export**: Easy data export for external tools

## Features

### Lead Data Captured
- Name and email
- ATS and Job Fit scores
- Resume filename and PDF URL
- Job description text
- Consent status
- IP address and user agent
- Timestamps

### Admin Capabilities
- View all leads with pagination
- Search and filter leads
- Export to CSV
- Delete individual leads
- View detailed lead information
- Statistics dashboard

## Installation & Setup

### 1. Automatic Setup
The lead storage system is automatically set up when you activate the JobReady plugin. A new database table `wp_jobready_leads` will be created.

### 2. Admin Access
After activation, you'll find:
- **JobReady Settings** → **Leads** submenu
- New "Leads" page in the WordPress admin

### 3. Google Sheets Integration (Optional)

#### Step 1: Create Google Apps Script
1. Go to [Google Apps Script](https://script.google.com/)
2. Create a new project
3. Copy the code from `google-apps-script-template.js`
4. Save the project

#### Step 2: Deploy as Web App
1. Click "Deploy" → "New deployment"
2. Choose "Web app" as type
3. Set access to "Anyone" (for the endpoint)
4. Deploy and copy the web app URL

#### Step 3: Configure WordPress
1. Go to **JobReady Settings**
2. Paste the Google Apps Script URL in the "Google Sheets URL" field
3. Save settings

## Usage

### Frontend Integration
The lead storage is automatically integrated into the existing JobReady widget. When users submit their resume analysis:

1. Lead data is stored in WordPress database
2. Data is optionally mirrored to Google Sheets (non-blocking)
3. User continues to the thank you page

### Admin Management

#### Viewing Leads
1. Navigate to **JobReady** → **Leads**
2. Use filters to search and filter leads
3. Click "View" to see detailed information
4. Use pagination to navigate through large datasets

#### Exporting Data
1. Apply any desired filters
2. Click "Export CSV" button
3. Download will start automatically

#### Deleting Leads
1. Click "Delete" button on any lead row
2. Confirm deletion
3. Lead is permanently removed

### API Access

#### Store Lead
```http
POST /wp-json/jobready/v1/leads
Content-Type: application/json

{
  "name": "John Doe",
  "email": "john@example.com",
  "ats_score": 85,
  "job_fit_score": 78,
  "pdf_url": "https://example.com/report.pdf",
  "resume_filename": "resume.pdf",
  "job_description": "Software Engineer position...",
  "consent_given": true
}
```

#### Get Leads (Admin Only)
```http
GET /wp-json/jobready/v1/leads?per_page=20&page=1&search=john
```

#### Get Lead Statistics (Admin Only)
```http
GET /wp-json/jobready/v1/leads/stats
```

## Database Schema

```sql
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
    PRIMARY KEY (id),
    KEY email (email),
    KEY created_at (created_at),
    KEY ats_score (ats_score),
    KEY job_fit_score (job_fit_score)
);
```

## Google Sheets Structure

The Google Apps Script automatically creates a spreadsheet with these columns:
1. **Timestamp** - When the lead was submitted
2. **Name** - Lead's name
3. **Email** - Lead's email address
4. **ATS Score** - ATS compatibility score (0-100)
5. **Job Fit Score** - Job match score (0-100)
6. **Resume Filename** - Original resume filename
7. **PDF URL** - Link to the analysis report
8. **Consent Given** - Whether consent was provided

## Customization

### Adding Custom Fields
To add custom fields to the lead storage:

1. **Database**: Modify the `jobready_create_leads_table()` function
2. **REST API**: Update the lead storage endpoint
3. **Admin Interface**: Add fields to the admin table and modal
4. **Frontend**: Include new data in the form submission

### Styling
The admin interface uses WordPress admin styles with custom CSS. Modify the CSS in `leads-admin.php` to customize the appearance.

## Troubleshooting

### Common Issues

#### Database Table Not Created
- Check WordPress error logs
- Ensure the plugin has database write permissions
- Try deactivating and reactivating the plugin

#### Google Sheets Not Receiving Data
- Verify the Google Apps Script URL is correct
- Check that the script is deployed as a web app
- Ensure the script has access to Google Sheets API
- Check WordPress error logs for failed requests

#### REST API Errors
- Verify WordPress REST API is enabled
- Check nonce validation
- Ensure proper permissions for admin endpoints

### Debug Mode
Enable debug logging by adding this to your `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
```

Check the logs at `wp-content/debug.log` for detailed error information.

## Security Considerations

- All lead data is sanitized before storage
- Admin endpoints require `manage_options` capability
- Frontend endpoints are public but validate input data
- IP addresses are stored for audit purposes
- User consent is tracked and stored

## Performance Notes

- Lead storage is non-blocking (doesn't affect user experience)
- Google Sheets mirroring uses short timeouts (5 seconds)
- Database queries are optimized with proper indexing
- CSV export handles large datasets efficiently

## Support

For technical support:
1. Check the WordPress error logs
2. Verify all files are properly included
3. Test with a clean WordPress installation
4. Contact the development team with specific error messages

## Changelog

### v0.7.7
- Initial implementation of lead storage system
- WordPress database integration
- Admin interface for lead management
- Google Sheets mirroring capability
- REST API endpoints
- CSV export functionality
