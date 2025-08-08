<?php
if ( ! defined( 'ABSPATH' ) ) exit;

// Add menu item
function jobready_add_admin_menu() {
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
add_action( 'admin_menu', 'jobready_add_admin_menu' );

// Register setting
function jobready_register_settings() {
    register_setting( 'jobready_settings_group', 'jobready_api_url' );
}
add_action( 'admin_init', 'jobready_register_settings' );

// Settings Page HTML
function jobready_settings_page_html() {
    ?>
    <div class="wrap">
        <h1>JobReady Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields( 'jobready_settings_group' );
            do_settings_sections( 'jobready_settings_group' );
            ?>
            <table class="form-table">
                <tr valign="top">
                    <th scope="row">Default API URL</th>
                    <td>
                        <input type="text" name="jobready_api_url"
                               value="<?php echo esc_attr( get_option('jobready_api_url') ); ?>"
                               style="width: 400px;">
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}
