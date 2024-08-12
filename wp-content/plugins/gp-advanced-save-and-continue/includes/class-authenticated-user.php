<?php

/**
 * Utility class to manage draft token persisting logic for authenticated users.
 */

namespace GP_Advanced_Save_And_Continue;

class Authenticated_User extends Token_Management {
	/**
	 * Creates or updates a save and continue resume token in the user meta table.
	 */
	public static function save_resume_token( $resume_token, $form, $form_path, $user_id = null ) {
		$meta = self::get_form_resume_tokens( $form['id'], $user_id );

		$meta_exists = false;

		// If the resume token has already been added to the db, then update the updated_at timestamp.
		foreach ( $meta as $idx => &$token_data ) {
			if ( $token_data['token'] === $resume_token ) {
				$meta_exists              = true;
				$token_data['updated_at'] = time();
				break;
			}
		}

		// If the resume token has not been added to the db, add it.
		if ( ! $meta_exists ) {
			$meta[] = array(
				'token'      => $resume_token,
				'updated_at' => time(),
				'form_path'  => $form_path,
			);
		}

		if ( empty( $user_id ) ) {
			$user_id = get_current_user_id();
		}

		update_user_meta( $user_id, self::get_token_meta_key( $form['id'] ), $meta );
	}

	public static function update_form_token_meta( $form_id, $meta, $user_id = null ) {
		if ( empty( $user_id ) ) {
			$user_id = get_current_user_id();
		}

		update_user_meta( $user_id, self::get_token_meta_key( $form_id ), $meta );
	}

	public static function delete_resume_token( $resume_token, $form, $user_id = null ) {
		if ( empty( $resume_token ) ) {
			return;
		}

		$meta = self::get_form_resume_tokens( $form['id'], $user_id );

		$meta = array_filter(
			$meta,
			function( $value ) use ( $resume_token ) {
				return $value['token'] !== $resume_token;
			}
		);

		self::update_form_token_meta( $form['id'], $meta, $user_id );

		return $meta;
	}

	/**
	 * Retrieve the resume tokens for a given form from the database user meta table.
	 * If $user_id is not passed, defaults to the current user's id.
	 */
	public static function get_form_resume_tokens( $form_id, $user_id = null ) {
		if ( empty( $user_id ) ) {
			$user_id = get_current_user_id();
		}

		$meta = get_user_meta( $user_id, self::get_token_meta_key( $form_id ), true );
		if ( empty( $meta ) ) {
			$meta = array();
		}

		// Filter any expired tokens and update the persisted tokens to remove any expired tokens from the database.
		$tokens = Utils::filter_expired_resume_tokens( $meta, function ( $valid_tokens ) use ( $form_id, $user_id ) {
			self::update_form_token_meta( $form_id, $valid_tokens, $user_id );
		} );

		/**
		 * Filters the resume tokens for a given form.
		 *
		 * This filter is applied to a user's resume tokens for a given form before they are returned.
		 *
		 * @since 1.0.2
		 *
		 * @param array $tokens  The resume tokens for a given form.
		 * @param int   $form_id The ID of the form.
		 * @param int   $user_id The ID of the user.
		 */
		$tokens = gf_apply_filters( array( 'gpasc_form_resume_tokens', $form_id ), $tokens, $form_id, $user_id );

		return $tokens;
	}

	/**
	 * Retrieve the most recently saved/updated save and continue token from the user meta table.
	 */
	public static function get_most_recent_resume_token( $form_id ) {
		$resume_token_meta = self::get_form_resume_tokens( $form_id );

		$most_recent_time_stamp = 0;
		$token                  = null;

		foreach ( $resume_token_meta as $key => $value ) {
			if ( $value['updated_at'] > $most_recent_time_stamp ) {
				$most_recent_time_stamp = $value['updated_at'];
				$token                  = $value['token'];
			}
		}

		return $token;
	}

	public static function get_all_resume_tokens() {
		global $wpdb;

		$user_id = get_current_user_id();

		/* Get all Advanced Save and Continue meta rows for the current user. */
		$query = $wpdb->prepare( "SELECT meta_key, meta_value FROM {$wpdb->usermeta} where user_id = %d AND meta_key LIKE %s;", $user_id, 'gpasc-resume-tokens-%' );
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$results = $wpdb->get_results( $query );

		$tokens = array();

		foreach ( $results as $result ) {

			// get the form id from the end of the meta key.
			$matches = array();
			preg_match( '/[0-9]+$/', $result->meta_key, $matches );
			$form_id = $matches[0];

			$form_tokens = unserialize( $result->meta_value );
			foreach ( $form_tokens as $form_token ) {
				$token            = $form_token;
				$token['form_id'] = $form_id;
				$tokens[]         = $token;
			}
		}

		return $tokens;
	}


	/**
	 * Get the meta key used to store the resume tokens for a given form.
	 */
	public static function get_token_meta_key( $form_id ) {
		return sprintf( 'gpasc-resume-tokens-%d', $form_id );
	}

}
