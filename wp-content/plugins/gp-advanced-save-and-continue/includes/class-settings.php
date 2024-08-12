<?php

/**
 * Helper class to house functions related to getting GPASC settings.
 */

namespace GP_Advanced_Save_And_Continue;

class Settings {
	public $slug;

	public $default_visitor_prompt_title;

	public $default_visitor_prompt_description;

	public $default_visitor_prompt_accept_button_label;

	public $default_visitor_prompt_decline_button_label;

	public function __construct() {
		$this->slug = GP_ADVANCED_SAVE_AND_CONTINUE_SLUG;

		$this->default_visitor_prompt_title                = __( 'Would you like to automatically save progress?', 'gp-advanced-save-and-continue' );
		$this->default_visitor_prompt_description          = __(
			'Select "Yes" to automatically save your progress as you work to your current browser session. Select "No" if on a shared device.',
			'gp-advanced-save-and-continue'
		);
		$this->default_visitor_prompt_accept_button_label  = __( 'Yes, save my progress', 'gp-advanced-save-and-continue' );
		$this->default_visitor_prompt_decline_button_label = __( 'No', 'gp-advanced-save-and-continue' );
	}

	/**
	 * Determines if Save and Continue is enabled for a given form.
	 */
	public function save_and_continue_enabled( $form ) {
		return rgars( $form, $this->slug . '/save_and_continue_enabled', false ) === '1';
	}

	/**
	 * determines if the form should have auto-load behavior enabled.
	 *
	 * @param array $form
	 *
	 * @return bool
	 */
	public function should_auto_load_draft( $form ) {
		if ( empty( $form ) ) {
			return false;
		}

		$should_auto_load = rgars( $form, $this->slug . '/auto_save_and_load_enabled', false ) === '1';

		/**
		 * Allows you to skip auto loading a draft entry for a given form or globally for all forms.
		 *
		 * @param bool $skip_auto_load whether to skip auto loading a draft entry. (default: false)
		 * @param array $form The current form.
		 *
		 * @since 1.0-beta-1.3
		 */
		return gf_apply_filters( array( 'gpasc_should_auto_load', rgar( $form, 'id' ) ), $should_auto_load, $form );
	}

	/**
	 * Determines if the form should have auto-save behavior enabled.
	 *
	 * @param array $form
	 *
	 * @return bool
	 */
	public function should_auto_save_draft( $form ) {
		$should_auto_save = $this->should_auto_load_draft( $form );

		/**
		 * Filter whether the form should auto-save.
		 *
		 * @param bool $should_auto_save Whether the form should auto-save. Defaults to the value of the "Enable Auto Save and Load" setting.
		 * @param array $form The current form.
		 *
		 * @since 1.0-beta-1
		 */
		$should_auto_save = gf_apply_filters( array( 'gpasc_should_auto_save', $form['id'] ), $should_auto_save, $form );

		return $should_auto_save;
	}

	/**
	 * Determines if the non-authorized visitor prompt should be shown for auto-loading.
	 */
	public function visitor_prompt_enabled( $form ) {
		$enabled = rgars( $form, $this->slug . '/auto_save_and_load_enabled', false ) === '1';

		/**
		 * Whether or not the visitor prompt should be displayed for this form.
		 *
		 * @param bool $enabled Whether or not the visitor prompt should be displayed.
		 * @param array $form The form object.
		 */
		return gf_apply_filters( array( 'gpasc_visitor_prompt_enabled', $form['id'] ), $enabled, $form );
	}

	/**
	 * Determines if the form should redirect to a new page or display an inline submission confirmation.
	 */
	public function should_show_inline_confirmation( $form ) {
		return rgars( $form, $this->slug . '/inline_save_and_continue_confirmation_enabled', '0' ) === '1';
	}

	public function visitor_prompt_title( $form ) {
		return rgars( $form, $this->slug . '/visitor_prompt_title', $this->default_visitor_prompt_title );
	}

	public function visitor_prompt_description( $form ) {
		return rgars( $form, $this->slug . '/visitor_prompt_description', $this->default_visitor_prompt_description );
	}

	public function visitor_prompt_accept_button_label( $form ) {
		return rgars( $form, $this->slug . '/visitor_prompt_accept_button_label', $this->default_visitor_prompt_accept_button_label );
	}

	public function visitor_prompt_decline_button_label( $form ) {
		return rgars( $form, $this->slug . '/visitor_prompt_decline_button_label', $this->default_visitor_prompt_decline_button_label );
	}

	public function hide_save_and_continue_link( $form ) {
		return ( rgars( $form, $this->slug . '/auto_save_and_load_enabled', '0' ) === '1' && rgars( $form, $this->slug . '/hide_save_and_continue_link', '0' ) === '1' );
	}

	public function draft_management_enabled( $form ) {
		return rgars( $form, $this->slug . '/draft_management_enabled', '0' ) === '1';
	}

	public function display_available_drafts_above_form( $form ) {
		return self::draft_management_enabled( $form ) && rgars( $form, $this->slug . '/display_available_drafts_above_form', '0' ) === '1';
	}
}
