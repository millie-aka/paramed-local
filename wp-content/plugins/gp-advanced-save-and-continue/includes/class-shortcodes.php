<?php

namespace GP_Advanced_Save_And_Continue;

use GFAPI;
use GFCommon;
use GFFormDisplay;

class Shortcodes {
	protected $plugin_base_url = null;

	protected $plugin_version = null;

	public function __construct( $plugin_base_url, $plugin_version ) {
		$this->plugin_base_url = $plugin_base_url;
		$this->plugin_version  = $plugin_version;

		add_shortcode( 'gpasc_drafts', array( $this, 'shortcode_gpasc_drafts' ) );
	}

	public function can_view_any_entry() {
		return GFCommon::current_user_can_any( 'gravityforms_view_entries' );
	}

	public function shortcode_gpasc_drafts( $attrs ) {
		$current_user_id = get_current_user_id();

		// make the attributes filterable
		$attrs = shortcode_atts( array(
			'form_id'   => null,
			'id'        => null,
			'form_path' => null,
			'title'     => null,
			'user_id'   => $current_user_id,
		), $attrs, 'gpasc_drafts' );

		// use the "id" param as an alias for "form_id" like Gravity Forms does.
		$form_id   = rgar( $attrs, 'form_id', rgar( $attrs, 'id' ) );
		$form_path = $attrs['form_path'];
		$title     = $attrs['title'];
		$user_id   = $attrs['user_id'];

		// if the user is not an admin or has access to gravity forms entries views, do not allow them to view other user's drafts.
		if ( $user_id != $current_user_id && ! $this->can_view_any_entry() ) {
			$user_id = $current_user_id;
		}

		if ( ! $form_id ) {
			return '';
		}

		$form = GFAPI::get_form( $form_id );

		require_once GFCommon::get_base_path() . '/form_display.php';

		wp_enqueue_script(
			'gp-advanced-save-and-continue-draft-management',
			$this->plugin_base_url . '/js/built/gp-advanced-save-and-continue-draft-management.js',
			array( 'jquery' ),
			$this->plugin_version,
			false
		);

		gp_advanced_save_and_continue()->localize_draft_management_scripts();

		$ajax_url = esc_js( admin_url( 'admin-ajax.php' ) );
		$nonce    = esc_js( wp_create_nonce( gp_advanced_save_and_continue()->get_slug() ) );
		$output   = gp_advanced_save_and_continue()->generate_draft_tokens_markup( $form_id, $form_path, $title, $user_id );

		$window_prop = ! $user_id ? "GPASCDraftManagement_{$form_id}" : "GPASCDraftManagement_{$form_id}_{$user_id}";
		$user_id     = ! $user_id ? 'null' : $user_id;

		$output .= GFCommon::get_inline_script_tag( "jQuery(function() {
			window['{$window_prop}'] = new GPAdvancedSaveAndContinueDraftManagement( {
				formId: {$form_id},
				ajaxUrl: '{$ajax_url}',
				nonce: '{$nonce}',
				userId: {$user_id},
		    });
	    });" );

		return $output;
	}
}
