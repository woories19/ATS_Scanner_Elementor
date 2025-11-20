/**
 * JobReady Leads Admin JavaScript
 * Handles admin interface interactions for managing leads
 */

(function($) {
    'use strict';

    const JobReadyLeadsAdmin = {
        init: function() {
            this.bindEvents();
            this.initDatepickers();
        },

        bindEvents: function() {
            // View lead details
            $(document).on('click', '.view-lead', function(e) {
                e.preventDefault();
                const leadId = $(this).data('lead-id');
                JobReadyLeadsAdmin.viewLead(leadId);
            });

            // Delete lead
            $(document).on('click', '.delete-lead', function(e) {
                e.preventDefault();
                const leadId = $(this).data('lead-id');
                JobReadyLeadsAdmin.deleteLead(leadId);
            });

            // Close modal
            $(document).on('click', '.jobready-modal-close', function(e) {
                e.preventDefault();
                JobReadyLeadsAdmin.closeModal();
            });

            // Close modal when clicking outside
            $(document).on('click', '.jobready-modal', function(e) {
                if (e.target === this) {
                    JobReadyLeadsAdmin.closeModal();
                }
            });

            // Close modal with Escape key
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape') {
                    JobReadyLeadsAdmin.closeModal();
                }
            });
        },

        initDatepickers: function() {
            // Initialize date pickers if jQuery UI is available
            if ($.fn.datepicker) {
                $('#date_from, #date_to').datepicker({
                    dateFormat: 'yy-mm-dd',
                    changeMonth: true,
                    changeYear: true,
                    yearRange: '-2:+1'
                });
            }
        },

        viewLead: function(leadId) {
            const $modal = $('#jobready-lead-modal');
            const $content = $('#jobready-lead-modal-content');
            
            // Show loading
            $content.html('<div class="jobready-loading">Loading lead details...</div>');
            $modal.show();
            
            // Debug logging
            console.log('JobReady: Fetching lead details for ID:', leadId);
            console.log('JobReady: REST URL:', jobreadyLeadsAdmin.restUrl + '/' + leadId);
            
            // Fetch lead details via REST API
            $.ajax({
                url: jobreadyLeadsAdmin.restUrl + '/' + leadId,
                type: 'GET',
                headers: {
                    'X-WP-Nonce': jobreadyLeadsAdmin.nonce
                },
                success: function(response) {
                    console.log('JobReady: Lead details response:', response);
                    
                    if (response && response.id) {
                        // Single lead response
                        JobReadyLeadsAdmin.renderLeadDetails(response);
                    } else if (response && response.leads && response.leads.length > 0) {
                        // Multiple leads response (fallback)
                        const lead = response.leads[0];
                        JobReadyLeadsAdmin.renderLeadDetails(lead);
                    } else {
                        console.error('JobReady: Invalid response format:', response);
                        $content.html('<div class="jobready-error">Lead not found or invalid response format</div>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('JobReady: Failed to load lead details:', {
                        status: status,
                        error: error,
                        responseText: xhr.responseText,
                        statusCode: xhr.status
                    });
                    
                    let errorMessage = 'Failed to load lead details: ' + error;
                    if (xhr.status === 404) {
                        errorMessage = 'Lead not found';
                    } else if (xhr.status === 403) {
                        errorMessage = 'Access denied. Please check your permissions.';
                    } else if (xhr.status === 500) {
                        errorMessage = 'Server error. Please try again later.';
                    }
                    
                    $content.html('<div class="jobready-error">' + errorMessage + '</div>');
                }
            });
        },

        renderLeadDetails: function(lead) {
            const $content = $('#jobready-lead-modal-content');
            
            // Construct resume URL if not available (for older leads)
            let resumeUrl = lead.resume_url || '';
            if (!resumeUrl && lead.pdf_url && lead.resume_filename) {
                try {
                    // Try to derive resume URL from PDF URL
                    // PDF format: {base}_report.pdf -> Resume format: {base}.{ext}
                    const pdfUrlObj = new URL(lead.pdf_url);
                    const pdfPath = pdfUrlObj.pathname;
                    const baseName = pdfPath
                        .replace(/^\/uploads\//, '')
                        .replace(/_report\.pdf$/i, '');
                    const fileExt = lead.resume_filename.toLowerCase().endsWith('.docx') ? '.docx' : '.pdf';
                    resumeUrl = pdfUrlObj.origin + '/uploads/' + baseName + fileExt;
                } catch (e) {
                    console.error('Error constructing resume URL:', e);
                }
            }
            // Final fallback
            if (!resumeUrl) {
                resumeUrl = lead.pdf_url; // Fallback to PDF URL if we can't construct resume URL
            }
            
            const locationDisplay = this.formatLocation(lead);
            const coordsDisplay = this.formatCoordinates(lead);

            const html = `
                <h2>Lead Details</h2>
                <div class="lead-details">
                    <div class="detail-row">
                        <div class="detail-label">ID:</div>
                        <div class="detail-value">${lead.id}</div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Name:</div>
                        <div class="detail-value">${this.escapeHtml(lead.name)}</div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Email:</div>
                        <div class="detail-value">
                            <a href="mailto:${this.escapeHtml(lead.email)}">${this.escapeHtml(lead.email)}</a>
                        </div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Phone:</div>
                        <div class="detail-value">${lead.phone ? this.escapeHtml(lead.phone) : '<span style="color:#999">N/A</span>'}</div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Location:</div>
                        <div class="detail-value">
                            ${locationDisplay ? this.escapeHtml(locationDisplay) : '<span style="color:#999">Unknown</span>'}
                            ${coordsDisplay ? `<div style="font-size:12px;color:#646970;margin-top:4px;">${this.escapeHtml(coordsDisplay)}</div>` : ''}
                        </div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">ATS Score:</div>
                        <div class="detail-value">
                            <span class="score-badge ats-score">${lead.ats_score}%</span>
                        </div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Job Fit Score:</div>
                        <div class="detail-value">
                            <span class="score-badge fit-score">${lead.job_fit_score}%</span>
                        </div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Resume Filename:</div>
                        <div class="detail-value">${this.escapeHtml(lead.resume_filename)}</div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Resume URL:</div>
                        <div class="detail-value">
                            <a href="${this.escapeHtml(resumeUrl)}" target="_blank">View Resume</a>
                        </div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Report PDF URL:</div>
                        <div class="detail-value">
                            <a href="${this.escapeHtml(lead.pdf_url)}" target="_blank">View Report</a>
                        </div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Consent Given:</div>
                        <div class="detail-value">${lead.consent_given ? 'Yes' : 'No'}</div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">IP Address:</div>
                        <div class="detail-value">${this.escapeHtml(lead.ip_address || 'N/A')}</div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">User Agent:</div>
                        <div class="detail-value">${this.escapeHtml(lead.user_agent || 'N/A')}</div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Created:</div>
                        <div class="detail-value">${this.formatDate(lead.created_at)}</div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Updated:</div>
                        <div class="detail-value">${this.formatDate(lead.updated_at)}</div>
                    </div>
                    <div class="detail-row full-width">
                        <div class="detail-label">Job Description:</div>
                        <div class="detail-value">
                            <div class="job-description-content">
                                ${this.escapeHtml(lead.job_description).replace(/\n/g, '<br>')}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="lead-actions">
                    <button type="button" class="button button-primary" onclick="window.open('${this.escapeHtml(resumeUrl)}', '_blank')">
                        View Resume
                    </button>
                    <button type="button" class="button button-secondary" onclick="window.open('${this.escapeHtml(lead.pdf_url)}', '_blank')">
                        View PDF Report
                    </button>
                    <button type="button" class="button button-secondary" onclick="JobReadyLeadsAdmin.closeModal()">
                        Close
                    </button>
                </div>
            `;
            
            $content.html(html);
        },

        deleteLead: function(leadId) {
            if (!confirm(jobreadyLeadsAdmin.strings.confirmDelete)) {
                return;
            }
            
            const $row = $(`tr[data-lead-id="${leadId}"]`);
            
            // Show loading state
            $row.addClass('deleting');
            
            // Delete via REST API
            $.ajax({
                url: jobreadyLeadsAdmin.restUrl + '/' + leadId,
                type: 'DELETE',
                headers: {
                    'X-WP-Nonce': jobreadyLeadsAdmin.nonce
                },
                success: function(response) {
                    if (response && response.status === 'success') {
                        // Remove row with animation
                        $row.fadeOut(300, function() {
                            $(this).remove();
                            
                            // Check if table is empty
                            const $tbody = $('.jobready-leads-table-container tbody');
                            if ($tbody.find('tr').length === 0) {
                                location.reload(); // Reload to show "no leads" message
                            }
                        });
                        
                        // Show success message
                        JobReadyLeadsAdmin.showNotice(jobreadyLeadsAdmin.strings.deleteSuccess, 'success');
                    } else {
                        JobReadyLeadsAdmin.showNotice(jobreadyLeadsAdmin.strings.deleteError, 'error');
                    }
                },
                error: function(xhr, status, error) {
                    JobReadyLeadsAdmin.showNotice(jobreadyLeadsAdmin.strings.deleteError + ': ' + error, 'error');
                },
                complete: function() {
                    $row.removeClass('deleting');
                }
            });
        },

        closeModal: function() {
            $('#jobready-lead-modal').hide();
        },

        showNotice: function(message, type) {
            // Remove existing notices
            $('.jobready-notice').remove();
            
            const noticeClass = type === 'success' ? 'notice-success' : 'notice-error';
            const notice = $(`
                <div class="jobready-notice notice ${noticeClass} is-dismissible">
                    <p>${this.escapeHtml(message)}</p>
                    <button type="button" class="notice-dismiss">
                        <span class="screen-reader-text">Dismiss this notice.</span>
                    </button>
                </div>
            `);
            
            // Insert notice after the page title
            $('.wrap h1').after(notice);
            
            // Auto-dismiss after 5 seconds
            setTimeout(function() {
                notice.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 5000);
            
            // Handle manual dismiss
            notice.on('click', '.notice-dismiss', function() {
                notice.fadeOut(300, function() {
                    $(this).remove();
                });
            });
        },

        escapeHtml: function(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        },

        formatDate: function(dateString) {
            if (!dateString) return 'N/A';
            const date = new Date(dateString);
            return date.toLocaleString();
        },

        formatLocation: function(lead) {
            const parts = [];
            if (lead.geo_city) parts.push(lead.geo_city);
            if (lead.geo_region) parts.push(lead.geo_region);
            if (lead.geo_country) parts.push(lead.geo_country);
            return parts.join(', ');
        },

        formatCoordinates: function(lead) {
            if (lead.geo_lat == null || lead.geo_lng == null) {
                return '';
            }
            const lat = Number(lead.geo_lat).toFixed(2);
            const lng = Number(lead.geo_lng).toFixed(2);
            return `${lat}, ${lng}${lead.geo_country_code ? ' • ' + lead.geo_country_code.toUpperCase() : ''}`;
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        JobReadyLeadsAdmin.init();
    });

    // Make functions globally accessible
    window.JobReadyLeadsAdmin = JobReadyLeadsAdmin;

})(jQuery);
