<?php

namespace GFPDF\Plugins\Watermark\Plugins;

use GV\View;

/**
 * @package     Gravity PDF Watermark
 * @copyright   Copyright (c) 2023, Blue Liquid Designs
 * @license     http://opensource.org/licenses/gpl-2.0.php GNU Public License
 */

/* Exit if accessed directly */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * @since 2.0
 */
class PdfForGravityView {

	public function init() {
		add_filter( 'gfpdf_gv_pdf_metabox_settings', [ $this, 'register_settings' ] );
		add_filter( 'gfpdf_gv_pdf_settings', [ $this, 'sync_watermark_settings' ], 10, 2 );
	}

	/**
	 * Register the Watermark settings in the Single Entry PDF Meta Box in GravityView
	 *
	 * @param array $settings
	 *
	 * @return array
	 *
	 * @since 2.0
	 */
	public function register_settings( $settings ) {
		$options = \GPDFAPI::get_options_class();

		$fonts = $this->flatten_options_groups( $options->get_installed_fonts() );

		$settings['gpdf_watermark'] = [
			'label'             => esc_html__( 'Enable Watermark?', 'gravity-pdf-watermark' ),
			'type'              => 'checkbox',
			'group'             => 'pdf',
			'value'             => '1',
			'desc'              => esc_html__( 'Display a text or image watermark in the PDF.', 'gravity-pdf-watermark' ),
			'show_in_shortcode' => false,
		];

		$settings['gpdf_watermark_background_image'] = [
			'label'             => esc_html__( 'Image Watermark', 'gravity-pdf-watermark' ),
			'type'              => 'upload',
			'group'             => 'pdf',
			'value'             => '',
			'requires'          => 'gpdf_watermark',
			'desc'              => esc_html__( 'For the best results, ensure the image is the same dimensions as the Paper Size.', 'gravity-pdf-watermark' ),
			'show_in_shortcode' => false,
		];

		$settings['gpdf_watermark_text'] = [
			'label'             => esc_html__( 'Text Watermark', 'gravity-pdf-watermark' ),
			'type'              => 'text',
			'group'             => 'pdf',
			'value'             => '',
			'requires'          => 'gpdf_watermark',
			'desc'              => esc_html__( 'Any UTF-8 character can be used, provided the Watermark Font supports it.', 'gravity-pdf-watermark' ),
			'show_in_shortcode' => false,
			'class'             => 'widefat',
		];

		$settings['gpdf_watermark_font'] = [
			'label'             => esc_html__( 'Watermark Font', 'gravity-pdf-watermark' ),
			'type'              => 'select',
			'tooltip'           => esc_html__( 'Select the font to use for the Text Watermark. You can install additional fonts by navigating to Forms -> Settings -> PDF -> Tools in your admin area.', 'gravity-pdf-watermark' ),
			'group'             => 'pdf',
			'options'           => $fonts,
			'value'             => $options->get_option( 'default_font' ),
			'show_in_shortcode' => false,
			'requires'          => 'gpdf_watermark',
		];

		$settings['gpdf_watermark_opacity'] = [
			'label'             => __( 'Opacity', 'gravity-pdf-watermark' ),
			'type'              => 'number',
			'group'             => 'pdf',
			'tooltip'           => esc_html__( 'Select a value between 0 and 100 to control the opacity of the watermark. 0 = completely transparent; 100 = not transparent.', 'gravity-pdf-watermark' ),
			'value'             => '20',
			'min'               => 1,
			'max'               => 100,
			'class'             => 'small-text',
			'show_in_shortcode' => false,
			'requires'          => 'gpdf_watermark',
		];

		return $settings;
	}

	/**
	 * Check for Watermark View settings and set in PDF settings
	 *
	 * @param array $settings
	 * @param View  $view
	 *
	 * @return array
	 */
	public function sync_watermark_settings( $settings, View $view ) {
		$view_settings = $view->settings;

		$settings['watermark_toggle']    = $view_settings->get( 'gpdf_watermark', false );
		$settings['watermark_image']     = $view_settings->get( 'gpdf_watermark_background_image', false );
		$settings['watermark_text']      = $view_settings->get( 'gpdf_watermark_text', false );
		$settings['watermark_text_font'] = $view_settings->get( 'gpdf_watermark_font', false );
		$settings['watermark_opacity']   = (float) $view_settings->get( 'gpdf_watermark_opacity', 20 );

		return $settings;
	}

	/**
	 * Convert a multi-dimensional array to a single-dimension array
	 *
	 * @param array $array A multi-dimensional array that goes two levels deep
	 *
	 * @return array A single-dimensional array
	 *
	 * @since 2.0
	 */
	protected function flatten_options_groups( $array ) {
		$new_array = [];
		foreach ( $array as $group ) {
			$new_array += $group;
		}

		return $new_array;
	}
}
