/**
 * JobReady Google Apps Script Web App
 * 
 * Instructions:
 * 1. Go to https://script.google.com/
 * 2. Create a new project
 * 3. Replace the default code with this template
 * 4. Deploy as a web app
 * 5. Copy the web app URL to your JobReady WordPress settings
 * 
 * This script will automatically create a spreadsheet and append lead data to it.
 */

function doPost(e) {
  try {
    // Parse the incoming JSON data
    const data = JSON.parse(e.postData.contents);
    
    // Get or create the spreadsheet
    const spreadsheet = getOrCreateSpreadsheet();
    const sheet = spreadsheet.getActiveSheet();
    
    // Prepare the row data
    const rowData = [
      data.timestamp || new Date().toISOString(),
      data.name || '',
      data.email || '',
      data.ats_score || '',
      data.job_fit_score || '',
      data.resume_filename || '',
      data.pdf_url || '',
      data.consent_given || 'Yes'
    ];
    
    // Append the data to the sheet
    sheet.appendRow(rowData);
    
    // Return success response
    return ContentService
      .createTextOutput(JSON.stringify({ status: 'success', message: 'Lead data added successfully' }))
      .setMimeType(ContentService.MimeType.JSON);
      
  } catch (error) {
    // Return error response
    return ContentService
      .createTextOutput(JSON.stringify({ 
        status: 'error', 
        message: 'Failed to process lead data: ' + error.toString() 
      }))
      .setMimeType(ContentService.MimeType.JSON);
  }
}

function doGet(e) {
  // Handle GET requests (optional - for testing)
  return ContentService
    .createTextOutput('JobReady Google Apps Script is running. Use POST to submit lead data.')
    .setMimeType(ContentService.MimeType.TEXT);
}

function getOrCreateSpreadsheet() {
  // Try to find existing spreadsheet by name
  const spreadsheetName = 'JobReady Leads';
  let spreadsheet;
  
  try {
    const files = DriveApp.getFilesByName(spreadsheetName);
    if (files.hasNext()) {
      spreadsheet = SpreadsheetApp.open(files.next());
    }
  } catch (e) {
    // File not found or no access
  }
  
  // If no existing spreadsheet, create a new one
  if (!spreadsheet) {
    spreadsheet = SpreadsheetApp.create(spreadsheetName);
    
    // Set up the sheet with headers
    const sheet = spreadsheet.getActiveSheet();
    sheet.setName('Leads');
    
    // Add headers
    const headers = [
      'Timestamp',
      'Name',
      'Email',
      'ATS Score',
      'Job Fit Score',
      'Resume Filename',
      'PDF URL',
      'Consent Given'
    ];
    
    sheet.getRange(1, 1, 1, headers.length).setValues([headers]);
    
    // Format headers
    sheet.getRange(1, 1, 1, headers.length)
      .setFontWeight('bold')
      .setBackground('#f3f3f3')
      .setBorder(true, true, true, true, true, true);
    
    // Auto-resize columns
    sheet.autoResizeColumns(1, headers.length);
    
    // Freeze the header row
    sheet.setFrozenRows(1);
    
    // Add some basic formatting
    sheet.getRange(1, 1, sheet.getMaxRows(), sheet.getMaxColumns())
      .setVerticalAlignment('middle');
    
    // Format timestamp column
    sheet.getRange(2, 1, sheet.getMaxRows() - 1, 1)
      .setNumberFormat('yyyy-mm-dd hh:mm:ss');
    
    // Format score columns
    sheet.getRange(2, 4, sheet.getMaxRows() - 1, 2)
      .setNumberFormat('0');
    
    // Add data validation for consent column
    const consentRange = sheet.getRange(2, 8, sheet.getMaxRows() - 1, 1);
    const consentRule = SpreadsheetApp.newDataValidation()
      .requireValueInList(['Yes', 'No'], true)
      .setAllowInvalid(false)
      .build();
    consentRange.setDataValidation(consentRule);
    
    // Add conditional formatting for scores
    const atsRange = sheet.getRange(2, 4, sheet.getMaxRows() - 1, 1);
    const fitRange = sheet.getRange(2, 5, sheet.getMaxRows() - 1, 1);
    
    // ATS Score formatting: Red (0-50), Yellow (51-75), Green (76-100)
    atsRange.setConditionalFormatRules([
      SpreadsheetApp.newConditionalFormatRule()
        .whenNumberLessThan(51)
        .setBackground('#ffcdd2')
        .setRanges([atsRange])
        .build(),
      SpreadsheetApp.newConditionalFormatRule()
        .whenNumberBetween(51, 75)
        .setBackground('#fff3e0')
        .setRanges([atsRange])
        .build(),
      SpreadsheetApp.newConditionalFormatRule()
        .whenNumberGreaterThan(75)
        .setBackground('#c8e6c9')
        .setRanges([atsRange])
        .build()
    ]);
    
    // Job Fit Score formatting: Red (0-50), Yellow (51-75), Green (76-100)
    fitRange.setConditionalFormatRules([
      SpreadsheetApp.newConditionalFormatRule()
        .whenNumberLessThan(51)
        .setBackground('#ffcdd2')
        .setRanges([fitRange])
        .build(),
      SpreadsheetApp.newConditionalFormatRule()
        .whenNumberBetween(51, 75)
        .setBackground('#fff3e0')
        .setRanges([fitRange])
        .build(),
      SpreadsheetApp.newConditionalFormatRule()
        .whenNumberGreaterThan(75)
        .setBackground('#c8e6c9')
        .setRanges([fitRange])
        .build()
    ]);
    
    // Log the creation
    console.log('Created new JobReady Leads spreadsheet: ' + spreadsheet.getUrl());
  }
  
  return spreadsheet;
}

