<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class JobReady_Widget extends \Elementor\Widget_Base {

    public function get_name() {
        return 'jobready_widget';
    }

    public function get_title() {
        return 'JobReady Resume Analyzer';
    }

    public function get_icon() {
        // Use a commonly available Elementor icon
        return 'eicon-document-file';
    }

    public function get_categories() {
        return [ 'general' ];
    }

    protected function register_controls() {
        $this->start_controls_section(
            'section_content',
            [ 'label' => __( 'Settings', 'jobready' ) ]
        );

        $this->add_control(
            'use_custom_api',
            [
                'label' => __( 'Use Custom API URL', 'jobready' ),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'return_value' => 'yes',
                'default' => 'no',
            ]
        );

        $this->add_control(
            'custom_api_url',
            [
                'label' => __( 'Custom API URL', 'jobready' ),
                'type' => \Elementor\Controls_Manager::TEXT,
                'placeholder' => __( 'https://your-api.example.com (no trailing slash)', 'jobready' ),
                'condition' => [ 'use_custom_api' => 'yes' ],
            ]
        );

        $this->add_control(
            'professional_feedback_url',
            [
                'label' => __( 'Professional Feedback URL', 'jobready' ),
                'type' => \Elementor\Controls_Manager::URL,
                'placeholder' => __( 'https://yourdomain.com/professional-feedback', 'jobready' ),
                'default' => [
                    'url' => '',
                    'is_external' => true,
                    'nofollow' => true,
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();

        // Determine API URL: custom overrides global if selected
        $global_api = untrailingslashit( get_option( 'jobready_api_url', '' ) );
        $api_url = $global_api;
        if ( isset( $settings['use_custom_api'] ) && $settings['use_custom_api'] === 'yes' && ! empty( $settings['custom_api_url'] ) ) {
            $api_url = untrailingslashit( esc_url( $settings['custom_api_url'] ) );
        }

        $feedback_url = ! empty( $settings['professional_feedback_url']['url'] ) ? esc_url( $settings['professional_feedback_url']['url'] ) : '';

        // Unique ID so multiple widgets don't conflict
        $uid = uniqid( 'jobready_' );
        ?>

        <div id="<?php echo esc_attr( $uid ); ?>" class="jobready-widget" data-api-url="<?php echo esc_attr( $api_url ); ?>" data-feedback-url="<?php echo esc_attr( $feedback_url ); ?>">
            <form class="jobready-form" enctype="multipart/form-data" novalidate>
                <div class="jobready-field">
                    <label for="<?php echo esc_attr( $uid . '_resume' ); ?>"><?php esc_html_e( 'Upload Resume (PDF or DOCX)', 'jobready' ); ?></label>
                    <input id="<?php echo esc_attr( $uid . '_resume' ); ?>" type="file" name="resume" accept=".pdf,.docx" required />
                </div>

                <div class="jobready-field">
                    <label for="<?php echo esc_attr( $uid . '_jobdesc' ); ?>"><?php esc_html_e( 'Job Description', 'jobready' ); ?></label>
                    <textarea id="<?php echo esc_attr( $uid . '_jobdesc' ); ?>" name="job_description" rows="6" placeholder="<?php esc_attr_e( 'Paste the job description here...', 'jobready' ); ?>" required></textarea>
                </div>

                <div class="jobready-actions">
                    <button type="submit" class="jobready-submit"><?php esc_html_e( 'Get Recommendations', 'jobready' ); ?></button>
                </div>
            </form>

            <div class="jobready-results" aria-live="polite" style="margin-top:16px;"></div>
        </div>

        <?php
    }
}
