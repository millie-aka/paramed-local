<?php

/**
 * Utility class to manage draft token persisting logic for unauthenticated users.
 */

namespace GP_Advanced_Save_And_Continue;

class Unauthenticated_User extends Token_Management {
	const _RESUME_TOKENS_COOKIE_NAME = 'gpasc-save-and-continue-resume-tokens';

	/**
	 * Sets a list of resume tokens in an HTTP cookie and updates the $_COOKIE superglobal.
	 * `setcookie()` does not update the $_COOKIE superglobal, so we need to do that manually
	 * so that other code within the same request can access the updated cookie value.
	 */
	public static function update_cookie( $tokens, $form_id ) {
		$encoded_tokens    = json_encode( $tokens );
		$expiration_length = 60 * 60 * 24 * 30; // thirty days
		/**
		 * Controls how long the draft token cookie is valid for in seconds.
		 *
		 * ** Note ** the draft token cookie is only applicable for non-authenticated users.
		 *
		 * @param $expiration_length int The number of seconds the cookie should be valid for. The default is 2592000 seconds (30 days).
		 *
		 * @since 1.0-beta-1
		 */
		$expiration_length = apply_filters( 'gpasc_drafts_cookie_expiration_time', $expiration_length );
		setcookie(
			self::_RESUME_TOKENS_COOKIE_NAME . '-' . $form_id,
			$encoded_tokens,
			time() + $expiration_length,
			'/'
		);

		$_COOKIE[ self::_RESUME_TOKENS_COOKIE_NAME . '-' . $form_id ] = $encoded_tokens;
	}


	/**
	 * Deletes a cookie from $_COOKIE and calls setcookie() to delete the cookie.
	 *
	 * @param $key string The name of the cookie to delete.
	 */
	public static function delete_cookie( $key ) {
		unset( $_COOKIE[ $key ] );
		setcookie( $key, null, -1, '/' );
	}

	public static function save_resume_token( $resume_token, $form, $form_path ) {
		$tokens = self::get_form_resume_tokens( $form['id'] );

		$token_already_exists = false;

		foreach ( $tokens as &$token ) {
			if ( $token['token'] === $resume_token ) {
				$token_already_exists = true;
				$token['updated_at']  = time();
				break;
			}
		}

		if ( ! $token_already_exists ) {
			$tokens[] = array(
				'token'      => $resume_token,
				'updated_at' => time(),
				'form_id'    => $form['id'],
				'form_path'  => $form_path,
			);
		}

		self::update_cookie( $tokens, $form['id'] );
	}
	public static function delete_resume_token( $resume_token, $form ) {
		if ( empty( $resume_token ) ) {
			return;
		}

		$tokens = self::get_form_resume_tokens( $form['id'] );

		$tokens = array_filter(
			$tokens,
			function( $token_data ) use ( $resume_token ) {
				return $token_data['token'] !== $resume_token;
			}
		);

		self::update_cookie( $tokens, $form['id'] );
	}

	/**
	 * Given a form ID, returns all resume tokens for that form.
	 */
	public static function get_form_resume_tokens( $form_id ) {
		$tokens_cookie = rgar( $_COOKIE, self::_RESUME_TOKENS_COOKIE_NAME . '-' . $form_id );

		$tokens = json_decode( stripslashes( $tokens_cookie ), true );

		if ( ! $tokens ) {
			$tokens = array();
		}

		/**
		 * Filters the resume tokens for a given form.
		 *
		 * This filter is applied to the resume tokens for a given form before they are returned.
		 *
		 * @since 1.0.2
		 *
		 * @param array $tokens  The resume tokens for a given form.
		 * @param int   $form_id The ID of the form.
		 * @param int   $user_id The ID of the user.
		 */
		$tokens = gf_apply_filters( array( 'gpasc_form_resume_tokens', $form_id ), $tokens, $form_id, null );

		return $tokens;
	}


	/**
	 * Reads the resume token HTTP cookie and attempts to serialize the JSON
	 * into a PHP array. If that fails, or the cookie was empty, then returns
	 * an empty array.
	 */
	public static function get_all_resume_tokens() {
		$tokens = array();

		foreach ( $_COOKIE as $key => $value ) {
			if ( strpos( $key, self::_RESUME_TOKENS_COOKIE_NAME ) === 0 && $value !== null ) {
				$next_tokens = json_decode( stripslashes( $value ), true );
				if ( ! is_null( $next_tokens ) ) {
					$tokens = array_merge( $tokens, $next_tokens );
				}
			}
		}

		// filter out any expired tokens.
		return Utils::filter_expired_resume_tokens( $tokens );
	}

	/**
	 * Retrieve the most recently saved/updated save and continue token from the the
	 * perk's cookie if it exists.
	 */
	public static function get_most_recent_resume_token( $form_id ) {
		$tokens = self::get_form_resume_tokens( $form_id );

		$token_data = array_reduce( $tokens, function ( $prev_token_data, $curr_token_data ) use ( $form_id ) {
			// the first token we find for a given $form_id will be the most recent by default.
			if ( empty( $prev_token_data ) ) {
				return $curr_token_data;
			}

			$prev_updated_at = rgar( $prev_token_data, 'updated_at' );
			$curr_updated_at = rgar( $curr_token_data, 'updated_at' );

			if ( $curr_updated_at > $prev_updated_at ) {
				return $curr_token_data;
			};

			return $prev_token_data;
		}, null );

		$resume_token = rgar( $token_data, 'token' );

		return $resume_token;
	}

	public static function delete_resume_token_cookies() {
		foreach ( $_COOKIE as $key => $value ) {
			if ( strpos( $key, self::_RESUME_TOKENS_COOKIE_NAME ) === 0 ) {
				self::delete_cookie( $key );
			}
		}
	}

	public static function migrate_single_cookie_to_form_specific_cookies() {
		$token_cookie = rgar( $_COOKIE, self::_RESUME_TOKENS_COOKIE_NAME );

		if ( ! $token_cookie ) {
			return;
		}

		// a map of form IDs to their respective resume tokens.
		$new_cookies = array();

		$tokens = json_decode( stripslashes( $token_cookie ), true );

		foreach ( $tokens as $token ) {
			$form_id = rgar( $token, 'form_id' );

			if ( ! $form_id ) {
				continue;
			}

			if ( ! array_key_exists( $form_id, $new_cookies ) ) {
				$new_cookies[ $form_id ] = array();
			}

			$new_cookies[ $form_id ][] = $token;
		}

		foreach ( $new_cookies as $form_id => $tokens ) {
			self::update_cookie( $tokens, $form_id );
		}

		self::delete_cookie( self::_RESUME_TOKENS_COOKIE_NAME );
	}
}
