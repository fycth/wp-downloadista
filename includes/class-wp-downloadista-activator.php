<?php

/**
 * Fired during plugin activation
 *
 * @link       https://serhiienko.se
 * @since      1.0.0
 *
 * @package    Wp_Downloadista
 * @subpackage Wp_Downloadista/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Wp_Downloadista
 * @subpackage Wp_Downloadista/includes
 * @author     Andrii Serhiienko <andrii@oslikas.com>
 */
class Wp_Downloadista_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'downloadista_links';
		$charset_collate = $wpdb->get_charset_collate();
		$sql = "CREATE TABLE IF NOT EXISTS $table_name (
        attachment_id INT NOT NULL PRIMARY KEY,
        file_url VARCHAR(255) NOT NULL,
        download_key CHAR(32) NOT NULL,
        expiration DATETIME NOT NULL
    ) $charset_collate;";
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}
}
