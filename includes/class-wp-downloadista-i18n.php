<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://serhiienko.se
 * @since      1.0.0
 *
 * @package    Wp_Downloadista
 * @subpackage Wp_Downloadista/includes
 */

/**
 * Define the internationalization functionality.
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @since      1.0.0
 * @package    Wp_Downloadista
 * @subpackage Wp_Downloadista/includes
 * @author     Andrii Serhiienko <andrii@oslikas.com>
 */
class Wp_Downloadista_i18n {


	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.0.0
	 */
	public function load_plugin_textdomain() {

		load_plugin_textdomain(
			'wp-downloadista',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}



}
