(function($){
    const DEBUG = true;
    function debug(...args) {
        if (DEBUG && console && console.log) {
            console.log('JobReady:', ...args);
        }
    }
    
    debug('Script loaded');
    debug('jQuery version:', $.fn.jquery);

    $(document).ready(function(){

        // Delegated submit handler (works in editor + frontend)
        $(document).on('submit', '.jobready-form', function(e){
            e.preventDefault();

            var $form = $(this);
            var $widget = $form.closest('.jobready-widget');
            var results = $widget.find('.jobready-results');

            // Resolve API URL: widget data-api-url or global localized script var
            var apiUrl = $widget.data('api-url') || (typeof jobreadySettings !== 'undefined' ? jobreadySettings.apiUrl : '');
            apiUrl = apiUrl ? apiUrl.replace(/\/$/, '') : '';

            // If empty, tell user
            if (!apiUrl) {
                results.html('<p style="color:red;">API URL not configured. Set it in JobReady settings or widget.</p>');
                console.error('JobReady: Missing API URL for widget', $widget.attr('id'));
                return;
            }

            // Basic validation and formdata
            var fileInput = $form.find('input[name="resume"]')[0];
            if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
                results.html('<p style="color:red;">Please attach a resume (PDF or DOCX).</p>');
                return;
            }
            var jobDesc = $form.find('textarea[name="job_description"]').val() || '';
            if (!jobDesc.trim()) {
                results.html('<p style="color:red;">Please enter the job description.</p>');
                return;
            }

            var fd = new FormData($form[0]); // automatically pulls file & job_description

            // show spinner / loading
            results.html('<p>Processing... please wait.</p>');

            $.ajax({
                url: apiUrl + '/analyze',
                type: 'POST',
                data: fd,
                processData: false,
                contentType: false,
                timeout: 30000, // 30 second timeout
                beforeSend: function() {
                    results.html(`
                        <div class="jobready-loading">
                            <div class="jobready-spinner"></div>
                            <p>Analyzing your resume...</p>
                        </div>
                    `);
                },
                success: function(data){
                    // Validate response
                    if (!data || typeof data !== 'object') {
                        results.html('<div class="jobready-error">Invalid server response</div>');
                        return;
                    }

                    // Ensure numbers and clamp for animation
                    var ats = Math.max(0, Math.min(100, Math.round(Number(data.ats_score || 0))));
                    var fit = Math.max(0, Math.min(100, Math.round(Number(data.job_fit_score || 0))));
                    debug('Creating circles with scores:', {ats: ats, fit: fit});

                    // Recommendations
                    var basicRecs = Array.isArray(data.basic_recommendations) ? data.basic_recommendations : [];
                    var aiRecs = Array.isArray(data.ai_recommendations) ? data.ai_recommendations : [];

                    // Keyword and other scores
                    var keywordMatch = '';
                    if (typeof data.keywords_match_percent !== 'undefined') {
                        keywordMatch += `<h4>Keyword Match</h4><p>Match Rate: ${data.keywords_match_percent}%</p>`;
                    }
                    if (typeof data.section_completeness_percent !== 'undefined') {
                        keywordMatch += `<h4>Section Completeness</h4><p>${data.section_completeness_percent}%</p>`;
                    }
                    if (typeof data.readability_percent !== 'undefined') {
                        keywordMatch += `<h4>Readability</h4><p>${data.readability_percent}%</p>`;
                    }

                    // Build HTML
                    var html = '';
                    html += '<div class="jobready-scores">';
                    html += makeCircleHtml('ATS Score', ats, 'ats');
                    html += makeCircleHtml('Job Fit', fit, 'fit');
                    html += '</div>';

                    html += '<h4>Basic Recommendations</h4>';
                    if (basicRecs.length) {
                        html += '<ul class="jobready-basic">';
                        basicRecs.forEach(function(b){ html += '<li>' + sanitizeHTML(b) + '</li>'; });
                        html += '</ul>';
                    } else {
                        html += '<p>No basic recommendations available.</p>';
                    }

                    html += '<h4>AI Recommendations</h4>';
                    if (aiRecs.length) {
                        html += '<ul class="jobready-ai">';
                        aiRecs.forEach(function(rec, index) {
                            html += '<li class="' + (index > 0 ? 'ai-blur' : 'ai-visible') + '">' + escapeHtml(rec) + '</li>';
                        });
                        html += '</ul>';
                    } else {
                        html += '<p>No AI recommendations available.</p>';
                    }

                    if (keywordMatch) {
                        html += `<div class="jobready-keywords">${keywordMatch}</div>`;
                    }

                    var feedbackUrl = $widget.data('feedback-url') || '';
                    if (feedbackUrl) {
                        html += '<div class="jobready-cta-wrap"><a class="jobready-cta" href="' + escapeAttr(feedbackUrl) + '" target="_blank" rel="noopener">Get Professional Feedback</a></div>';
                    }

                    results.html(html);
                    animateCircle($widget, 'ats', ats);
                    animateCircle($widget, 'fit', fit);
                    resetForm($form);
                },
                error: function(xhr, status, err) {
                    let errorMessage = 'An error occurred while processing your request.';
                    
                    if (status === 'timeout') {
                        errorMessage = 'Request timed out. Please try again.';
                    } else if (xhr.status === 413) {
                        errorMessage = 'File is too large. Please upload a smaller file.';
                    } else if (xhr.status === 415) {
                        errorMessage = 'Invalid file type. Please upload PDF or DOCX only.';
                    }
                    
                    results.html(`<div class="jobready-error">${errorMessage}</div>`);
                    console.error('JobReady API Error:', {status, error: err, response: xhr.responseText});
                }
            });

        });

        // ---------- helpers ----------
        function makeCircleHtml(label, value, key) {
            return `
                <div class="jr-circle-wrap jr-${key}">
                    <svg viewBox="0 0 36 36" class="circular-chart">
                        <path class="circle-bg" 
                            d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
                        <path class="circle" data-key="${key}"
                            d="M18 2.0845 a 15.9155 15.9155 0 0 1 0 31.831 a 15.9155 15.9155 0 0 1 0 -31.831"/>
                        <text x="18" y="20.35" class="percentage">${value}%</text>
                    </svg>
                    <div class="jr-label">${escapeHtml(label)}</div>
                </div>
            `;
        }

        function animateCircle($widget, key, value) {
            console.log(`Attempting to animate circle: ${key} with value: ${value}`);
            var $circle = $widget.find(`path.circle[data-key="${key}"]`);
            if (!$circle.length) {
                console.error(`Circle path not found for key: ${key}`);
                return;
            }
            $circle.attr('stroke-dasharray', `${value},100`);
        }

        // basic helpers to prevent XSS in inserted HTML
        function escapeHtml(str) {
            if (!str && str !== 0) return '';
            return String(str).replace(/[&<>"'`=\/]/g, function(s) {
                return ({
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#39;',
                    '/': '&#x2F;',
                    '`': '&#x60;',
                    '=': '&#x3D;'
                })[s];
            });
        }
        function escapeAttr(s) { return escapeHtml(s); }

    });

    // Add after document.ready
    function validateFileUpload(file) {
        const maxSize = 5 * 1024 * 1024; // 5MB
        const allowedTypes = ['application/pdf', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
        
        if (file.size > maxSize) {
            return 'File size must be less than 5MB';
        }
        if (!allowedTypes.includes(file.type)) {
            return 'Only PDF and DOCX files are allowed';
        }
        return null;
    }

    // Add file input change handler
    $(document).on('change', '.jobready-form input[type="file"]', function(e) {
        const file = e.target.files[0];
        const error = validateFileUpload(file);
        const $field = $(this).closest('.jobready-field');
        
        if (error) {
            e.target.value = '';
            $field.append(`<div class="jobready-error">${error}</div>`);
        } else {
            $field.find('.jobready-error').remove();
        }
    });

    // Add this helper function
    function sanitizeHTML(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function resetForm($form) {
        $form[0].reset();
        $form.find('.jobready-error').remove();
    }

})(jQuery);
