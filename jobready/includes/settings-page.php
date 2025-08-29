<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// Register settings and add admin menu
function jobready_register_settings_and_menu() {
    register_setting( 'jobready_settings_group', 'jobready_api_url' );
    register_setting( 'jobready_settings_group', 'jobready_webhook_token' );

    add_menu_page(
        'JobReady Settings',
        'JobReady',
        'manage_options',
        'jobready-settings',
        'jobready_settings_page_html',
        'dashicons-clipboard',
        20
    );
}
add_action( 'admin_menu', 'jobready_register_settings_and_menu' );

function jobready_settings_page_html() {
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }
    ?>
    <div class="wrap">
        <h1>JobReady Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields( 'jobready_settings_group' );
            do_settings_sections( 'jobready_settings_group' );
            $api_url = esc_attr( get_option( 'jobready_api_url', '' ) );
            $token   = esc_attr( get_option( 'jobready_webhook_token', '' ) );
            ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Default API URL</th>
                    <td>
                        <input type="text" name="jobready_api_url" value="<?php echo $api_url; ?>" style="width:420px;" placeholder="https://your-api.example.com" />
                        <p class="description">Enter the default backend API URL (no trailing slash). Can be overridden per widget.</p>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row">Webhook Token</th>
                    <td>
                        <input type="text" name="jobready_webhook_token" value="<?php echo $token; ?>" style="width:420px;" placeholder="random-long-secret-token" />
                        <p class="description">Set a shared secret token. PythonAnywhere will send it as header <code>X-JobReady-Token</code> to authorize email dispatch.</p>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}
