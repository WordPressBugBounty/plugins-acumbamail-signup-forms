<?php
//if uninstall not called from WordPress exit
if ( !defined( 'WP_UNINSTALL_PLUGIN' ) )
    exit();

delete_option('acumbamail_options');

try {
    $translation_files = glob(WP_CONTENT_DIR . '/languages/plugins/acumbamail-signup-forms-es_ES.*', GLOB_BRACE);
    if ($translation_files) {
        foreach ($translation_files as $file) {
            if (is_file($file)) {
                wp_delete_file($file);
            }
        }
    }
} catch (Exception $error) {
    //error_log($error->getMessage());
}
?>
