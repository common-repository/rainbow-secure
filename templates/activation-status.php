<?php 
defined('ABSPATH') or die('Access Denied');
?>
<?php
// Fetch the activation status
$activation_status = rainbow_secure_check_activation_key_status();
$status_message = $activation_status['status'];
$expiration_date = $activation_status['expiration_date'];
$days_remaining = $activation_status['days_remaining'];
?>

<div class="wrap">
    <h1>Activation Status</h1>
    <div style="background-color: white; padding: 20px; border-radius: 8px; margin-top: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <p><strong>Status:</strong> <?php echo esc_html($status_message); ?></p>
        <p><strong>Expiration Date:</strong> <?php echo esc_html($expiration_date); ?></p>
        <p><strong>Days Remaining:</strong> <?php echo esc_html($days_remaining); ?> days</p>
    </div>
</div>

<?php
// Function to check activation key status
function rainbow_secure_check_activation_key_status() {
    $key = get_option('rainbow_secure_activation_key');
    $site_url = get_site_url();
    $request_url = "https://www.rsecureoffice.com/sso/rs_activatewebsiteplugin.aspx?ReqSiteURL={$site_url}&ReqSiteType=Wordpress&ReqSiteActivationKey={$key}&ReqMode=Activation";
    
    $response = wp_remote_get($request_url);
    $body = wp_remote_retrieve_body($response);

    if ($response['response']['code'] != '200') {
        return array(
            'status' => 'Invalid or Expired',
            'expiration_date' => 'N/A',
            'days_remaining' => 'N/A'
        );
    }

    // Parse the response to extract the expiration date
    if (strpos($body, 'AlreadyActivated|') !== false) {
        $parts = explode('|', $body);
        $expiration_date = trim($parts[1]);
        $expiration_timestamp = strtotime($expiration_date);
        $current_timestamp = time();
        $days_remaining = ($expiration_timestamp - $current_timestamp) / (60 * 60 * 24);

        return array(
            'status' => 'Active',
            'expiration_date' => $expiration_date,
            'days_remaining' => round($days_remaining)
        );
    }

    return array(
        'status' => 'Unknown',
        'expiration_date' => 'N/A',
        'days_remaining' => 'N/A'
    );
}
