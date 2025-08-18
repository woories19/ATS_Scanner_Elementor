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
        // State for leadgen
        let leadgenData = null;

        // Step 1: Resume/Jobdesc submit
        $(document).on('submit', '.jobready-form', function(e){
            e.preventDefault();
            var $form = $(this);
            var $widget = $form.closest('.jobready-widget');
            var results = $widget.find('.jobready-results');
            var apiUrl = $widget.data('api-url') || (typeof jobreadySettings !== 'undefined' ? jobreadySettings.apiUrl : '');
            apiUrl = apiUrl ? apiUrl.replace(/\/$/, '') : '';
            if (!apiUrl) {
                results.html('<p style="color:red;">API URL not configured. Set it in JobReady settings or widget.</p>');
                return;
            }
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
            // Save for next step
            leadgenData = new FormData();
            leadgenData.append('resume', fileInput.files[0]);
            leadgenData.append('job_description', jobDesc);
            // Show name/email modal
            showLeadgenModal($widget, results);
        });

        // Step 2: Name/Email submit
        $(document).on('submit', '.jobready-leadgen-form', function(e){
            e.preventDefault();
            var $modal = $(this).closest('.jobready-leadgen-modal');
            var $widget = $modal.closest('.jobready-widget');
            var results = $widget.find('.jobready-results');
            var name = $modal.find('input[name="name"]').val() || '';
            var email = $modal.find('input[name="email"]').val() || '';
            if (!name.trim() || !email.trim()) {
                $modal.find('.jobready-leadgen-error').text('Please enter your name and email.');
                return;
            }
            $modal.find('.jobready-leadgen-error').text('');
            // Add to FormData
            leadgenData.append('name', name);
            leadgenData.append('email', email);
            // Hide modal, show loading
            $modal.remove();
            results.html('<p>Processing... please wait.</p>');
            var apiUrl = $widget.data('api-url') || (typeof jobreadySettings !== 'undefined' ? jobreadySettings.apiUrl : '');
            apiUrl = apiUrl ? apiUrl.replace(/\/$/, '') : '';
            $.ajax({
                url: apiUrl + '/analyze',
                type: 'POST',
                data: leadgenData,
                processData: false,
                contentType: false,
                timeout: 30000,
                success: function(data){
                    if (!data || typeof data !== 'object') {
                        results.html('<div class="jobready-error">Invalid server response</div>');
                        return;
                    }
                    // Redirect to thank you page with scores
                    var thankYouUrl = '/resume-submission/?ats=' + encodeURIComponent(data.ats_score) + '&fit=' + encodeURIComponent(data.job_fit_score);
                    window.location.href = thankYouUrl;
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
                }
            });
        });

        function showLeadgenModal($widget, $results) {
            // Remove any existing modal
            $widget.find('.jobready-leadgen-modal').remove();
            var modal = `
                <div class="jobready-leadgen-modal">
                  <form class="jobready-leadgen-form">
                    <div class="jobready-leadgen-title">Almost done! Please enter your name and email to receive your full report.</div>
                    <input type="text" name="name" placeholder="Your Name" required />
                    <input type="email" name="email" placeholder="Your Email" required />
                    <div class="jobready-leadgen-error"></div>
                    <button type="submit" class="jobready-leadgen-submit">Get My Report</button>
                  </form>
                </div>
            `;
            $results.html(modal);
        }

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
            var $circle = $widget.find(`path.circle[data-key="${key}"]`);
            if (!$circle.length) return;
            var radius = 15.9155;
            var circumference = 2 * Math.PI * radius;
            $circle.attr('stroke-dasharray', circumference);
            $circle.attr('stroke-dashoffset', circumference);
            setTimeout(function() {
                $circle.css('transition', 'stroke-dashoffset 1.2s ease');
                var offset = circumference - (value / 100) * circumference;
                $circle.attr('stroke-dashoffset', offset);
            }, 100);
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
