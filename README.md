# JobReady - Resume Analyzer & Lead Generator

**Version: 0.8.8**

JobReady is a comprehensive WordPress plugin that transforms your website into a powerful resume analysis and lead generation tool. Built as an Elementor widget, it allows users to upload their resumes and receive instant feedback on ATS compatibility, job fit scores, and detailed improvement suggestions.

---

## 🚀 Features

### Core Resume Analysis
- **Multi-format Support**: Upload PDF or DOCX resume files (up to 5MB)
- **Real-time ATS Scoring**: Instant analysis with animated progress visualization
- **Job Fit Analysis**: Compare resume against specific job descriptions
- **Comprehensive Feedback**: Section-wise analysis (contact info, experience, skills, etc.)
- **Keyword Extraction**: Automatic keyword identification and matching
- **PDF Report Generation**: Detailed analysis report with actionable insights

### Lead Generation & Management
- **Smart Lead Capture**: Collect user information during analysis process
- **Database Storage**: Secure WordPress database storage for all leads
- **Admin Dashboard**: Complete leads management interface with filtering and search
- **CSV Export**: Export leads data for external analysis
- **Lead Statistics**: Track conversion rates and engagement metrics

### Email Automation
- **Automatic Email Dispatch**: Send analysis reports directly to users
- **PDF Attachments**: Include full analysis report as email attachment
- **Customizable Templates**: Professional email templates with branding
- **Multiple Delivery Methods**: REST API, AJAX fallback, and form submission
- **Delivery Tracking**: Monitor email success/failure rates

### Integration & Connectivity
- **Google Sheets Integration**: Mirror leads data to Google Sheets automatically
- **REST API Endpoints**: Full API for external integrations
- **Webhook Support**: Secure token-based webhook authentication
- **Elementor Integration**: Native Elementor widget with drag-and-drop functionality
- **Responsive Design**: Mobile-friendly interface across all devices

### Security & Performance
- **File Validation**: Secure file upload with type and size validation
- **CSRF Protection**: WordPress nonce verification for all requests
- **Error Logging**: Comprehensive logging for debugging and monitoring
- **Rate Limiting**: Built-in protection against abuse
- **Data Sanitization**: All user inputs properly sanitized and validated

---

## 🛠️ Tech Stack

### Frontend
- **WordPress**: Core CMS platform
- **Elementor**: Page builder integration
- **jQuery**: JavaScript framework
- **HTML5/CSS3**: Modern web standards
- **Responsive Design**: Mobile-first approach

### Backend
- **PHP 7.4+**: WordPress plugin development
- **MySQL**: Database storage
- **REST API**: WordPress REST API endpoints
- **Python Flask**: External analysis engine (optional)

### File Processing
- **PDF Support**: PDF parsing and analysis
- **DOCX Support**: Microsoft Word document processing
- **File Upload**: Secure file handling with validation

---

## 📦 Installation

### Prerequisites
- WordPress 5.0 or higher
- Elementor 3.0.0 or higher
- PHP 7.4 or higher
- MySQL 5.7 or higher

### Installation Steps

1. **Download the Plugin**
   ```bash
   git clone https://github.com/your-repo/ATS_Scanner_Elementor.git
   # Or download the ZIP file from releases
   ```

2. **Upload to WordPress**
   - Upload the `jobready` folder to `/wp-content/plugins/`
   - Or use WordPress admin → Plugins → Add New → Upload Plugin

3. **Activate the Plugin**
   - Go to WordPress admin → Plugins
   - Find "JobReady" and click "Activate"

4. **Configure Settings**
   - Go to JobReady → Settings in WordPress admin
   - Set your API URL (if using external analysis)
   - Configure webhook token for security
   - Set up Google Sheets integration (optional)

5. **Add to Your Page**
   - Edit any page with Elementor
   - Search for "JobReady" widget
   - Drag and drop onto your page
   - Configure widget settings as needed

---

## ⚙️ Configuration

### Plugin Settings

Navigate to **JobReady → Settings** in WordPress admin:

#### API Configuration
- **Default API URL**: Set your backend analysis API endpoint
- **Webhook Token**: Security token for API communication
- **Google Sheets URL**: Optional Google Apps Script webhook URL

#### Widget Settings
- **API URL Override**: Per-widget API endpoint configuration
- **Custom Styling**: Widget appearance customization
- **Form Fields**: Customize required fields and validation

### Database Setup

The plugin automatically creates required database tables on activation:
- `wp_jobready_leads`: Stores lead information and analysis results
- Automatic indexing for performance optimization

---

## 📊 Admin Features

### Leads Management

Access via **JobReady → Leads** in WordPress admin:

#### Lead Dashboard
- **Lead List**: View all captured leads with pagination
- **Search & Filter**: Find leads by email, date range, or keywords
- **Sort Options**: Sort by date, score, or other criteria
- **Bulk Actions**: Delete multiple leads at once

#### Data Export
- **CSV Export**: Download leads data in CSV format
- **Filtered Export**: Export only filtered results
- **Date Range Export**: Export leads from specific time periods

#### Analytics
- **Conversion Tracking**: Monitor form completion rates
- **Score Distribution**: View ATS and job fit score trends
- **Email Success Rates**: Track email delivery statistics

### Email Management

#### Email Templates
- **Customizable Content**: Edit email subject and body
- **Branding Options**: Add your logo and company information
- **Dynamic Variables**: Use lead data in email content

#### Delivery Monitoring
- **Success Tracking**: Monitor email delivery success
- **Failure Logging**: Track and debug failed email attempts
- **Retry Logic**: Automatic retry for failed deliveries

---

## 🔌 API Reference

### REST Endpoints

