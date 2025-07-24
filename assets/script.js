jQuery(document).ready(function($) {
    const form = $('#atsForm');
    const results = $('#atsResults');
    const submitButton = form.find('.submit-button');

    const apiURL = form.data('api-url');  // ✅ Get API URL from form's data attribute

    form.on('submit', function(e) {
        e.preventDefault();
        
        submitButton.addClass('loading');
        const formData = new FormData(this);

        $.ajax({
            url: apiURL + '/result',  // ✅ Proper API endpoint
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                const score = `
                    <div class="score-section">
                        <div class="circle-progress" data-score="${response.ats_score}">
                            <svg viewBox="0 0 36 36">
                                <path class="bg" d="M18 2.0845
                                    a 15.9155 15.9155 0 0 1 0 31.831
                                    a 15.9155 15.9155 0 0 1 0 -31.831"/>
                                <path class="progress" stroke-dasharray="0, 100" d="M18 2.0845
                                    a 15.9155 15.9155 0 0 1 0 31.831
                                    a 15.9155 15.9155 0 0 1 0 -31.831"/>
                                <text x="18" y="20.35" class="percentage">${response.ats_score}%</text>
                            </svg>
                        </div>
                        <div class="score-label">ATS Score</div>
                    </div>
                `;

                const feedbackList = response.feedback?.length
                    ? response.feedback.map(item => `<li>${item}</li>`).join('')
                    : '<li>No feedback found.</li>';

                const keywordsList = response.keywords?.length
                    ? response.keywords.map(item => `<li>${item}</li>`).join('')
                    : '<li>No keywords found.</li>';

                let keywordMatchHTML = '';
                if (response.keyword_analysis) {
                    const match = response.keyword_analysis.match_percentage;
                    const matched = response.keyword_analysis.matching_keywords.map(k => `<li>${k}</li>`).join('');
                    const missing = response.keyword_analysis.missing_keywords.map(k => `<li>${k}</li>`).join('');

                    keywordMatchHTML = `
                        <p><strong>Keyword Match:</strong> ${match}%</p>
                        <p><strong>Matching Keywords:</strong></p><ul>${matched}</ul>
                        <p><strong>Missing Keywords:</strong></p><ul>${missing}</ul>
                    `;
                }

                results.html(`
                    <div class="ats-results-card">
                        ${score}
                        <h4>Feedback:</h4><ul>${feedbackList}</ul>
                        <h4>Extracted Keywords:</h4><ul>${keywordsList}</ul>
                        ${keywordMatchHTML}
                    </div>
                `);

                // ✅ Animate circular progress
                const circle = document.querySelector('.circle-progress .progress');
                const scoreValue = document.querySelector('.circle-progress').dataset.score;
                const dash = (scoreValue / 100) * 100;
                circle.setAttribute('stroke-dasharray', `${dash}, 100`);
            },

            error: function(xhr, status, error) {
                console.error('AJAX Error:', status, error);
                console.error('Response:', xhr.responseText);
                results.html('<div class="error">Error processing your request. Please try again.</div>');
            },

            complete: function() {
                submitButton.removeClass('loading');
            }
        });
    });

    // 📁 Show file name
    $('#resume').on('change', function(e) {
        const fileName = e.target.files[0]?.name;
        if (fileName) {
            $(this).closest('.file-input-wrapper')
                .find('.file-input-content')
                .html(`<i class="fas fa-file"></i><div>${fileName}</div>`);
        }
    });
});
