<?php

/**
 * Plugin Name:     Gravity PDF Watermark
 * Plugin URI:      https://gravitypdf.com/shop/watermark-add-on/
 * Description:     Easily add a text or image watermark to any Gravity PDF-generated document.
 * Author:          Blue Liquid Designs
 * Author URI:      https://blueliquiddesigns.com.au
 * Update URI:      https://gravitypdf.com
 * Text Domain:     gravity-pdf-watermark
 * Domain Path:     /languages
 * Version:         2.0.0
 * Requires PHP:    7.3
 */

/**
 * @package     Gravity PDF Watermark
 * @copyright   Copyright (c) 2023, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'GFPDF_WATERMARK_FILE', __FILE__ );
define( 'GFPDF_WATERMARK_VERSION', '2.0.0' );

/**
 * Class GPDF_Watermark_Checks
 *
 * @since 1.0
 */
class GPDF_Watermark_Checks {

	/**
	 * Holds any blocker error messages stopping plugin running
	 *
	 * @var array
	 *
	 * @since 1.0
	 */
	private $notices = [];

	/**
	 * @var string
	 *
	 * @since 1.0
	 */
	private $required_gravitypdf_version = '6.0.0';

	/**
	 * Run our pre-checks and if it passes bootstrap the plugin
	 *
	 * @return void
	 *
	 * @since 1.0
	 */
	public function init() {

		/* Test the minimum version requirements are met */
		$this->check_gravitypdf_version();

		/* Check if any errors were thrown, enqueue them and exit early */
		if ( count( $this->notices ) > 0 ) {
			add_action( 'admin_notices', [ $this, 'display_notices' ] );

			return null;
		}

		add_action(
			'gfpdf_fully_loaded',
			function() {
				require_once __DIR__ . '/src/bootstrap.php';
			}
		);
	}

	/**
	 * Check if the current version of Gravity PDF is compatible with this add-on
	 *
	 * @return bool
	 *
	 * @since 1.0
	 */
	public function check_gravitypdf_version() {

		/* Check if the Gravity PDF Minimum version requirements are met */
		if ( defined( 'PDF_EXTENDED_VERSION' ) &&
			 version_compare( PDF_EXTENDED_VERSION, $this->required_gravitypdf_version, '>=' )
		) {
			return true;
		}

		/* Throw error */
		$this->notices[] = sprintf( esc_html__( 'Gravity PDF Version %s or higher is required to use this add-on. Please upgrade Gravity PDF to the latest version.', 'gravity-pdf-watermark' ), $this->required_gravitypdf_version );
	}

	/**
	 * Helper function to easily display error messages
	 *
	 * @return void
	 *
	 * @since 1.0
	 */
	public function display_notices() {
		?>
		<div class="error">
			<p>
				<strong><?php esc_html_e( 'Gravity PDF Watermark Installation Problem', 'gravity-pdf-watermark' ); ?></strong>
			</p>

			<p><?php esc_html_e( 'The minimum requirements for the Gravity PDF Watermark plugin have not been met. Please fix the issue(s) below to continue:', 'gravity-pdf-watermark' ); ?></p>
			<ul style="padding-bottom: 0.5em">
				<?php foreach ( $this->notices as $notice ): ?>
					<li style="padding-left: 20px;list-style: inside"><?php echo wp_kses_post( $notice ); ?></li>
				<?php endforeach; ?>
			</ul>
		</div>
		<?php
	}
}

/* Initialise the software */
add_action(
	'plugins_loaded',
	function() {
		$gravitypdf_watermark = new GPDF_Watermark_Checks();
		$gravitypdf_watermark->init();
	}
);
