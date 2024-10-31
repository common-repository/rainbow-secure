<?php 
defined('ABSPATH') or die('Access Denied');
?>
<div class="wrap">
    <h1>Request for Activation-Key</h1>

    <?php
    // Check if the activation key has already been requested
    $activation_status = get_option('rainbow_secure_activation_status', 'NotRequested');

    if ($activation_status === 'Requested') {
        echo '<div style="text-align: center; margin-top: 20px; background-color: #f4f4f4; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">';
        echo '<p>' . esc_html__('You have already requested an activation key. Please check your email for further instructions.', 'rainbow-secure') . '</p>';
        echo '</div>';
    } else {
        // Handle the form submission
        $submitted = false;
        $api_response = '';
        if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
            // Verify the nonce
            if (!isset($_POST['rainbow_secure_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['rainbow_secure_nonce'])), 'rainbow_secure_request_activation_key')) {
                wp_die(esc_html__('Nonce verification failed', 'rainbow-secure'));
            }

            $submitted = true;
            // Collect post data
            $company = isset($_POST['ReqCompany']) ? sanitize_text_field(wp_unslash($_POST['ReqCompany'])) : '';
            $admin_name = isset($_POST['ReqAdminName']) ? sanitize_text_field(wp_unslash($_POST['ReqAdminName'])) : '';
            $email = isset($_POST['ReqAdminEmail']) ? sanitize_email(wp_unslash($_POST['ReqAdminEmail'])) : '';
            $phone = isset($_POST['ReqAdminPhone']) ? sanitize_text_field(wp_unslash($_POST['ReqAdminPhone'])) : '';
            $company_type = isset($_POST['ReqCompanyType']) ? sanitize_text_field(wp_unslash($_POST['ReqCompanyType'])) : '';
            $industry = isset($_POST['ReqIndustry']) ? sanitize_text_field(wp_unslash($_POST['ReqIndustry'])) : '';
            $size = isset($_POST['ReqSize']) ? sanitize_text_field(wp_unslash($_POST['ReqSize'])) : '';        
            
            // API URL
            $api_url = "https://www.rsecureoffice.com/sso/rs_activatewebsiteplugin.aspx";
            $query_args = array(
                'ReqSiteURL' => urlencode(get_site_url()),
                'ReqSiteType' => 'wordpress',
                'ReqCompany' => $company,
                'ReqCompanyType' => $company_type,
                'ReqCompanyIndustry' => $industry,
                'ReqCompanyAdmin' => $admin_name,
                'ReqAdminEmail' => $email,
                'ReqAdminPhone' => $phone,
                'ReqLicenseSize' => $size,
                'ReqMode' => 'Request'
            );
            $api_url = add_query_arg($query_args, $api_url);

            // API request
            $response = wp_remote_get($api_url);
            $api_response = wp_remote_retrieve_body($response);

            // Log response
            error_log(print_r($api_response, true));

            // Check the response and update the status option
            if (strpos($api_response, 'Requested|') !== false) {
                update_option('rainbow_secure_activation_status', 'Requested');
            }
        }

        // Display success message or form based on submission status
        if ($submitted) {
            $response_message = '';
            switch (trim($api_response)) {
                case 'Requested|':
                    $response_message = 'Your request has been submitted successfully. We will get back to you soon.';
                    break;
                case 'AlreadyRequested|':
                    $response_message = 'You have already requested activation. Please check your email for further instructions.';
                    update_option('rainbow_secure_activation_status', 'Requested');
                    break;
                case 'Activated|':
                    $response_message = 'Your plugin has been activated successfully.';
                    break;
                default:
                    $response_message = 'An unexpected error occurred. Please try again.';
            }
            echo '<div style="text-align: center; margin-top: 20px; background-color: #f4f4f4; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">' . esc_html($response_message) . '</div>';
        } else {
            ?>
            <div style="background-color: white; padding: 20px; border-radius: 8px; margin-top: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                <form action="<?php echo esc_url(admin_url('admin.php?page=rainbow_secure_activate_plugin')); ?>" method="post" style="max-width: 500px;">
                <?php wp_nonce_field('rainbow_secure_request_activation_key', 'rainbow_secure_nonce'); ?>
                    <div style="margin-bottom: 15px;">
                        <label for="ReqCompany" style="display: block; margin-bottom: 5px;">Company Name*:</label>
                        <input type="text" id="ReqCompany" name="ReqCompany" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label for="ReqAdminName" style="display: block; margin-bottom: 5px;">Admin Name*:</label>
                        <input type="text" id="ReqAdminName" name="ReqAdminName" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label for="ReqAdminEmail" style="display: block; margin-bottom: 5px;">Admin Email*:</label>
                        <input type="email" id="ReqAdminEmail" name="ReqAdminEmail" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label for="ReqAdminPhone" style="display: block; margin-bottom: 5px;">Admin Phone:</label>
                        <input type="text" id="ReqAdminPhone" name="ReqAdminPhone" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label for="ReqCompanyType" style="display: block; margin-bottom: 5px;">Type:</label>
                        <select id="ReqCompanyType" name="ReqCompanyType" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                            <option value="">Select Type</option>
                            <option value="Commercial">Commercial</option>
                            <option value="Government">Government</option>
                            <option value="Startup">Startup</option>
                            <option value="Non Profit">Non Profit</option>
                        </select>
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label for="ReqIndustry" style="display: block; margin-bottom: 5px;">Industry:</label>
                        <select id="ReqIndustry" name="ReqIndustry" style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                            <option value="">Select Industry</option>
                            <option value="Advertising & Marketing">Advertising & Marketing</option>
                            <option value="Computer & Technology">Computer & Technology</option>
                            <option value="Education">Education</option>
                            <option value="Entertainment">Entertainment</option>
                            <option value="Finance & Economics">Finance & Economics</option>
                            <option value="Healthcare">Healthcare</option>
                            <option value="Pharmaceutical">Pharmaceutical</option>
                            <option value="Telecommunication">Telecommunication</option>
                        </select>
                    </div>
                    <div style="margin-bottom: 15px;">
                        <label for="ReqSize" style="display: block; margin-bottom: 5px;">Size (Enter a number):</label>
                        <input type="number" id="ReqSize" name="ReqSize" min="1" step="1" required style="width: 100%; padding: 8px; border: 1px solid #ccc; border-radius: 4px;">
                    </div>
                    <input type="submit" value="Request Activation Key" class="button button-primary" style="background-color: #0073aa; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;">
                </form>
            </div>
            <?php
        }
    }
    ?>
</div>
