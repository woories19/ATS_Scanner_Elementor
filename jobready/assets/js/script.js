(function ($) {
    // Helper: write client-side trace to console and optionally attempt to POST small log to server (disabled by default)
    function jobreadyClientLog() {
        // For now we just use console.log. Future: POST to server log endpoint.
        if (console && console.log) {
            console.log.apply(console, arguments);
        }
    }

    $(document).ready(function () {
        // Delegated handler works if Elementor injects widget dynamically
        $(document).on('submit', '.jobready-form', function (e) {
            e.preventDefault();
            var $form = $(this);
            var $widget = $form.closest('.jobready-widget');
            var results = $widget.find('.jobready-results');
            var apiUrl = $widget.data('api-url') || (typeof jobreadySettings !== 'undefined' ? jobreadySettings.apiUrl : '');

            results.html('<p>Processing... please wait.</p>');

            // Basic validation
            var fileInput = $form.find('input[name="resume"]')[0];
            if (!fileInput || !fileInput.files || fileInput.files.length === 0) {
                results.html('<p style="color:red;">Please attach a resume (PDF or DOCX).</p>');
                jobreadyClientLog('No file selected in widget', $widget.attr('id'));
                return;
            }
            var resumeFile = fileInput.files[0];
            var jobDesc = $form.find('textarea[name="job_description"]').val() || '';
            if (!jobDesc.trim()) {
                results.html('<p style="color:red;">Please enter the job description.</p>');
                return;
            }

            // Build FormData
            var fd = new FormData($form[0]); // automatically includes file & job_description

            // Ensure we have API url
            if (!apiUrl) {
                results.html('<p style="color:red;">API URL not configured. Please set it in JobReady settings or widget.</p>');
                jobreadyClientLog('Missing apiUrl for widget', $widget.attr('id'));
                return;
            }

            // Make AJAX request
            $.ajax({
                url: apiUrl + (apiUrl.endsWith('/analyze') ? '' : '/analyze'),
                type: 'POST',
                data: fd,
                processData: false,
                contentType: false,
                success: function (data) {
                    jobreadyClientLog('AJAX success', data);
                    if (data.error) {
                        results.html('<p style="color:red;">Error: ' + data.error + '</p>');
                        return;
                    }

                    var html = '<h3>Results</h3>';
                    html += '<p><strong>ATS Score:</strong> ' + (data.ats_score || '-') + '</p>';
                    html += '<p><strong>Job Fit Score:</strong> ' + (data.job_fit_score || '-') + '</p>';

                    if (data.feedback && data.feedback.length) {
                        html += '<h4>Feedback</h4><ul>';
                        data.feedback.forEach(function (f) { html += '<li>' + f + '</li>'; });
                        html += '</ul>';
                    }

                    if (data.recommendations && data.recommendations.length) {
                        html += '<h4>AI Recommendations</h4><ul>';
                        data.recommendations.forEach(function (r) { html += '<li>' + r + '</li>'; });
                        html += '</ul>';
                    }

                    results.html(html);
                },
                error: function (xhr, status, err) {
                    jobreadyClientLog('AJAX error', status, err, xhr && xhr.responseText);
                    var serverText = xhr && xhr.responseText ? xhr.responseText : status;
                    results.html('<p style="color:red;">Error fetching recommendations: ' + serverText + '</p>');
                }
            });

        });
    });
})(jQuery);
