<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              https://serhiienko.se
 * @since             1.0.0
 * @package           Wp_Downloadista
 *
 * @wordpress-plugin
 * Plugin Name:       Downloadista
 * Plugin URI:        https://downloadista.com
 * Description:       Downloadista is a plugin for Wordpress.
 * Version:           1.0.0
 * Author:            Andrii Serhiienko
 * Author URI:        https://serhiienko.se/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       wp-downloadista
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Currently plugin version.
 * Start at version 1.0.0 and use SemVer - https://semver.org
 * Rename this for your plugin and update it as you release new versions.
 */
define( 'WP_DOWNLOADISTA_VERSION', '1.0.0' );

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-wp-downloadista-activator.php
 */
function activate_wp_downloadista() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wp-downloadista-activator.php';
	Wp_Downloadista_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-wp-downloadista-deactivator.php
 */
function deactivate_wp_downloadista() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wp-downloadista-deactivator.php';
	Wp_Downloadista_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_wp_downloadista' );
register_deactivation_hook( __FILE__, 'deactivate_wp_downloadista' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-wp-downloadista.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_wp_downloadista() {

	$plugin = new Wp_Downloadista();
	$plugin->run();

}


function downloadista_shortcode_handler($atts, $content, $tag) {
    $a = shortcode_atts(array(
        'file_id' => 0, // default value if no file is provided
        'file_name' => 'My nice file',
    ), $atts);

	$file_id = intval($a['file_id']);
	if ($file_id <= 0) {
		return "No attachment ID provided.";
	}
	$file_url = wp_get_attachment_url($file_id);
	if (!$file_url) {
		return "Invalid attachment ID: " . $file_id;
	}
	$file_name = $a['file_name'];

    global $wpdb;
	$table_name = $wpdb->prefix . 'downloadista_links';
	// Check if a valid, unexpired link exists
	$link = $wpdb->get_row($wpdb->prepare(
		"SELECT * FROM $table_name WHERE attachment_id = %d",
		$file_id
	));

	if ($link) {
		$expiration_time = new DateTime($link->expiration);
		$utc_expiration = $expiration_time->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z');

		// Return existing link if not expired
        if (new DateTime() < $expiration_time) {
	        $download_url = add_query_arg( 'download_key', $link->download_key, get_site_url( null, '/downloadista-page/' ) );

	        return downloadista_generate_download_link_js( $file_id, $file_name, $download_url, $utc_expiration, strtolower( substr( $file_url, - 4 ) ) === '.zip' );
        } else {
	        return downloadista_generate_new_download_link($file_id, $file_name);
        }
	} else {
		// Generate a new link if none exists or it has expired
		return downloadista_generate_new_download_link($file_id, $file_name);
	}
}

function downloadista_generate_new_download_link($file_id, $file_name) {
	global $wpdb;
	$table_name = $wpdb->prefix . 'downloadista_links';
	$file_url = wp_get_attachment_url($file_id);
	$download_key = md5(uniqid(rand(), true));

	$options = get_option('downloadista_options');
	$link_expiration = $options['link_expiration'];
	$expiration_time = new DateTime('+' . $link_expiration. ' minutes');
	$utc_expiration = $expiration_time->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d\TH:i:s\Z');

	$wpdb->replace(
		$table_name,
		array(
			'file_url' => $file_url,
			'download_key' => $download_key,
			'expiration' => $expiration_time->format('Y-m-d H:i:s'),
			'attachment_id' => $file_id
		),
		array('%s', '%s', '%s', '%d')
	);
	// Generate the URL for the temporary download page
	$download_url = add_query_arg(array(
		'download_key' => $download_key
	), get_site_url(null, '/downloadista-page/'));
	return downloadista_generate_download_link_js($file_id, $file_name, $download_url, $utc_expiration, strtolower(substr($file_url, -4)) === '.zip');
}

function downloadista_generate_download_link_js($file_id, $file_desc, $file_url, $utc_expiration, $is_zip) {
	// JavaScript for countdown and displaying the download link
	$options = get_option('downloadista_options');
	$button_text_color = $options['button_text_color'];
	$button_background_color = $options['button_background_color'];
	$pane_text_color = $options['pane_text_color'];
	$pane_background_color = $options['pane_background_color'];
	$button_text = $options['button_text'];
	$pane_border_size = $options['pane_border_size'];
	$icon_color = $options['icon_color'];

	$svg_zip_icon = '<svg class="left-svg" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="36" height="36" color="' . $icon_color . '" fill="none">
    <path d="M3.5 13V12.1963C3.5 9.22889 3.5 7.7452 3.96894 6.56021C4.72281 4.65518 6.31714 3.15252 8.33836 2.44198C9.59563 2 11.1698 2 14.3182 2C16.1173 2 17.0168 2 17.7352 2.25256C18.8902 2.65858 19.8012 3.51725 20.232 4.60584C20.5 5.28297 20.5 6.13079 20.5 7.82643V12.0142V13" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
    <path d="M3.5 12C3.5 10.1591 4.99238 8.66667 6.83333 8.66667C7.49912 8.66667 8.28404 8.78333 8.93137 8.60988C9.50652 8.45576 9.95576 8.00652 10.1099 7.43136C10.2833 6.78404 10.1667 5.99912 10.1667 5.33333C10.1667 3.49238 11.6591 2 13.5 2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
    <path d="M3.5 16H6.9C7.14721 16 7.28833 16.2822 7.14 16.48L3.72 21.04C3.42334 21.4355 3.70557 22 4.2 22H7.5M10.5 16H12.25M12.25 16H14M12.25 16V21.6787M10.5 22H14M17 22V16H18.8618C19.5675 16 20.2977 16.3516 20.4492 17.0408C20.5128 17.33 20.5109 17.6038 20.4488 17.8923C20.2936 18.6138 19.5392 19 18.8012 19H18" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
</svg>';
	$file_icon = $is_zip ? '<i>' . $svg_zip_icon .'</i>' : '';
	$file_size = downloadista_get_attachment_file_size($file_id);
    $file_size_str = '';
	if (is_numeric($file_size) && $file_size > 0) {
		$file_size_str = 'Size: ' . size_format($file_size, 2);
	}
    $file_name = 'Name: ' . downloadista_get_file_name_by_attachment_id($file_id);

	$download_box = "<div id=\"downloadista-counter\" class=\"flex-container\" style=\"color: $pane_text_color; background-color: $pane_background_color; width: 100%; overflow: auto; border-width: " . $pane_border_size . "px; padding: 15px;\" data-url='" . esc_attr($file_url) . "' data-expiration='" . esc_attr($utc_expiration) . "'>
    ". $file_icon ."
    <div class=\"text-container\">
        <p> " . $file_desc . "</p>
        <p> ". $file_size_str . "</p>
        <p> ". $file_name . "</p>
    </div>
    <button id=\"startCounterButton\" class=\"right-button\" style=\"float: right; background-color: $button_background_color; color: $button_text_color; padding: 15px 32px; font-size: 16px; border: none; cursor: pointer;\">
            $button_text
    </button>
</div>
";
	return $download_box;
}

function downloadista_get_attachment_file_size($file_id) {
	// Get the file path from the attachment ID
	$file_path = get_attached_file($file_id);

	// Check if the file exists
	if (file_exists($file_path)) {
		// Return the file size
		return filesize($file_path);
	} else {
		// Handle the error if file doesn't exist
		return 0;
	}
}

function downloadista_get_file_name_by_attachment_id($file_id) {
	// Get the full file path from the attachment ID
	$file_path = get_attached_file($file_id);

	// Use basename() to extract the file name from the path
	$file_name = basename($file_path);

	return $file_name;
}

function downloadista_handle_download_request() {
	if (!empty($_GET['download_key'])) {
		$download_key = $_GET['download_key'];

		global $wpdb;
		$table_name = $wpdb->prefix . 'downloadista_links';
		$link = $wpdb->get_row($wpdb->prepare(
			"SELECT * FROM $table_name WHERE download_key = %s",
			$download_key
		));

		if ($link && new DateTime() < new DateTime($link->expiration)) {
			// Get the real file path from the URL
			$file_path = get_attached_file($link->attachment_id);  // Ensure this returns the server path to the file
			if (file_exists($file_path)) {
				// Clean all buffering to avoid memory issues
				while (ob_get_level()) {
					ob_end_clean();
				}

				// Set headers to serve the file as a download
				header('Content-Description: File Transfer');
				header('Content-Type: application/octet-stream');
				header('Content-Disposition: attachment; filename="' . basename($file_path) . '"');
				header('Expires: 0');
				header('Cache-Control: must-revalidate');
				header('Pragma: public');
				header('Content-Length: ' . filesize($file_path));

				// Read the file and output its contents
				readfile($file_path);
				exit;
			} else {
				downloadista_redirect_404();
			}
		} else {
			downloadista_redirect_404();
		}
	}
}

function downloadista_rewrite_tag_rule() {
	add_rewrite_rule('^downloadista-page/?$', 'index.php?downloadista_page=1', 'top');
	add_rewrite_tag('%downloadista-page%', '([^&]+)');
}

function downloadista_shortcodes_init() {
    add_shortcode( 'downloadista', 'downloadista_shortcode_handler' );
}

function downloadista_add_attachment_id_column($columns) {
	$columns['attachment_id'] = 'ID'; // Adds a new column for IDs
	return $columns;
}

function downloadista_show_attachment_id_column($column_name, $post_id) {
	if ('attachment_id' === $column_name) {
		echo $post_id; // Display the attachment ID
	}
}

function downloadista_enqueue_scripts() {
    wp_enqueue_script('downloadista-counter-js', plugin_dir_url(__FILE__) . 'public/js/download-counter.js', array('jquery'), '1.0', true);

    $options = get_option('downloadista_options', array('counter_pre_text' => '', 'counter_post_text' => '', 'url_text' => '', 'url_name' => ''));

    // Localize script for passing PHP variables to JavaScript
    wp_localize_script('downloadista-counter-js', 'downloadistaSettings', array(
        'counterPreText' => !empty($options['counter_pre_text']) ? $options['counter_pre_text'] : 'counter_pre_text',
        'counterPostText' => !empty($options['counter_post_text']) ? $options['counter_post_text'] : 'counter_post_text',
        'urlText' => !empty($options['url_text']) ? $options['url_text'] : '',
        'urlName' => !empty($options['url_name']) ? $options['url_name'] : '',
        'counterValue' => !empty($options['counter_value']) ? $options['counter_value'] : '15',
        'linkExpiredMessage' => !empty($options['link_expired_message']) ? $options['link_expired_message'] : 'This download link has expired, please refresh the page.'
    ));
}

function downloadista_add_admin_menu() {
    add_menu_page(
        'Downloadista Settings',     // Page title
        'Downloadista Settings',       // Menu title
        'manage_options',         // Capability
        'downloadista_settings',    // Menu slug
        'downloadista_settings_page'// Function to display the settings page
    );
}

function downloadista_settings_page() {
    ?>
    <div class="wrap">
        <h2>Downloadista Plugin Settings</h2>
        <form method="post" action="options.php">
            <?php
            settings_fields('downloadista_settings');
            do_settings_sections('downloadista_settings');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

function downloadista_settings_init() {
    // Register a new setting for "my_counter" page.
    register_setting('downloadista_settings', 'downloadista_options');

    // Add a new section to a settings page.
    add_settings_section(
        'downloadista_section',
        'Customize Downloadista Plugin',
        'downloadista_section_callback',
        'downloadista_settings'
    );

    add_settings_field(
        'downloadista_button_text',
        'Download Button Text',
        'downloadista_button_text_render',
        'downloadista_settings',
        'downloadista_section'
    );

    add_settings_field(
        'downloadista_button_text_color',
        'Download Button Text Color',
        'downloadista_button_text_color_render',
        'downloadista_settings',
        'downloadista_section'
    );

    add_settings_field(
        'downloadista_button_background_color',
        'Download Button Background Color',
        'downloadista_button_background_color_render',
        'downloadista_settings',
        'downloadista_section'
    );

    add_settings_field(
        'downloadista_pane_text_color',
        'Pane Text Color',
        'downloadista_pane_text_color_render',
        'downloadista_settings',
        'downloadista_section'
    );

    add_settings_field(
        'downloadista_pane_background_color',
        'Pane Background Color',
        'downloadista_pane_background_color_render',
        'downloadista_settings',
        'downloadista_section'
    );

    add_settings_field(
        'downloadista_pane_border_size',
        'Pane border size, px',
        'downloadista_pane_border_size_render',
        'downloadista_settings',
        'downloadista_section'
    );

    add_settings_field(
        'downloadista_url_text',
        'URL Text',
        'downloadista_url_text_render',
        'downloadista_settings',
        'downloadista_section'
    );

    add_settings_field(
        'downloadista_url_name',
        'URL Name',
        'downloadista_url_name_render',
        'downloadista_settings',
        'downloadista_section'
    );

    add_settings_field(
        'downloadista_counter_pre_text',
        'Counter Pre Text',
        'downloadista_counter_pre_text_render',
        'downloadista_settings',
        'downloadista_section'
    );

    add_settings_field(
        'downloadista_counter_post_text',
        'Counter Post Text',
        'downloadista_counter_post_text_render',
        'downloadista_settings',
        'downloadista_section'
    );

	add_settings_field(
		'downloadista_counter_value',
		'Counter Timer, seconds',
		'downloadista_counter_value_render',
		'downloadista_settings',
		'downloadista_section'
	);

	add_settings_field(
		'downloadista_link_expiration_value',
		'Link Expiration, minutes',
		'downloadista_link_expiration_render',
		'downloadista_settings',
		'downloadista_section'
	);

	add_settings_field(
		'downloadista_link_expired_message',
		'Link Expiration, minutes',
		'downloadista_link_expired_message_render',
		'downloadista_settings',
		'downloadista_section'
	);

	add_settings_field(
		'downloadista_icon_color',
		'Icon color',
		'downloadista_icon_color_render',
		'downloadista_settings',
		'downloadista_section'
	);
}

function downloadista_section_callback() {
    echo 'Customize the appearance and text. Example of the short code: [downloadista file_id=123 file_name="Cat picture"]';
}

function downloadista_button_text_render() {
    $options = get_option('downloadista_options');
    ?>
    <input type='text' name='downloadista_options[button_text]' value='<?php echo $options['button_text']; ?>'>
    <?php
}

function downloadista_button_text_color_render() {
    $options = get_option('downloadista_options');
    ?>
    <input type='text' name='downloadista_options[button_text_color]' value='<?php echo $options['button_text_color']; ?>'>
    <?php
}

function downloadista_button_background_color_render() {
    $options = get_option('downloadista_options');
    ?>
    <input type='text' name='downloadista_options[button_background_color]' value='<?php echo $options['button_background_color']; ?>'>
    <?php
}

function downloadista_pane_text_color_render() {
    $options = get_option('downloadista_options');
    ?>
    <input type='text' name='downloadista_options[pane_text_color]' value='<?php echo $options['pane_text_color']; ?>'>
    <?php
}

function downloadista_pane_background_color_render() {
    $options = get_option('downloadista_options');
    ?>
    <input type='text' name='downloadista_options[pane_background_color]' value='<?php echo $options['pane_background_color']; ?>'>
    <?php
}

function downloadista_pane_border_size_render() {
    $options = get_option('downloadista_options');
    ?>
    <input type='text' name='downloadista_options[pane_border_size]' value='<?php echo $options['pane_border_size']; ?>'>
    <?php
}

function downloadista_url_text_render() {
    $options = get_option('downloadista_options');
    ?>
    <input type='text' name='downloadista_options[url_text]' value='<?php echo $options['url_text']; ?>'>
    <?php
}

function downloadista_url_name_render() {
    $options = get_option('downloadista_options');
    ?>
    <input type='text' name='downloadista_options[url_name]' value='<?php echo $options['url_name']; ?>'>
    <?php
}

function downloadista_counter_pre_text_render() {
    $options = get_option('downloadista_options');
    ?>
    <input type='text' name='downloadista_options[counter_pre_text]' value='<?php echo $options['counter_pre_text']; ?>'>
    <?php
}

function downloadista_counter_post_text_render() {
    $options = get_option('downloadista_options');
    ?>
    <input type='text' name='downloadista_options[counter_post_text]' value='<?php echo $options['counter_post_text']; ?>'>
    <?php
}

function downloadista_counter_value_render() {
	$options = get_option('downloadista_options');
	?>
    <input type='text' name='downloadista_options[counter_value]' value='<?php echo $options['counter_value']; ?>'>
	<?php
}

function downloadista_link_expiration_render() {
	$options = get_option('downloadista_options');
	?>
    <input type='text' name='downloadista_options[link_expiration]' value='<?php echo $options['link_expiration']; ?>'>
	<?php
}

function downloadista_link_expired_message_render() {
	$options = get_option('downloadista_options');
	?>
    <input type='text' name='downloadista_options[link_expired_message]' value='<?php echo $options['link_expired_message']; ?>'>
	<?php
}

function downloadista_icon_color_render() {
	$options = get_option('downloadista_options');
	?>
    <input type='text' name='downloadista_options[icon_color]' value='<?php echo $options['icon_color']; ?>'>
	<?php
}

function downloadista_redirect_404() {
	status_header( 404 );
	nocache_headers();
	include( get_query_template('404') );
	exit;
}

run_wp_downloadista();
