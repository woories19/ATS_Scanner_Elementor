<?php
add_action('wp_enqueue_scripts', function() {
    wp_enqueue_style('ats-scanner-style', plugin_dir_url(__FILE__) . 'assets/style.css');
    wp_enqueue_script('ats-scanner-script', plugin_dir_url(__FILE__) . 'assets/script.js', ['jquery'], null, true);
});
class ATS_Scanner_Widget extends \Elementor\Widget_Base {
    public function get_name() {
        return 'job_ready_elementor';
    }

    public function get_title() {
        return 'JobReady By Mazin Digital';
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
            <form id="atsForm" class="ats-form" data-api-url="<?php echo esc_url($settings['api_url']); ?>">
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
        <?php
    }
}