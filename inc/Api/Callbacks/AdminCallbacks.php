<?php 
/**
* @package RainbowSecure
 */
namespace rainbow_secure_Inc\Api\Callbacks;

use rainbow_secure_Inc\Base\BaseController;

class AdminCallbacks extends BaseController
{
	public function rainbowSecureOptionsGroup( $input )
	{
		return $input;
	}

	public function rainbowSecureAdminSection($args)
	{
		$allowed_tags = [
            'br' => [],
            'code' => [],
            'strong' => [],
            'em' => [],
            'p' => [],
        ];
		// $displayText = isset($args['text']) ? esc_html($args['text']) : '';
		$displayText = isset($args['text']) ? wp_kses(($args['text']), $allowed_tags) : '';
        echo ($displayText);
	}

	public function rainbowSecureText($args)
	{	
		$name = $args['label_for'];
		$tooltip = isset($args['toolTipInfo']) ? $args['toolTipInfo'] : '';
		$value = get_option($name);
        
		echo '<input type="text" class="regular-text" name="'.esc_attr($name).'" value="' .esc_attr($value). '" placeholder="">';
		
		if (!empty($tooltip)) {
			$allowed_tags = [
				'br' => [],
				'code' => [],
				'strong' => [],
				'em' => [],
				'p' => [],
			];
			echo "<p class='tooltip'>" . wp_kses($tooltip, $allowed_tags) . "</p>";
        	// echo "<p class='tooltip'>" . $tooltip . "</p>";
			// echo "<p class='tooltip'>" . esc_attr($tooltip) . "</p>";
		}
	}

	public function rainbowSecureCheckBox($args)
	{
		$name = $args['label_for'];
		$checkbox = get_option( $name );
		$tooltip = isset($args['toolTipInfo']) ? $args['toolTipInfo'] : '';
		echo '<input type="checkbox" name="' . esc_attr($name) . '" value="1" ' . ($checkbox ? 'checked' : '') . '>';
		if (!empty($tooltip)) {
			$allowed_tags = [
				'br' => [],
				'code' => [],
				'strong' => [],
				'em' => [],
				'p' => [],
			];
			echo "<p class='tooltip'>" . wp_kses($tooltip, $allowed_tags) . "</p>";
			// echo "<p class='tooltip'>" . esc_attr($tooltip) . "</p>";
		}
	}

	public function rainbowSecureTextArea($args)
	{
		$name = $args['label_for'];
		$value = esc_textarea(get_option($name));
		$tooltip = isset($args['toolTipInfo']) ? $args['toolTipInfo'] : '';
        echo '<textarea name="'.esc_attr($name).'" style="width:350px; height:200px; font-size:12px;">'.esc_textarea($value).'</textarea>';
		if (!empty($tooltip)) {
			$allowed_tags = [
				'br' => [],
				'code' => [],
				'strong' => [],
				'em' => [],
				'p' => [],
			];
			echo "<p class='tooltip'>" . wp_kses($tooltip, $allowed_tags) . "</p>";
			// echo "<p class='tooltip'>" . esc_attr($tooltip) . "</p>";
		}
	}
	
	public function rainbowSecureDropdown($args)
	{
		$name = $args['label_for'];
		$value = esc_attr(get_option($name));
		$tooltip = isset($args['toolTipInfo']) ? $args['toolTipInfo'] : '';
		//var_dump($args);
		//echo($args['options']);
		$options = isset($args['options']) ? $args['options'] : [];

		echo '<select name="'.esc_attr($name).'" id="'.esc_attr($name).'">';
		foreach ($options as $optionValue => $optionLabel) {
            $selected = selected($value, $optionValue, false);
            echo '<option value="' . esc_attr($optionValue) . '" ' . esc_attr($selected) . '>' . esc_html($optionLabel) . '</option>';
        }
		echo '</select>';
		if (!empty($tooltip)) {
			$allowed_tags = [
				'br' => [],
				'code' => [],
				'strong' => [],
				'em' => [],
				'p' => [],
			];
			echo "<p class='tooltip'>" . wp_kses($tooltip, $allowed_tags) . "</p>";
			// echo "<p class='tooltip'>" . esc_attr($tooltip) . "</p>";
		}
	}

	public function rainbowSecureMultipleDropdown($args)
	{
		$name = $args['label_for'] . '[]';
		$value = get_option($args['label_for']);
		$value = is_array($value) ? $value : [];
		$options = isset($args['options']) ? $args['options'] : [];

		echo '<select multiple="multiple" name="' . esc_attr($name) . '" id="' . esc_attr($args['label_for']) . '">';
        foreach ($options as $optionValue => $optionLabel) {
            $selected = in_array($optionValue, $value) ? 'selected="selected"' : '';
            echo '<option value="' . esc_attr($optionValue) . '" ' . esc_attr($selected) . '>' . esc_html($optionLabel) . '</option>';
        }
        echo '</select>';
    }


    public function adminDashboard()
    {
        require_once PLUGIN_PATH . 'templates/admin.php';
    }
}