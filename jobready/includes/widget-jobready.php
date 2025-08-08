<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class JobReady_Widget extends \Elementor\Widget_Base {

    public function get_name() {
        return 'jobready_widget';
    }

    public function get_title() {
        return 'JobReady Resume Scanner';
    }

    public function get_icon() {
        return 'eicon-form-horizontal';
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
                'label_on' => __( 'Yes', 'jobready' ),
                'label_off' => __( 'No', 'jobready' ),
                'return_value' => 'yes',
                'default' => 'no',
            ]
        );

        $this->add_control(
            'custom_api_url',
            [
                'label' => __( 'Custom API URL', 'jobready' ),
                'type' => \Elementor\Controls_Manager::TEXT,
                'placeholder' => __( 'https://your-api.example.com', 'jobready' ),
                'condition' => [ 'use_custom_api' => 'yes' ],
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();

        // Resolve API url: custom if enabled, else global
        $global_api = untrailingslashit( get_option( 'jobready_api_url', '' ) );
        $api_url = $global_api;
        if ( isset( $settings['use_custom_api'] ) && $settings['use_custom_api'] === 'yes' && ! empty( $settings['custom_api_url'] ) ) {
            $api_url = untrailingslashit( esc_url( $settings['custom_api_url'] ) );
        }

        // Use a unique ID for this widget instance to avoid collisions
        $uid = uniqid( 'jobready_' );

        // Render form - note name attributes must match backend: 'resume' and 'job_description'
        ?>
        <div class="jobready-widget" data-api-url="<?php echo esc_attr( $api_url ); ?>" id="<?php echo esc_attr( $uid ); ?>">
            <form class="jobready-form" enctype="multipart/form-data">
                <div class="jobready-field">
                    <label><?php esc_html_e( 'Upload Resume (PDF or DOCX)', 'jobready' ); ?></label>
                    <input type="file" name="resume" accept=".pdf,.docx" required />
                </div>

                <div class="jobready-field">
                    <label><?php esc_html_e( 'Job Description', 'jobready' ); ?></label>
                    <textarea name="job_description" rows="6" placeholder="<?php esc_attr_e( 'Paste the job description here...', 'jobready' ); ?>" required></textarea>
                </div>

                <div class="jobready-actions">
                    <button type="submit" class="jobready-submit"><?php esc_html_e( 'Get Recommendations', 'jobready' ); ?></button>
                </div>
            </form>

            <div class="jobready-results"></div>
        </div>
        <?php
    }
}
