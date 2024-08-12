<?php

/**
 * Utility class to house functions shared across all plugin classes.
 */

namespace GP_Advanced_Save_And_Continue;

use GFFormsModel;
use GFAPI;

class Utils {
	/**
	 * Checks a resume token to see if it is expired.
	 *
	 * NOTE: We may want to consider improving perf here by accepting an array of resume tokens, making only
	 * a single query to the database and then returning an array of expired tokens.
	 *
	 * @param string $resume_token The resume token to check.
	 */
	public static function is_resume_token_expired( $resume_token ) {
		global $wpdb;

		$table = version_compare( GFFormsModel::get_database_version(), '2.3-dev-1', '<' ) ? GFFormsModel::get_incomplete_submissions_table_name() : GFFormsModel::get_draft_submissions_table_name();
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$sql = $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE uuid = %s", $resume_token );
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$count = $wpdb->get_var( $sql );

		return $count == '0';
	}

	/**
	 * Filters an array of resume token data to remove any expired tokens are returns any valid tokens.
	 *
	 * @param array $resume_token_data The resume token data to filter.
	 * @param Closure|null $update_resume_tokens A callback function to perform a side effect with the updated resume tokens.
	 */
	public static function filter_expired_resume_tokens( $resume_token_data, $update_resume_tokens = null ) {
		$valid_tokens = array();

		foreach ( $resume_token_data as $value ) {
			if ( ! self::is_resume_token_expired( $value['token'] ) ) {
				$valid_tokens[] = $value;
			}
		}

		if ( is_callable( $update_resume_tokens ) ) {
			$update_resume_tokens( $valid_tokens );
		}

		return $valid_tokens;
	}

}
