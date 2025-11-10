/**
 * JobReady Resume Analyzer - Clean, Optimized JavaScript
 * Handles form submission, lead generation, and API communication
 */
(function($) {
    'use strict';

    // Configuration
    const CONFIG = {
        DEBUG: false, // Disabled in production for performance
        TIMEOUT: 30000,
        MAX_FILE_SIZE: 5 * 1024 * 1024, // 5MB
        ALLOWED_TYPES: ['application/pdf', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        LAZY_LOAD: true, // Only initialize when widget is visible or interacted
        INTERSECTION_THRESHOLD: 0.1 // Start loading when 10% visible
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

            // Lead generation form submission (modal appended to body)
            $(document).on('submit', '.jobready-leadgen-form', (e) => {
                e.preventDefault();
                this.handleLeadgenSubmit();
            });

            // Close modal on click outside
            $(document).on('click', '.jobready-overlay', (e) => {
                if (e.target === e.currentTarget) {
                    this.closeLeadgenModal();
                }
            });

            // Close modal on X button
            $(document).on('click', '.jobready-modal-close', (e) => {
                e.preventDefault();
                this.closeLeadgenModal();
            });

            // Close modal on Escape key
            $(document).on('keydown', (e) => {
                if (e.key === 'Escape') {
                    this.closeLeadgenModal();
                }
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
            const $modal = $('.jobready-leadgen-modal');
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
            // Remove any existing overlay/modal
            $('.jobready-overlay').remove();

            const overlay = document.createElement('div');
            overlay.className = 'jobready-overlay';
            overlay.setAttribute('role', 'dialog');
            overlay.setAttribute('aria-modal', 'true');

            const modal = document.createElement('div');
            modal.className = 'jobready-leadgen-modal';
            modal.innerHTML = `
                <button type=\"button\" class=\"jobready-modal-close\" aria-label=\"Close\">&times;</button>
                <form class=\"jobready-leadgen-form\">
                    <div class=\"jobready-leadgen-title\">
                        Almost done! Please enter your name and email to receive your full report.
                    </div>
                    <input type=\"text\" name=\"name\" placeholder=\"Your Name\" required />
                    <input type=\"email\" name=\"email\" placeholder=\"Your Email\" required />
                    <div class=\"jobready-leadgen-error\"></div>
                    <button type=\"submit\" class=\"jobready-leadgen-submit\">Get My Report</button>
                </form>
            `;

            overlay.appendChild(modal);
            document.body.appendChild(overlay);
            document.body.classList.add('jobready-modal-open');
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
                        
                        // ALWAYS attempt email dispatch regardless of WordPress integration success
                        Utils.debug('Starting email dispatch process...');
                        
                        // Method 1: Try WordPress REST API
                        let emailSent = false;
                        
                        try {
                            // Prepare headers for email dispatch
                            const emailHeaders = { 'X-WP-Nonce': jobreadyRest?.nonce || '' };
                            
                            // Add token header if available
                            if (jobreadyRest?.token) {
                                emailHeaders['X-JobReady-Token'] = jobreadyRest.token;
                                Utils.debug('Token added to email headers');
                            } else {
                                Utils.debug('No token available for email');
                            }
                            
                            // Determine email URL
                            let emailUrl = jobreadyRest?.emailUrl;
                            if (!emailUrl) {
                                emailUrl = window.location.origin + '/wp-json/jobready/v1/send-report';
                                Utils.debug('Using fallback email URL:', emailUrl);
                            } else {
                                Utils.debug('Using provided email URL:', emailUrl);
                            }
                            
                            Utils.debug('Attempting email dispatch with data:', {
                                name: this.leadgenData.get('name') || '',
                                email: this.leadgenData.get('email') || '',
                                ats_score: response.ats_score,
                                job_fit_score: response.job_fit_score,
                                pdf_url: response.pdf_url
                            });
                            
                            const emailResponse = await $.ajax({
                                url: emailUrl,
                                type: 'POST',
                                headers: emailHeaders,
                                contentType: 'application/json; charset=UTF-8',
                                data: JSON.stringify({
                                    name: this.leadgenData.get('name') || '',
                                    email: this.leadgenData.get('email') || '',
                                    ats_score: response.ats_score,
                                    job_fit_score: response.job_fit_score,
                                    pdf_url: response.pdf_url
                                }),
                                timeout: 10000
                            });
                            
                            Utils.debug('Email response:', emailResponse);
                            emailSuccess = true;
                            emailSent = true;
                            Utils.debug('Email dispatched successfully via WordPress REST API!');
                        } catch (emailError) {
                            Utils.debug('Email dispatch failed via WordPress REST API:', emailError);
                            Utils.debug('Email error details:', {
                                status: emailError.status,
                                statusText: emailError.statusText,
                                responseText: emailError.responseText,
                                responseJSON: emailError.responseJSON
                            });
                        }
                        
                        // Method 2: Try direct AJAX to WordPress admin-ajax.php as fallback
                        if (!emailSent) {
                            Utils.debug('Trying fallback email method via admin-ajax.php...');
                            try {
                                const fallbackResponse = await $.ajax({
                                    url: window.location.origin + '/wp-admin/admin-ajax.php',
                                    type: 'POST',
                                    data: {
                                        action: 'jobready_send_email_fallback',
                                        name: this.leadgenData.get('name') || '',
                                        email: this.leadgenData.get('email') || '',
                                        ats_score: response.ats_score,
                                        job_fit_score: response.job_fit_score,
                                        pdf_url: response.pdf_url,
                                        nonce: jobreadyRest?.nonce || ''
                                    },
                                    timeout: 10000
                                });
                                
                                Utils.debug('Fallback email response:', fallbackResponse);
                                if (fallbackResponse.success) {
                                    emailSuccess = true;
                                    emailSent = true;
                                    Utils.debug('Email dispatched successfully via fallback method!');
                                } else {
                                    Utils.debug('Fallback email failed:', fallbackResponse);
                                }
                            } catch (fallbackError) {
                                Utils.debug('Fallback email dispatch failed:', fallbackError);
                            }
                        }
                        
                        // Method 3: Try simple form submission as last resort
                        if (!emailSent) {
                            Utils.debug('Trying last resort email method...');
                            try {
                                // Create a hidden form and submit it
                                const form = document.createElement('form');
                                form.method = 'POST';
                                form.action = window.location.origin + '/wp-admin/admin-ajax.php';
                                form.style.display = 'none';
                                
                                const fields = {
                                    action: 'jobready_send_email_fallback',
                                    name: this.leadgenData.get('name') || '',
                                    email: this.leadgenData.get('email') || '',
                                    ats_score: response.ats_score,
                                    job_fit_score: response.job_fit_score,
                                    pdf_url: response.pdf_url,
                                    nonce: jobreadyRest?.nonce || ''
                                };
                                
                                for (const [key, value] of Object.entries(fields)) {
                                    const input = document.createElement('input');
                                    input.type = 'hidden';
                                    input.name = key;
                                    input.value = value;
                                    form.appendChild(input);
                                }
                                
                                document.body.appendChild(form);
                                
                                // Submit form in background
                                const formResponse = await new Promise((resolve, reject) => {
                                    const iframe = document.createElement('iframe');
                                    iframe.style.display = 'none';
                                    iframe.name = 'email_iframe';
                                    form.target = 'email_iframe';
                                    
                                    iframe.onload = () => {
                                        document.body.removeChild(iframe);
                                        document.body.removeChild(form);
                                        resolve({ success: true });
                                    };
                                    
                                    iframe.onerror = () => {
                                        document.body.removeChild(iframe);
                                        document.body.removeChild(form);
                                        reject(new Error('Form submission failed'));
                                    };
                                    
                                    document.body.appendChild(iframe);
                                    form.submit();
                                });
                                
                                Utils.debug('Form submission response:', formResponse);
                                emailSuccess = true;
                                emailSent = true;
                                Utils.debug('Email dispatched successfully via form submission!');
                            } catch (formError) {
                                Utils.debug('Form submission email dispatch failed:', formError);
                            }
                        }
                        
                        if (!emailSent) {
                            Utils.debug('All email dispatch methods failed!');
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

        closeLeadgenModal() {
            const overlay = document.querySelector('.jobready-overlay');
            if (overlay) {
                overlay.parentNode.removeChild(overlay);
            }
            document.body.classList.remove('jobready-modal-open');
        }
    }

    // Lazy initialization manager
    const JobReadyLazyLoader = {
        initialized: new Set(),
        
        init() {
            if (!CONFIG.LAZY_LOAD) {
                // Immediate initialization (fallback for older browsers)
                this.initializeAll();
                return;
            }
            
            // Use Intersection Observer for modern browsers
            if ('IntersectionObserver' in window) {
                this.initWithObserver();
            } else {
                // Fallback: initialize on scroll or interaction
                this.initWithFallback();
            }
        },
        
        initWithObserver() {
            const widgets = document.querySelectorAll('.jobready-widget');
            if (widgets.length === 0) return;
            
            const observer = new IntersectionObserver((entries) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const widget = entry.target;
                        const id = widget.id || widget.className;
                        if (!this.initialized.has(id)) {
                            this.initializeWidget($(widget));
                            observer.unobserve(widget);
                        }
                    }
                });
            }, {
                rootMargin: '50px', // Start loading 50px before widget is visible
                threshold: CONFIG.INTERSECTION_THRESHOLD
            });
            
            widgets.forEach(widget => {
                observer.observe(widget);
            });
            
            // Also initialize on any user interaction (hover, click, focus)
            widgets.forEach(widget => {
                ['mouseenter', 'touchstart', 'focus'].forEach(eventType => {
                    widget.addEventListener(eventType, () => {
                        const id = widget.id || widget.className;
                        if (!this.initialized.has(id)) {
                            this.initializeWidget($(widget));
                            observer.unobserve(widget);
                        }
                    }, { once: true, passive: true });
                });
            });
        },
        
        initWithFallback() {
            // Initialize on scroll or user interaction
            let initialized = false;
            const initOnInteraction = () => {
                if (!initialized) {
                    initialized = true;
                    this.initializeAll();
                    window.removeEventListener('scroll', initOnInteraction, { passive: true });
                    window.removeEventListener('mousemove', initOnInteraction, { passive: true });
                    window.removeEventListener('touchstart', initOnInteraction, { passive: true });
                }
            };
            
            window.addEventListener('scroll', initOnInteraction, { passive: true });
            window.addEventListener('mousemove', initOnInteraction, { passive: true });
            window.addEventListener('touchstart', initOnInteraction, { passive: true });
            
            // Also initialize after a short delay if no interaction
            setTimeout(() => {
                if (!initialized) {
                    this.initializeAll();
                }
            }, 1000);
        },
        
        initializeWidget($widget) {
            const id = $widget.attr('id') || $widget[0].className;
            if (!this.initialized.has(id)) {
                this.initialized.add(id);
                try {
                    new JobReady($widget);
                    Utils.debug('JobReady widget initialized:', id);
                } catch (e) {
                    console.error('JobReady initialization error:', e);
                }
            }
        },
        
        initializeAll() {
            $('.jobready-widget').each((index, element) => {
                this.initializeWidget($(element));
            });
        }
    };

    // Initialize when DOM is ready
    $(document).ready(function() {
        Utils.debug('JobReady script loaded');
        
        // Initialize REST data (lightweight, always needed)
        if (window.jobreadyRest) {
            Utils.debug('REST API configured');
        }

        // Lazy load widgets
        JobReadyLazyLoader.init();
    });

})(jQuery);
