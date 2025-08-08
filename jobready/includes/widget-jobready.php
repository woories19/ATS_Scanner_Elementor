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
        return ['general'];
    }

    protected function register_controls() {
        $this->start_controls_section(
            'section_content',
            ['label' => __('Settings', 'jobready')]
        );

        $this->add_control(
            'use_custom_api',
            [
                'label' => __('Use Custom API URL?', 'jobready'),
                'type' => \Elementor\Controls_Manager::SWITCHER,
                'label_on' => __('Yes', 'jobready'),
                'label_off' => __('No', 'jobready'),
                'return_value' => 'yes',
                'default' => 'no',
            ]
        );

        $this->add_control(
            'custom_api_url',
            [
                'label' => __('Custom API URL', 'jobready'),
                'type' => \Elementor\Controls_Manager::TEXT,
                'placeholder' => __('https://yourapi.com/endpoint', 'jobready'),
                'condition' => [
                    'use_custom_api' => 'yes',
                ],
            ]
        );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        $api_url = ( $settings['use_custom_api'] === 'yes' && ! empty($settings['custom_api_url']) )
            ? esc_url($settings['custom_api_url'])
            : esc_url(get_option('jobready_api_url'));

        ?>
        <div class="jobready-widget" data-api-url="<?php echo $api_url; ?>">
            <textarea class="jobready-resume" placeholder="Paste your resume here"></textarea>
            <textarea class="jobready-job" placeholder="Paste job description here"></textarea>
            <button class="jobready-submit">Get Recommendations</button>
            <div class="jobready-results"></div>
        </div>
        <?php
    }
}
