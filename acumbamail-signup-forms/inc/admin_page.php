<div class="wrap">
    <div id="poststuff">
        <div id="post-body" class="metabox-holder columns-2">
            <div id="post-body-content">
                <h1><?php _e('Acumbamail Plugin', 'acumbamail-signup-forms') ?></h1>
                <form method="POST" action="options.php" name="acumbamail_configuration">
                    <?php settings_fields('acumbamail_options') ?>
                    <?php do_settings_sections($acumbamail_settings_section) ?>
                    <?php echo '<br>';
                    submit_button(__('Delete forms', 'acumbamail-signup-forms'), 'delete button-primary', 'reset', false);
                    echo '  ';
                    submit_button(__('Save changes', 'acumbamail-signup-forms'), 'button-primary', 'submit', false); ?>
                </form>
            </div>
            <!-- sidebar -->
            <div id="postbox-container-1" class="postbox-container">
                <div class="meta-box-sortables">
                    <div class="postbox"><?php _e('', 'acumbamail-signup-forms') ?>
                        <h3><span><?php _e('How does the plugin work?', 'acumbamail-signup-forms') ?></span></h3>
                        <div class="inside">
                            <ol>
                                <li>
                                    <?php _e('Enter the auth token corresponding to your account', 'acumbamail-signup-forms') ?>. 
                                    <?php _e('You can find it', 'acumbamail-signup-forms') ?> 
                                    <a target="_blank" href="<?php _e('https://acumbamail.com/en/apidoc/getAuthToken/', 'acumbamail-signup-forms') ?>">
                                        <?php _e('here', 'acumbamail-signup-forms') ?>.
                                    </a>
                                </li>
                                <li>
                                    <?php _e('Select a list previously created in', 'acumbamail-signup-forms') ?> 
                                    <a target="_blank" href="<?php _e('https://acumbamail.com/en/', 'acumbamail-signup-forms') ?>">Acumbamail</a>. 
                                    <?php _e('The forms associated with the list will be displayed', 'acumbamail-signup-forms') ?>.
                                </li>
                                <li>
                                    <?php _e('Select the form that will be displayed on your Wordpress pages', 'acumbamail-signup-forms') ?>. 
                                    <?php _e('You can create new forms in', 'acumbamail-signup-forms') ?> 
                                    <a target="_blank" href="<?php _e('https://acumbamail.com/en/', 'acumbamail-signup-forms') ?>">Acumbamail</a>.
                                </li>
                                <li>
                                    <?php _e('In the Appearance/Widgets section, select the Acumbamail widget and place it where you want it to be displayed', 'acumbamail-signup-forms') ?>. 
                                    <?php _e('Remember that only the classic forms will be displayed where selected. The rest of form types already have their default position within the page', 'acumbamail-signup-forms') ?>.
                                </li>
                            </ol>
                            <p>
                                <?php _e('If you have enabled the Woocommerce plugin, you can also set up a list to which your customers will be subscribed after purchasing a product', 'acumbamail-signup-forms') ?>.
                            </p>
                        </div>
                    </div> <!-- .postbox -->
                    <div class="postbox">
                        <h3><span><?php _e('About Acumbamail', 'acumbamail-signup-forms') ?></span></h3>
                        <div class="inside">
                            <p>
                                <?php _e('Our team is composed of professionals who come from the online marketing, IT and design sectors', 'acumbamail-signup-forms') ?>. 
                                <?php _e('We count with years of experience to guarantee a high quality service', 'acumbamail-signup-forms') ?>. 
                                <?php _e('We will be happy to assist you whenever you need it', 'acumbamail-signup-forms') ?>. 
                            </p>
                        </div>
                    </div> <!-- .postbox -->
                </div> <!-- .meta-box-sortables -->
            </div> <!-- #postbox-container-1 .postbox-container -->
        </div>
    </div>
</div>
