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
            
            // Fetch lead details via REST API
            $.ajax({
                url: jobreadyLeadsAdmin.restUrl + '/' + leadId,
                type: 'GET',
                headers: {
                    'X-WP-Nonce': jobreadyLeadsAdmin.nonce
                },
                success: function(response) {
                    if (response && response.leads && response.leads.length > 0) {
                        const lead = response.leads[0];
                        JobReadyLeadsAdmin.renderLeadDetails(lead);
                    } else {
                        $content.html('<div class="jobready-error">Lead not found</div>');
                    }
                },
                error: function(xhr, status, error) {
                    $content.html('<div class="jobready-error">Failed to load lead details: ' + error + '</div>');
                }
            });
        },

        renderLeadDetails: function(lead) {
            const $content = $('#jobready-lead-modal-content');
            
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
                        <div class="detail-label">PDF URL:</div>
                        <div class="detail-value">
                            <a href="${this.escapeHtml(lead.pdf_url)}" target="_blank">View PDF</a>
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
                    <button type="button" class="button button-primary" onclick="window.open('${this.escapeHtml(lead.pdf_url)}', '_blank')">
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
        }
    };

    // Initialize when document is ready
    $(document).ready(function() {
        JobReadyLeadsAdmin.init();
    });

    // Make functions globally accessible
    window.JobReadyLeadsAdmin = JobReadyLeadsAdmin;

})(jQuery);
