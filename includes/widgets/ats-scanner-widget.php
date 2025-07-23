<?php
class ATS_Scanner_Widget extends \Elementor\Widget_Base {
    public function get_name() {
        return 'ats_scanner';
    }

    public function get_title() {
        return 'ATS Resume Scanner';
    }

    public function get_icon() {
        return 'eicon-document-file';
    }

    public function get_categories() {
        return ['general'];
    }

    protected function register_controls() {
        $this->start_controls_section(
            'content_section',
            [
                'label' => 'Settings',
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        $this->add_control(
            'api_url',
            [
                'label' => 'API URL',
                'type' => \Elementor\Controls_Manager::TEXT,
                'default' => 'https://psuedouser1.pythonanywhere.com/api/analyze',
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        ?>
        <div class="ats-scanner-widget">
            <form id="atsForm" class="ats-form">
                <div class="form-group">
                    <label for="resume">Upload Resume</label>
                    <div class="file-input-wrapper">
                        <input type="file" name="resume" id="resume" accept=".pdf,.docx" required>
                        <div class="file-input-content">
                            <i class="fas fa-upload"></i>
                            <div>Drag & drop your resume or click to browse</div>
                            <div class="file-types">Supported formats: PDF, DOCX</div>
                        </div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="job_description">Job Description</label>
                    <textarea 
                        name="job_description" 
                        id="job_description"
                        placeholder="Paste the job description here..."
                        rows="8"
                    ></textarea>
                </div>

                <button type="submit" class="submit-button">
                    <span class="button-text">Analyze Resume</span>
                    <span class="spinner"></span>
                </button>
            </form>

            <div id="atsResults" class="results-container"></div>
        </div>

        <style>
            .ats-scanner-widget {
                max-width: 800px;
                margin: 0 auto;
                padding: 20px;
            }

            .ats-form .form-group {
                margin-bottom: 20px;
            }

            .file-input-wrapper {
                border: 2px dashed #5944F9;
                padding: 20px;
                text-align: center;
                border-radius: 8px;
                background: rgba(89, 68, 249, 0.05);
                cursor: pointer;
                transition: all 0.3s ease;
            }

            .file-input-wrapper:hover {
                background: rgba(89, 68, 249, 0.1);
            }

            textarea {
                width: 100%;
                padding: 12px;
                border: 2px solid #5944F9;
                border-radius: 8px;
                min-height: 150px;
            }

            .submit-button {
                background: #5944F9;
                color: white;
                padding: 12px 24px;
                border: none;
                border-radius: 8px;
                cursor: pointer;
                width: 100%;
                position: relative;
            }

            .submit-button:hover {
                background: #4935E8;
            }

            .spinner {
                display: none;
                width: 20px;
                height: 20px;
                border: 2px solid #ffffff;
                border-top: 2px solid transparent;
                border-radius: 50%;
                position: absolute;
                right: 10px;
                top: 50%;
                transform: translateY(-50%);
            }

            .loading .spinner {
                display: block;
                animation: spin 1s linear infinite;
            }

            @keyframes spin {
                0% { transform: translateY(-50%) rotate(0deg); }
                100% { transform: translateY(-50%) rotate(360deg); }
            }

            .results-container {
                margin-top: 30px;
            }
			.ats-results-card,
		.ats-results-card * {
			color: #333 !important;
		}

		.ats-results-card {
			background: #fff;
			padding: 20px;
			border-radius: 12px;
			box-shadow: 0 0 12px rgba(0, 0, 0, 0.05);
			margin-top: 20px;
			font-family: 'Inter', sans-serif;
		}

		.ats-results-card h3,
		.ats-results-card h4 {
			color: #5944F9 !important;
			margin-bottom: 10px;
		}

		.ats-results-card p,
		.ats-results-card li {
			color: #333 !important;
			font-size: 15px;
			line-height: 1.6;
		}

		.ats-results-card ul {
			padding-left: 20px;
			list-style-type: disc;
			margin-bottom: 20px;
		}

		.keyword-columns {
			display: flex;
			gap: 30px;
			flex-wrap: wrap;
		}

		.keyword-columns div {
			flex: 1;
			min-width: 200px;
		}
		.score-section {
			display: flex;
			align-items: center;
			gap: 20px;
			margin-bottom: 20px;
		}

		.circle-progress {
			width: 80px;
			height: 80px;
			position: relative;
		}

		.circle-progress svg {
			width: 100%;
			height: 100%;
			transform: rotate(-90deg);
		}

		.circle-progress .bg {
			fill: none;
			stroke: #eee;
			stroke-width: 3.8;
		}

		.circle-progress .progress {
			fill: none;
			stroke: #5944F9;
			stroke-width: 3.8;
			stroke-linecap: round;
			transition: stroke-dasharray 1s ease-out;
		}

		.circle-progress .percentage {
			font-size: 12px;
			fill: #333;
			text-anchor: middle;
			dominant-baseline: middle;
			transform: rotate(90deg);
			font-family: Arial, sans-serif;
		}
        </style>

        <script>
        jQuery(document).ready(function($) {
            const form = $('#atsForm');
            const results = $('#atsResults');
            const submitButton = form.find('.submit-button');

            form.on('submit', function(e) {
                e.preventDefault();
                
                submitButton.addClass('loading');
                const formData = new FormData(this);

                $.ajax({
                    url: '<?php echo $settings['api_url']; ?>',
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
						  </div>`;
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
						// Animate the circular progress bar
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

            // Show filename when selected
            $('#resume').on('change', function(e) {
                const fileName = e.target.files[0]?.name;
                if (fileName) {
                    $(this).closest('.file-input-wrapper')
                        .find('.file-input-content')
                        .html(`<i class="fas fa-file"></i><div>${fileName}</div>`);
                }
            });
        });
        </script>
        <?php
    }
}