#### Store Lead
```http
POST /wp-json/jobready/v1/leads
Content-Type: application/json
X-WP-Nonce: {nonce}

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

#### Send Email Report
```http
POST /wp-json/jobready/v1/send-report
Content-Type: application/json
X-WP-Nonce: {nonce}
X-JobReady-Token: {token}

{
  "name": "John Doe",
  "email": "john@example.com",
  "ats_score": 85,
  "job_fit_score": 78,
  "pdf_url": "https://example.com/report.pdf"
}
```

#### Get Leads (Admin Only)
```http
GET /wp-json/jobready/v1/leads?per_page=20&page=1&search=john
Authorization: Bearer {token}
```

### Webhook Integration

#### Google Sheets Integration
```javascript
// Google Apps Script webhook
function doPost(e) {
  const data = JSON.parse(e.postData.contents);
  const sheet = SpreadsheetApp.getActiveSpreadsheet().getActiveSheet();
  
  sheet.appendRow([
    data.timestamp,
    data.name,
    data.email,
    data.ats_score,
    data.job_fit_score,
    data.resume_filename,
    data.pdf_url,
    data.consent_given
  ]);
  
  return ContentService.createTextOutput('Success');
}
```

---

## 📁 File Structure

```
jobready/
├── assets/
│   ├── css/
│   │   └── styles.css              # Main stylesheet
│   ├── js/
│   │   ├── script.js              # Frontend JavaScript
│   │   └── leads-admin.js         # Admin interface JavaScript
│   ├── logs/
│   │   └── error.log              # Error logging
│   └── templates/
│       └── thank-you-template.php # Email templates
├── includes/
│   ├── database.php               # Database operations
│   ├── enqueue-scripts.php        # Asset loading
│   ├── leads-admin.php            # Admin interface
│   ├── leads-endpoint.php         # REST API endpoints
│   ├── rest-endpoint.php          # Email endpoints
│   ├── settings-page.php          # Plugin settings
│   └── widget-jobready.php        # Elementor widget
├── jobready.php                   # Main plugin file
└── README.md                      # This file
```

---

## 🐛 Troubleshooting

### Common Issues

#### Email Not Sending
1. Check WordPress email configuration
2. Verify webhook token settings
3. Check error logs in `assets/logs/error.log`
4. Test with fallback email methods

#### File Upload Issues
1. Verify file size limits (max 5MB)
2. Check file type (PDF/DOCX only)
3. Ensure proper file permissions
4. Check server upload settings

#### API Connection Problems
1. Verify API URL configuration
2. Check network connectivity
3. Validate webhook token
4. Review API endpoint logs

### Debug Mode

Enable debug logging by setting `CONFIG.DEBUG = true` in `assets/js/script.js`:

```javascript
const CONFIG = {
    DEBUG: true, // Enable debug logging
    // ... other config
};
```

### Error Logs

Check error logs at:
- **WordPress**: `/wp-content/debug.log`
- **Plugin**: `/wp-content/plugins/jobready/assets/logs/error.log`
- **Browser Console**: F12 → Console tab

---

## 🔄 Changelog

### Version 0.8.8 (Current)
- ✅ **Fixed**: Email dispatch URL configuration issue
- ✅ **Improved**: REST API endpoint reliability
- ✅ **Enhanced**: Admin interface with better filtering
- ✅ **Added**: Comprehensive error logging
- ✅ **Updated**: Email template system

### Version 0.8.7
- ✅ **Added**: Google Sheets integration
- ✅ **Enhanced**: Lead management dashboard
- ✅ **Improved**: Email delivery reliability
- ✅ **Fixed**: File upload validation issues

### Version 0.8.0
- ✅ **Added**: Complete lead generation system
- ✅ **Implemented**: Database storage for leads
- ✅ **Created**: Admin interface for lead management
- ✅ **Added**: Email automation system

---

## 🛣️ Roadmap

### Completed ✅
- [x] Basic resume upload and analysis
- [x] ATS scoring algorithm
- [x] Job description comparison
- [x] PDF report generation
- [x] Lead generation system
- [x] Database storage
- [x] Admin dashboard
- [x] Email automation
- [x] Google Sheets integration
- [x] REST API endpoints
- [x] Security improvements

### Planned 🚧
- [ ] **Multilingual Support**: Internationalization (i18n)
- [ ] **Advanced Analytics**: Detailed performance metrics
- [ ] **Custom Email Templates**: Drag-and-drop template builder
- [ ] **API Rate Limiting**: Enhanced security measures
- [ ] **Mobile App**: Native mobile application
- [ ] **White-label Solution**: Customizable branding options
- [ ] **Advanced Reporting**: Custom report generation
- [ ] **Integration Hub**: Third-party CRM integrations

---

## 🤝 Contributing

We welcome contributions! Here's how you can help:

### Development Setup
1. Fork the repository
2. Create a feature branch: `git checkout -b feature/amazing-feature`
3. Make your changes
4. Test thoroughly
5. Submit a pull request

### Code Standards
- Follow WordPress coding standards
- Use meaningful commit messages
- Include proper documentation
- Test on multiple WordPress versions

### Reporting Issues
- Use GitHub Issues for bug reports
- Include detailed reproduction steps
- Provide error logs and screenshots
- Specify WordPress and plugin versions

---

## 📄 License

This project is licensed under the [MIT License](LICENSE).

---

## 🌐 Live Demo

Experience JobReady in action:  
**[https://resume.mazindigital.com/](https://resume.mazindigital.com/)**

---

## 📞 Support

- **Documentation**: [GitHub Wiki](https://github.com/your-repo/wiki)
- **Issues**: [GitHub Issues](https://github.com/your-repo/issues)
- **Email**: support@mazindigital.com
- **Website**: [https://mazindigital.com](https://mazindigital.com)

---

**Built with ❤️ by [Mazin Digital](https://mazindigital.com)**
