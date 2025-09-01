<?php
/**
 * JobReady Resume Analyzer - Elementor Widget
 * Clean, organized, and optimized for better maintainability
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class JobReady_Widget extends \Elementor\Widget_Base {

    /**
     * Get widget name
     */
    public function get_name() {
        return 'jobready_widget';
    }

    /**
     * Get widget title
     */
    public function get_title() {
        return 'JobReady Resume Analyzer';
    }

    /**
     * Get widget icon
     */
    public function get_icon() {
        return 'eicon-document-file';
    }

    /**
     * Get widget categories
     */
    public function get_categories() {
        return ['general'];
    }

    /**
     * Register widget controls
     */
    protected function register_controls() {
        $this->start_controls_section(
            'section_content',
            [
                'label' => __('Settings', 'jobready'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        // Custom API URL toggle
        $this->add_control(
            'use_custom_api',
            [
                'label' => __('Use Custom API URL', 'jobready'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'no',
                'description' => __('Enable to override the global API URL for this widget', 'jobready'),
            ]
        );

        // Custom API URL field
        $this->add_control(
            'custom_api_url',
            [
                'label' => __('Custom API URL', 'jobready'),
                'type' => \Elementor\Controls_Manager::URL,
                'placeholder' => __('https://your-api.example.com', 'jobready'),
                'condition' => ['use_custom_api' => 'yes'],
                'description' => __('Enter the full API URL (without trailing slash)', 'jobready'),
            ]
        );

        // Professional feedback URL
        $this->add_control(
            'professional_feedback_url',
            [
                'label' => __('Professional Feedback URL', 'jobready'),
                'type' => \Elementor\Controls_Manager::URL,
                'placeholder' => __('https://yourdomain.com/professional-feedback', 'jobready'),
                'description' => __('Optional: Link to professional resume review services', 'jobready'),
                'default' => [
                    'url' => '',
                    'is_external' => true,
                    'nofollow' => true,
                ],
            ]
        );

        $this->end_controls_section();
    }

    /**
     * Render widget output
     */
    protected function render() {
        $settings = $this->get_settings_for_display();
        
        // Get API URL (custom or global)
        $api_url = $this->get_api_url($settings);
        
        // Get feedback URL
        $feedback_url = $this->get_feedback_url($settings);
        
        // Generate unique widget ID
        $widget_id = 'jobready_' . uniqid();
        
        // Render the widget HTML
        $this->render_widget_html($widget_id, $api_url, $feedback_url);
    }

    /**
     * Get the API URL for this widget
     */
    private function get_api_url($settings) {
        // Check for custom API URL first
        if (isset($settings['use_custom_api']) && 
            $settings['use_custom_api'] === 'yes' && 
            !empty($settings['custom_api_url']['url'])) {
            return untrailingslashit(esc_url($settings['custom_api_url']['url']));
        }
        
        // Fall back to global API URL
        return untrailingslashit(get_option('jobready_api_url', ''));
    }

    /**
     * Get the feedback URL for this widget
     */
    private function get_feedback_url($settings) {
        if (!empty($settings['professional_feedback_url']['url'])) {
            return esc_url($settings['professional_feedback_url']['url']);
        }
        return '';
    }

    /**
     * Render the widget HTML structure
     */
    private function render_widget_html($widget_id, $api_url, $feedback_url) {
        ?>
        <div id="<?php echo esc_attr($widget_id); ?>" 
             class="jobready-widget" 
             data-api-url="<?php echo esc_attr($api_url); ?>" 
             data-feedback-url="<?php echo esc_attr($feedback_url); ?>">
            
            <!-- Main Form -->
            <form class="jobready-form" enctype="multipart/form-data" novalidate>
                <!-- Resume Upload Field -->
                <div class="jobready-field">
                    <label for="<?php echo esc_attr($widget_id . '_resume'); ?>">
                        <?php esc_html_e('Upload Resume (PDF or DOCX)', 'jobready'); ?>
                    </label>
                    <input id="<?php echo esc_attr($widget_id . '_resume'); ?>" 
                           type="file" 
                           name="resume" 
                           accept=".pdf,.docx" 
                           required />
                    <small class="jobready-field-help">
                        <?php esc_html_e('Maximum file size: 5MB', 'jobready'); ?>
                    </small>
                </div>

                <!-- Job Description Field -->
                <div class="jobready-field">
                    <label for="<?php echo esc_attr($widget_id . '_jobdesc'); ?>">
                        <?php esc_html_e('Job Description', 'jobready'); ?>
                    </label>
                    <textarea id="<?php echo esc_attr($widget_id . '_jobdesc'); ?>" 
                              name="job_description" 
                              rows="6" 
                              placeholder="<?php esc_attr_e('Paste the job description here...', 'jobready'); ?>" 
                              required></textarea>
                    <small class="jobready-field-help">
                        <?php esc_html_e('Copy and paste the complete job description for accurate analysis', 'jobready'); ?>
                    </small>
                </div>

                <!-- Submit Button -->
                <div class="jobready-actions">
                    <button type="submit" class="jobready-submit">
                        <?php esc_html_e('Analyze Resume', 'jobready'); ?>
                    </button>
                </div>
            </form>

            <!-- Results Container -->
            <div class="jobready-results" aria-live="polite"></div>

            <!-- Optional Feedback Link -->
            <?php if ($feedback_url): ?>
                <div class="jobready-feedback-link">
                    <a href="<?php echo esc_url($feedback_url); ?>" 
                       target="_blank" 
                       rel="noopener noreferrer"
                       class="jobready-feedback-btn">
                        <?php esc_html_e('Need Professional Help?', 'jobready'); ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Get widget keywords for search
     */
    public function get_keywords() {
        return ['resume', 'ats', 'job', 'analysis', 'analyzer', 'cv', 'application'];
    }

    /**
     * Get widget custom CSS classes
     */
    public function get_custom_css_class() {
        return 'jobready-elementor-widget';
    }
}
