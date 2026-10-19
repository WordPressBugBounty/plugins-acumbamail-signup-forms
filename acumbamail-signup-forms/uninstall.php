<?php
//if uninstall not called from WordPress exit
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) )
    exit();

delete_option('acumbamail_options');
delete_option('acumbamail_woo_statuses_since');
delete_option('custom_user_logged_in');
delete_metadata('user', 0, '_acumbamail_login_pending', '', true);

try {
    $acumbamail_translation_files = glob(WP_CONTENT_DIR . '/languages/plugins/acumbamail-signup-forms-es_ES.*');
    if ($acumbamail_translation_files) {
        foreach ($acumbamail_translation_files as $acumbamail_file) {
            if (is_file($acumbamail_file)) {
                wp_delete_file($acumbamail_file);
            }
        }
    }
} catch (Exception $acumbamail_error) {
    //error_log($acumbamail_error->getMessage());
}
?>
