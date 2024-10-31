<?php 
defined('ABSPATH') or die('Access Denied');
?>
<div class="wrap">
    <h1>Rainbow Secure SSO Plugin</h1>
    <?php settings_errors(); ?>

    <?php if (isset($_GET['upload'])): ?>
        <?php 
            $upload_status = sanitize_text_field(wp_unslash($_GET['upload'])); 
        ?>
        <?php if ($upload_status === 'success'): ?>
            <div class="notice notice-success is-dismissible">
                <p><?php esc_html_e('Metadata uploaded and processed successfully.', 'rainbow-secure'); ?></p>
            </div>
        <?php elseif ($upload_status === 'error' && isset($_GET['message'])): ?>
            <div class="notice notice-error is-dismissible">
                <p><?php echo esc_html(sanitize_text_field(wp_unslash($_GET['message']))); ?></p>
            </div>
        <?php endif; ?>
    <?php endif; ?>

    <ul class="nav nav-tabs">
        <li class="active"><a href="#tab-1">Manage Settings</a></li>
        <li><a href="#tab-2">Upload IDP metadata</a></li>
        <li><a href="#tab-3">Download SP metadata</a></li>
        <li><a href="#tab-4">Activate Plugin</a></li>
        <li><a href="#tab-5">Export Users</a></li>
        <li><a href="#tab-6">Instructions</a></li>
    </ul>

    <div class="tab-content">
        <div id="tab-1" class="tab-pane active">
            <?php
                // Checking the activation key status
                $is_verified = rainbow_secure_check_activation_key();
                
                // Checks the result and display the appropriate message
                if ($is_verified) {
                    echo '<div style="display:flex; float:right; color:green">Activation key Verified</div>';
                } else {
                    echo '<div style="display:flex; float:right; color:red">Activation Key Not Verified</div>';
                }
                echo '<br><div style="display:flex; float:right;"><a href="' . esc_url(home_url('/?saml_validate_config')) . '" target="_blank" style="color: blue; text-decoration: underline;">Validate Configuration</a></div>';
            ?>
            <form method="post" action="options.php">
                <?php 
                    settings_fields('rainbow_secure_options_group');
                    do_settings_sections('rainbow_secure');
                    submit_button();
                ?>
            </form>
        </div>
        <div id="tab-2" class="tab-pane">
            <h3>Upload IDP metadata</h3>
            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" enctype="multipart/form-data" style="background-color: #f7f7f7; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <input type="hidden" name="action" value="upload_metadata">
                <?php wp_nonce_field('upload_metadata_action', 'upload_metadata_nonce'); ?>
                <label for="metadata_file" style="display: block; margin-bottom: 10px;">Select metadata file:</label>
                <input type="file" name="metadata_file" id="metadata_file" required style="display: block; margin-bottom: 20px;">
                <button type="submit" name="upload_metadata" class="button button-primary" style="background-color: #0073aa; color: white; border: none; padding: 10px 20px; border-radius: 5px; cursor: pointer;">Upload Metadata</button>
            </form>
        </div>

        <div id="tab-3" class="tab-pane">
            <h3>Download Service Provider metadata</h3>
            <form method="get" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" style="background-color: #f7f7f7; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <input type="hidden" name="action" value="download_sp_metadata">
                <p>Click this button to download CSV file with user data<p>
                <button type="submit" class="button button-primary">Download SP Metadata</button>
            </form>
        </div>
        <div id="tab-4" class="tab-pane">
            <?php include plugin_dir_path(__FILE__) . 'activation-key.php'; ?>
        </div>
        <div id="tab-5" class="tab-pane">
            <?php 
                echo '<div class="wrap"><h3>Export Users</h3>';
                echo '<div style="background-color: #f7f7f7; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
                echo '<h2>Step 1: Export Users Data</h2>';
                echo '<form action="' . esc_url(admin_url('admin.php')) . '" method="get">';
                echo '<label for="action">Click this button to download CSV file with user data</label><br>';
                echo '<input type="hidden" name="action" value="export_users">';
                submit_button('Export Users to CSV');
                echo '</form>';
                echo '<h2>Step 2: Add Users to IDP</h2>';
                echo '<p>Click here to login to your Rainbow Secure Dashboard and add your existing users to the IDP.</p>';
                echo '<a href="https://rainbowsecure.com" target="_blank" class="button button-primary">Go to Rainbow Secure Dashboard</a>';
                echo '</div>';
                echo '</div>';
            ?>
        </div>
        <div id="tab-6" class="tab-pane">
            <h3>Getting Started with Rainbow Secure SSO Plugin</h3>
            <div style="background-color: #f7f7f7; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <h3>Follow the steps below to start using the Rainbow Secure SSO Plugin:</h3>
                <ol>
                    <h3><li><strong>Request for Activation Key:</strong></h3>
                        <ul>
                            <li>Navigate to the Activate Plugin tab.</li>
                            <li>Fill in the required information such as Company Name, Admin Email, Admin Phone, etc.</li>
                            <li>Submit the form to request your activation key.</li>
                        </ul>
                        <div class="rainbow-secure-instructions-img">
                            <img src="<?php echo esc_url( plugins_url('assets/img/rainbow-secure-activate-plugin.png', dirname(__FILE__)) ); ?>" alt="Placeholder Image" style="max-width: 100%; height: auto; max-height: 600px; border: 1px solid #ccc; border-radius: 4px;">
                        </div>
                    </li>
                    <h3><li><strong>Receive and Enter Activation Key:</strong></h3>
                        <ul>
                            <li>Our team will send you the activation key along with configuration files.</li>
                            <li>Once received, enter the activation key in the Manage Settings tab.</li>
                        </ul>
                    </li>
                    <h3><li><strong>Upload IDP Metadata:</strong></h3>
                        <ul>
                            <li>Navigate to the Upload IDP Metadata tab.</li>
                            <li>Select and upload the IDP metadata file provided by our team.</li>
                        </ul>
                        <div class="rainbow-secure-instructions-img">
                            <img src="<?php echo esc_url( plugins_url('assets/img/rainbow-secure-upload-idp-metadata.png', dirname(__FILE__)) ); ?>" alt="Placeholder Image" style="max-width: 100%; height: auto; max-height: 600px; border: 1px solid #ccc; border-radius: 4px;">
                        </div>
                    </li>
                    <h3><li><strong>Configure Attribute Mapping:</strong></h3>
                        <ul>
                            <li>Map the required attributes in the plugin settings to ensure proper integration.</li>
                        </ul>
                        <div class="rainbow-secure-instructions-img">
                            <img src="<?php echo esc_url( plugins_url('assets/img/rainbow-secure-attribute-mapping.png', dirname(__FILE__)) ); ?>" alt="Placeholder Image" style="max-width: 100%; height: auto; max-height: 600px; border: 1px solid #ccc; border-radius: 4px;">
                        </div>
                    </li>
                    <h3><li><strong>Export Existing Users:</strong></h3>
                        <ul>
                            <li>Navigate to the Export Users tab.</li>
                            <li>Export the existing users of your WordPress website.</li>
                            <li>Send the exported file to our team or upload it to the Rainbow Secure Dashboard to add users to the IDP.</li>
                        </ul>
                        <div class="rainbow-secure-instructions-img">
                            <img src="<?php echo esc_url( plugins_url('assets/img/rainbow-secure-export-user.png', dirname(__FILE__)) ); ?>" alt="Placeholder Image" style="max-width: 100%; height: auto; max-height: 600px; border: 1px solid #ccc; border-radius: 4px;">
                        </div>
                    </li>
                    <h3><li><strong>Customize Login Page:</strong></h3>
                        <ul>
                            <li>Upload your company logo and choose a background color in the Manage Settings tab.</li>
                        </ul>
                        <div class="rainbow-secure-instructions-img">
                            <img src="<?php echo esc_url( plugins_url('assets/img/rainbow-secure-customize-actions.png', dirname(__FILE__)) ); ?>" alt="Placeholder Image" style="max-width: 100%; height: auto; max-height: 600px; border: 1px solid #ccc; border-radius: 4px;">
                        </div>
                    </li>
                </ol>
            </div>
        </div>
    </div>
</div>
