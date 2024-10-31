<?php 
defined('ABSPATH') or die('Access Denied');
?>
<div class="wrap">
    <h1>Customize Actions and Links</h1>

    <?php 
    //handle the form submission
    if (isset($_POST['rainbow_secure_logo_upload'])) {
        // Verify the nonce
        if (!isset($_POST['rainbow_secure_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['rainbow_secure_nonce'])), 'rainbow_secure_request_activation_key')) {
            wp_die(esc_html__('Nonce verification failed', 'rainbow-secure'));
        }
    
        // Ensure file is uploaded
        if (empty(sanitize_file_name($_FILES['rainbow_secure_logo_img']['name']))) {
            wp_die(esc_html__('No file was uploaded.', 'rainbow-secure'));
        }
        
        // Validate file type
        $allowed_mime_types = array('image/jpeg', 'image/png', 'image/gif');
        $file_type = wp_check_filetype(sanitize_file_name($_FILES['rainbow_secure_logo_img']['name']));
        if (!in_array($file_type['type'], $allowed_mime_types)) {
            wp_die(esc_html__('Invalid file type. Only JPEG, PNG, and GIF are allowed.', 'rainbow-secure'));
        }
    
        // Handle the file upload
        $uploaded_logo = wp_handle_upload($_FILES['rainbow_secure_logo_img'], array('test_form' => false));
        if (is_wp_error($uploaded_logo)) {
            wp_die(esc_html__('File upload failed: ', 'rainbow-secure') . esc_html($uploaded_logo->get_error_message()));
        }
    
        // Delete the previous logo file if it exists
        $previous_logo_url = get_option('rainbow_secure_saml_logo_url');
        if (!empty($previous_logo_url)) {
            $previous_logo_path = str_replace(home_url('/'), ABSPATH, $previous_logo_url);
            if (file_exists($previous_logo_path)) {
                wp_delete_file($previous_logo_path);
            }
        }
    
        // Update the new logo URL in the database
        update_option('rainbow_secure_saml_logo_url', esc_url_raw($uploaded_logo['url']));
    }    

    settings_errors();
    ?>
    
    <form method="post" enctype="multipart/form-data" style="margin-top: 20px;">
        <?php wp_nonce_field('rainbow_secure_request_activation_key', 'rainbow_secure_nonce'); ?>
        <div style="background-color: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <h2>Upload Logo</h2>
            <div style="margin-bottom: 15px;">
                <input type="file" name="rainbow_secure_logo_img" required style="width: 100%; padding: 10px; border: 1px solid #ccc; border-radius: 4px;">
            </div>
            <input type="hidden" name="rainbow_secure_logo_upload" value="1">
            <input type="submit" value="Upload Logo" class="button button-primary" style="background-color: #0073aa; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer;">
        </div>
    </form>

    <?php 
    $logo_url = get_option('rainbow_secure_saml_logo_url');
    $background_color = get_option('rainbow_secure_background_color', '#fff');

    if (!empty($logo_url)) {
        echo '<div style="font-size: 110%;padding:8px;text-align: center;">';
        echo '<h2>Current Logo</h2>';
        echo '<img src="' . esc_url($logo_url) . '" alt="SAML Logo" style="display: block;background: ' . esc_attr($background_color) . '; margin: 0 auto; max-width: 280px; max-height: 120px;">';
        echo '</div>';
    }
    ?>
</div>
