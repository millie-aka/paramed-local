<?php
/**
 * Plugin Name: GP Limit Dates
 * Description: Limit which days are selectable for your Gravity Forms Date Picker fields.
 * Plugin URI: https://gravitywiz.com/documentation/gravity-forms-limit-dates/
 * Version: 1.1.26
 * Author: Gravity Wiz
 * Author URI: http://gravitywiz.com/
 * License: GPL2
 * Perk: True
 * Update URI: https://gravitywiz.com/updates/gp-limit-dates
 * Text Domain: gp-limit-dates
 * Domain Path: /languages
 */

define( 'GP_LIMIT_DATES_VERSION', '1.1.26' );

require_once plugin_dir_path( __FILE__ ) . '/includes/class-gp-bootstrap.php';

$gp_limit_dates_bootstrap = new GP_Bootstrap( 'class-gp-limit-dates.php', __FILE__ );
