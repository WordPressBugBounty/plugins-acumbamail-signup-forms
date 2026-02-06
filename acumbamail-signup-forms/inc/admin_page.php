<div class="wrap">
    <div id="poststuff">
        <div id="post-body" class="metabox-holder columns-2">
            <div id="post-body-content">
                <h1><?php esc_html_e('Acumbamail Plugin', 'acumbamail-signup-forms') ?></h1>
                <form method="POST" action="options.php" name="acumbamail_configuration">
                    <?php settings_fields('acumbamail_options') ?>
                    <?php do_settings_sections($acumbamail_settings_section) ?>
                    <?php echo '<br>';
                    submit_button(__('Delete forms', 'acumbamail-signup-forms'), 'delete button-primary', 'reset', false);
                    echo '  ';
                    submit_button(__('Save changes', 'acumbamail-signup-forms'), 'button-primary', 'submit', false); ?>
                </form>
                <div style="margin-top:14px"></div>
                <?php if (!empty($acumbamail_cart_section)) { do_settings_sections($acumbamail_cart_section); } ?>
            </div>
            <!-- sidebar -->
            <div id="postbox-container-1" class="postbox-container">
                <div class="meta-box-sortables">
                    <div class="postbox"><?php esc_html_e(" ", 'acumbamail-signup-forms') // phpcs:ignore WordPress.WP.I18n.NoEmptyStrings ?>
                        <h3><span><?php esc_html_e('How does the plugin work?', 'acumbamail-signup-forms') ?></span></h3>
                        <div class="inside">
                            <ol>
                                <li>
                                    <?php esc_html_e('Enter the auth token corresponding to your account', 'acumbamail-signup-forms') ?>.
                                    <?php esc_html_e('You can find it', 'acumbamail-signup-forms') ?>
                                    <a target="_blank" href="<?php echo esc_url(__('https://acumbamail.com/en/apidoc/getAuthToken/', 'acumbamail-signup-forms')) ?>">
                                        <?php esc_html_e('here', 'acumbamail-signup-forms') ?>.
                                    </a>
                                </li>
                                <li>
                                    <?php esc_html_e('Select a list previously created in', 'acumbamail-signup-forms') ?>
                                    <a target="_blank" href="<?php echo esc_url(__('https://acumbamail.com/en/', 'acumbamail-signup-forms')) ?>">Acumbamail</a>.
                                    <?php esc_html_e('The forms associated with the list will be displayed', 'acumbamail-signup-forms') ?>.
                                </li>
                                <li>
                                    <?php esc_html_e('Select the form that will be displayed on your Wordpress pages', 'acumbamail-signup-forms') ?>.
                                    <?php esc_html_e('You can create new forms in', 'acumbamail-signup-forms') ?>
                                    <a target="_blank" href="<?php echo esc_url(__('https://acumbamail.com/en/', 'acumbamail-signup-forms')) ?>">Acumbamail</a>.
                                </li>
                                <li>
                                    <?php esc_html_e('In the Appearance/Widgets section, select the Acumbamail widget and place it where you want it to be displayed', 'acumbamail-signup-forms') ?>.
                                    <?php esc_html_e('Remember that only the classic forms will be displayed where selected. The rest of form types already have their default position within the page', 'acumbamail-signup-forms') ?>.
                                </li>
                            </ol>
                            <p>
                                <?php esc_html_e('If you have enabled the Woocommerce plugin, you can also set up a list to which your customers will be subscribed after purchasing a product', 'acumbamail-signup-forms') ?>.
                            </p>
                        </div>
                    </div> <!-- .postbox -->
                    <div class="postbox">
                        <h3><span><?php esc_html_e('About Acumbamail', 'acumbamail-signup-forms') ?></span></h3>
                        <div class="inside">
                            <p>
                                <?php esc_html_e('Our team is composed of professionals who come from the online marketing, IT and design sectors', 'acumbamail-signup-forms') ?>.
                                <?php esc_html_e('We count with years of experience to guarantee a high quality service', 'acumbamail-signup-forms') ?>.
                                <?php esc_html_e('We will be happy to assist you whenever you need it', 'acumbamail-signup-forms') ?>.
                            </p>
                        </div>
                    </div> <!-- .postbox -->
                </div> <!-- .meta-box-sortables -->
            </div> <!-- #postbox-container-1 .postbox-container -->
        </div>
    </div>
</div>

<script type="text/javascript">
    if (document.getElementById('acumbamail_woo_cart_update_state_button')) {
        document.getElementById('acumbamail_woo_cart_update_state_button').addEventListener('click', function() {
            fetch(ajaxurl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'action=action_update_state_cart&nonce=' + '<?php echo wp_create_nonce("acumbamail_cart_nonce"); ?>'
            })
            .then(response => response.text())
            .then(data => {
                document.getElementById('acumbamail_state_cart').value = data;
            });
        });
    }
</script>
