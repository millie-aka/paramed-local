<?php

/**
 * Utility class to house functions relating to GPEB compatibility.
 */

namespace GP_Advanced_Save_And_Continue;

use GP_Entry_Blocks;

class GPEB_Compat {
	/**
	 * Checks to see if the context is the GP Entry Block Editor.
	 */
	public static function is_editing_entry( $form_id ) {
		if ( method_exists( 'GP_Entry_Blocks\GF_Queryer', 'attach_to_current_block' ) ) {
			$gpeb_queryer = GP_Entry_Blocks\GF_Queryer::attach_to_current_block();

			if ( $gpeb_queryer && $gpeb_queryer->is_edit_entry() && $gpeb_queryer->form_id == $form_id ) {
				return true;
			}
		}

		if ( function_exists( 'gp_entry_blocks' ) && property_exists( gp_entry_blocks(), 'block_edit_form' ) ) {
			return gp_entry_blocks()->block_edit_form->has_submitted_edited_entry();
		}

		return false;
	}
}
