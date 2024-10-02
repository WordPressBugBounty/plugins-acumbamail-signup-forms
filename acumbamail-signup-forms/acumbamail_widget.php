<?php

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );

class Acumbamail_Widget extends WP_Widget {

    public function __construct() {
        $widget_opts = array(
            'classname' => 'Acumbamail',
            'description' => __('Widget for integrating Acumbamail forms into your pages', 'acumbamail-signup-forms')
        );
        parent::__construct('acumbamail', 'Acumbamail', $widget_opts);
    }

    public function widget($args, $instance) {
        $this->acumbamail_display_form();
    }

    public function form($instance) {
    }

    public function update($new_instance, $old_instance) {
    }

    function acumbamail_get_form_details() {
        $options = get_option('acumbamail_options');
        $api = new AcumbamailAPI('', $options['auth_token']);
        $form_details = $api->getFormDetails($options['form_id']);

        return $form_details;
    }

    function acumbamail_display_form() {
        $form_details = acumbamail_get_form_details();
        $output = var_export($form_details, true);
        if ($form_details) {
            if ($form_details['classic'] == 'yes') {
                echo '<br><div id=' . $form_details['div_id'] . '></div>';
            }
            echo '<script type="text/javascript" src=' . $form_details['js_link'] . '></script>';
        }
    }
}

