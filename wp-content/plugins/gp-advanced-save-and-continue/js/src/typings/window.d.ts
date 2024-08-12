import type { GPAdvancedSaveAndContinue, createGPAdvancedSaveAndContinue } from '../gp-advanced-save-and-continue';
import type { DraftManagement } from '../draft-management';
/**
 * Augment Window typings and add in properties provided by Gravity Forms, WordPress, etc.
 */

declare global {

	type GPASCKey = `GPASC_${number}`

	interface Window {
	    jQuery: JQueryStatic;
	    GPPS_AJAX_URL: string;
	    GPPS_NONCE: string;
		GPPA: {
			AJAXURL: string;
		};
		GPASC_DRAFT_MANAGEMENT: {
			strings: {
				confirm_delete_draft: string;
			};
		};
		createGPAdvancedSaveAndContinue: typeof createGPAdvancedSaveAndContinue;
		GPAdvancedSaveAndContinue: typeof GPAdvancedSaveAndContinue;
		GPAdvancedSaveAndContinueDraftManagement: typeof DraftManagement;
		gform: {
			doAction: (action: string, ...args: any[]) => void;
			applyFilters: (action: string, ...args: any[]) => any;
		}

		[key: GPASCKey]: GPAdvancedSaveAndContinue;

		gfMultiFileUploader?: {
			uploaders: { [name: string]: any }
			setup: Function
			toggleDisabled: Function
		}

		tinymce?: {
			get: Function
		}
	}
}
