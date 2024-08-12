<?php
/**
 * Plugin Name: GP Advanced Save and Continue
 * Description: Level up your Gravity Forms Save & Continue functionality with auto-saving, auto-loading, and draft management.
 * Plugin URI: https://gravitywiz.com/documentation/gravity-forms-advanced-save-continue/
 * Version: 1.0.22
 * Author: Gravity Wiz
 * Author URI: https://gravitywiz.com/
 * License: GPL2
 * Perk: True
 * Text Domain: gp-advanced-save-and-continue
 * Domain Path: /languages
 */

define( 'GP_ADVANCED_SAVE_AND_CONTINUE_VERSION', '1.0.22' );
define( 'GP_ADVANCED_SAVE_AND_CONTINUE_SLUG', 'gp-advanced-save-and-continue' );

require plugin_dir_path( __FILE__ ) . 'includes/autoload.php';

$GLOBALS['gp_advanced_save_and_continue_bootstrap'] = new \GP_Advanced_Save_And_Continue\GP_Bootstrap( 'class-gp-advanced-save-and-continue.php', __FILE__ );