// Optional: Add a menu to the spreadsheet for manual operations
function onOpen() {
  const ui = SpreadsheetApp.getUi();
  ui.createMenu('JobReady')
    .addItem('Refresh Data', 'refreshData')
    .addItem('Export to CSV', 'exportToCSV')
    .addSeparator()
    .addItem('About', 'showAbout')
    .addToUi();
}

function refreshData() {
  // Refresh any data connections or calculations
  SpreadsheetApp.getActiveSpreadsheet().getActiveSheet().getRange(1, 1).setValue(
    SpreadsheetApp.getActiveSpreadsheet().getActiveSheet().getRange(1, 1).getValue()
  );
}

function exportToCSV() {
  const sheet = SpreadsheetApp.getActiveSheet();
  const data = sheet.getDataRange().getValues();
  const csvContent = data.map(row => 
    row.map(cell => 
      typeof cell === 'string' ? `"${cell.replace(/"/g, '""')}"` : cell
    ).join(',')
  ).join('\n');
  
  const blob = Utilities.newBlob(csvContent, MimeType.CSV, 'jobready-leads.csv');
  const url = URL.createObjectURL(blob);
  
  // Create a temporary download link
  const htmlOutput = HtmlService
    .createHtmlOutput(`
      <html>
        <body>
          <p>Your CSV file is ready for download:</p>
          <a href="${url}" download="jobready-leads.csv">Download CSV</a>
          <script>
            // Auto-download after a short delay
            setTimeout(() => {
              document.querySelector('a').click();
              google.script.host.close();
            }, 1000);
          </script>
        </body>
      </html>
    `)
    .setWidth(300)
    .setHeight(100);
  
  SpreadsheetApp.getUi().showModalDialog(htmlOutput, 'Export CSV');
}

function showAbout() {
  SpreadsheetApp.getUi().alert(
    'JobReady Leads Integration',
    'This spreadsheet automatically receives lead data from your JobReady WordPress plugin.\n\n' +
    'Each time someone uses your resume analyzer, their information will be added to this sheet.\n\n' +
    'For support, contact your website administrator.',
    SpreadsheetApp.getUi().ButtonSet.OK
  );
}
