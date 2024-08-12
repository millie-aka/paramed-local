<?php

/**
 * Abstract class defining a Token Management interface for use with GPASC.
 *
 * This should be extended and used anytime draft tokens need to be persisted in a new way.
 * (eg authenticated user tokens are stored in the database whereas unauthenticated users
 * tokens are stored in browser cookies.)
 */

namespace GP_Advanced_Save_And_Continue;

abstract class Token_Management {
	/***
	 * Save a resume token to the user meta table or HTTP cookie depending on the user's
	 * authentication status.
	 */
	abstract public static function save_resume_token( $resume_token, $form, $form_path );

	/**
	 * Delete a resume token from the user meta table or HTTP cookie depending on the user's
	 * authentication status.
	 */
	abstract public static function delete_resume_token( $resume_token, $form );

	/**
	 * Get the resume tokens associated to a user (in user meta table or HTTP cookie) for
	 * a given form id.
	 */
	abstract public static function get_form_resume_tokens( $form_id );

	/**
	 * Get the most recent resume token for a given form id.
	 */
	abstract public static function get_most_recent_resume_token( $form_id );

	/**
	 *
	 * Get all resume tokens for the current user.
	 */
	abstract public static function get_all_resume_tokens();
}
