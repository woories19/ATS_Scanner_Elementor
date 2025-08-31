/**
 * JobReady Resume Analyzer - Clean, Optimized JavaScript
 * Handles form submission, lead generation, and API communication
 */
(function($) {
    'use strict';

    // Configuration
    const CONFIG = {
        DEBUG: false,
        TIMEOUT: 30000,
        MAX_FILE_SIZE: 5 * 1024 * 1024, // 5MB
        ALLOWED_TYPES: ['application/pdf', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document']
    };

    // Utility functions
    const Utils = {
        debug: (...args) => {
            if (CONFIG.DEBUG && console && console.log) {
                console.log('JobReady:', ...args);
            }
        },

        escapeHtml: (str) => {
            if (!str && str !== 0) return '';
            const div = document.createElement('div');
            div.textContent = String(str);
            return div.innerHTML;
        },

        validateFile: (file) => {
            if (file.size > CONFIG.MAX_FILE_SIZE) {
                return 'File size must be less than 5MB';
            }
            if (!CONFIG.ALLOWED_TYPES.includes(file.type)) {
                return 'Only PDF and DOCX files are allowed';
            }
            return null;
        },

        showError: ($container, message) => {
            $container.find('.jobready-error').remove();
            $container.append(`<div class="jobready-error">${Utils.escapeHtml(message)}</div>`);
        },

        clearErrors: ($container) => {
            $container.find('.jobready-error').remove();
        },

        showLoading: ($container, message = 'Processing... please wait.') => {
            $container.html(`<div class="jobready-loading">${message}</div>`);
        }
    };

    // Main JobReady class
    class JobReady {
        constructor($widget) {
            this.$widget = $widget;
            this.apiUrl = $widget.data('api-url') || '';
            this.feedbackUrl = $widget.data('feedback-url') || '';
            this.leadgenData = null;
            
            this.init();
        }

        init() {
            this.bindEvents();
            Utils.debug('JobReady initialized for widget:', this.$widget[0]);
        }

        bindEvents() {
            // Form submission
            this.$widget.on('submit', '.jobready-form', (e) => {
                e.preventDefault();
                this.handleFormSubmit();
            });

            // Lead generation form submission
            this.$widget.on('submit', '.jobready-leadgen-form', (e) => {
                e.preventDefault();
                this.handleLeadgenSubmit();
            });

            // File input change
            this.$widget.on('change', 'input[type="file"]', (e) => {
                this.handleFileChange(e);
            });
        }

        handleFormSubmit() {
            const $form = this.$widget.find('.jobready-form');
            const $results = this.$widget.find('.jobready-results');
            
            Utils.clearErrors($form);

            // Validate API URL
            if (!this.apiUrl) {
                Utils.showError($form, 'API URL not configured. Please contact support.');
                return;
            }

            // Validate file
            const fileInput = $form.find('input[name="resume"]')[0];
            if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
                Utils.showError($form, 'Please attach a resume (PDF or DOCX).');
                return;
            }

            const file = fileInput.files[0];
            const fileError = Utils.validateFile(file);
            if (fileError) {
                Utils.showError($form, fileError);
                fileInput.value = '';
                return;
            }

            // Validate job description
            const jobDesc = $form.find('textarea[name="job_description"]').val() || '';
            if (!jobDesc.trim()) {
                Utils.showError($form, 'Please enter the job description.');
                return;
            }

            // Store data for lead generation
            this.leadgenData = new FormData();
            this.leadgenData.append('resume', file);
            this.leadgenData.append('job_description', jobDesc);

            // Show lead generation modal
            this.showLeadgenModal($results);
        }

        handleLeadgenSubmit() {
            const $modal = this.$widget.find('.jobready-leadgen-modal');
            const $results = this.$widget.find('.jobready-results');
            
            Utils.clearErrors($modal);

            const name = $modal.find('input[name="name"]').val() || '';
            const email = $modal.find('input[name="email"]').val() || '';

            if (!name.trim() || !email.trim()) {
                Utils.showError($modal, 'Please enter your name and email.');
                return;
            }

            // Add to FormData
            this.leadgenData.append('name', name);
            this.leadgenData.append('email', email);

            // Show pretty loading in modal (instead of abrupt removal)
            this.showLeadgenLoading($modal);

            // Submit to API
            this.submitToAPI($results, $modal);
        }

        handleFileChange(e) {
            const file = e.target.files[0];
            const $field = $(e.target).closest('.jobready-field');
            
            Utils.clearErrors($field);
            
            if (file) {
                const error = Utils.validateFile(file);
                if (error) {
                    e.target.value = '';
                    Utils.showError($field, error);
                }
            }
        }

        showLeadgenModal($results) {
            // Remove any existing modal
            this.$widget.find('.jobready-leadgen-modal').remove();
            
            const modal = `
                <div class="jobready-leadgen-modal">
                    <form class="jobready-leadgen-form">
                        <div class="jobready-leadgen-title">
                            Almost done! Please enter your name and email to receive your full report.
                        </div>
                        <input type="text" name="name" placeholder="Your Name" required />
                        <input type="email" name="email" placeholder="Your Email" required />
                        <div class="jobready-leadgen-error"></div>
                        <button type="submit" class="jobready-leadgen-submit">Get My Report</button>
                    </form>
                </div>
            `;
            
            $results.html(modal);
        }

        showLeadgenLoading($modal) {
            // Add a class for styling transition
            $modal.addClass('is-loading');
            // Smoothly replace inner content with loading UI
            const loadingHtml = `
                <div class="jobready-loading-overlay" role="status" aria-live="polite">
                    <div class="jobready-loading-title">Processing your report…</div>
                    <div class="jobready-progress">
                        <div class="jobready-progress-bar"></div>
                    </div>
                    <div class="jobready-loading-sub">This usually takes ~5–10 seconds.</div>
                </div>
            `;
            $modal.html(loadingHtml);
        }

        async submitToAPI($results, $modal) {
            try {
                const response = await $.ajax({
                    url: this.apiUrl.replace(/\/$/, '') + '/analyze',
                    type: 'POST',
                    data: this.leadgenData,
                    processData: false,
                    contentType: false,
                    timeout: CONFIG.TIMEOUT
                });

                if (!response || typeof response !== 'object') {
                    throw new Error('Invalid server response');
                }

                // Store lead data in WordPress and dispatch email
                let wpSuccess = false;
                let emailSuccess = false;
                
                try {
                    if (window.jobreadyRest && jobreadyRest.restUrl) {
                        const headers = { 'X-WP-Nonce': jobreadyRest.nonce };
                        
                        // Get resume filename from the file input
                        const resumeFile = this.leadgenData.get('resume');
                        const resumeFilename = resumeFile ? resumeFile.name : 'Unknown';
                        
                        // Get job description from the form
                        const jobDescription = this.leadgenData.get('job_description') || '';
                        
                        // Store lead in WordPress
                        const leadResponse = await $.ajax({
                            url: jobreadyRest.restUrl,
                            type: 'POST',
                            headers: headers,
                            contentType: 'application/json; charset=UTF-8',
                            data: JSON.stringify({
                                name: this.leadgenData.get('name') || '',
                                email: this.leadgenData.get('email') || '',
                                ats_score: response.ats_score,
                                job_fit_score: response.job_fit_score,
                                pdf_url: response.pdf_url,
                                resume_filename: resumeFilename,
                                job_description: jobDescription,
                                consent_given: true
                            }),
                            timeout: 8000
                        });
                        
                        wpSuccess = true;
                        Utils.debug('Lead stored successfully in WordPress');
                        
                        // Now dispatch email via WordPress REST endpoint
                        if (jobreadyRest.emailUrl) {
                            try {
                                // Add token header if available
                                if (jobreadyRest.token) {
                                    headers['X-JobReady-Token'] = jobreadyRest.token;
                                }
                                
                                const emailResponse = await $.ajax({
                                    url: jobreadyRest.emailUrl,
                                    type: 'POST',
                                    headers: headers,
                                    contentType: 'application/json; charset=UTF-8',
                                    data: JSON.stringify({
                                        name: this.leadgenData.get('name') || '',
                                        email: this.leadgenData.get('email') || '',
                                        ats_score: response.ats_score,
                                        job_fit_score: response.job_fit_score,
                                        pdf_url: response.pdf_url
                                    }),
                                    timeout: 8000
                                });
                                
                                emailSuccess = true;
                                Utils.debug('Email dispatched successfully via WordPress');
                            } catch (emailError) {
                                Utils.debug('Email dispatch failed:', emailError);
                                // Don't fail the whole process if email fails
                            }
                        } else {
                            Utils.debug('Email URL not available - skipping email dispatch');
                        }
                    }
                } catch (wpError) {
                    Utils.debug('WP lead storage failed (client-side):', wpError);
                    // Continue with redirect even if WordPress integration fails
                }

                // Redirect to thank you page with scores and status
                const thankYouUrl = `/resume-submission/?ats=${encodeURIComponent(response.ats_score)}&fit=${encodeURIComponent(response.job_fit_score)}&wp=${wpSuccess ? '1' : '0'}&email=${emailSuccess ? '1' : '0'}`;
                window.location.href = thankYouUrl;

            } catch (error) {
                Utils.debug('API Error:', error);
                // If modal exists, show error there; otherwise, in results
                const $target = ($modal && $modal.length) ? $modal : $results;
                let errorMessage = 'An error occurred while processing your request.';
                if (error.status === 0) errorMessage = 'Network error. Please try again.';
                else if (error.status === 413) errorMessage = 'File is too large. Please upload a smaller file.';
                else if (error.status === 415) errorMessage = 'Invalid file type. Please upload PDF or DOCX only.';
                else if (error.status === 400 && error.responseJSON && error.responseJSON.error) errorMessage = error.responseJSON.error;
                else if (error.statusText === 'timeout') errorMessage = 'Request timed out. Please try again.';
                $target.removeClass('is-loading');
                $target.html(`<div class="jobready-error">${Utils.escapeHtml(errorMessage)}</div>`);
            }
        }
    }

    // Initialize JobReady when document is ready
    $(document).ready(function() {
        Utils.debug('JobReady script loaded');
        Utils.debug('jQuery version:', $.fn.jquery);

        // Initialize JobReady for each widget on the page
        $('.jobready-widget').each(function() {
            new JobReady($(this));
        });
    });

})(jQuery);
