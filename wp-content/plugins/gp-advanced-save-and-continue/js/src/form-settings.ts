/* Polyfills */
import 'core-js/es/array/includes';
import 'core-js/es/object/assign';
import 'core-js/es/object/values';
import 'core-js/es/object/entries';

import copy from 'copy-to-clipboard';

/**
 * jQuery is commonly used in Perks since Gravity Forms relies heavily on jQuery. Especially in the Form Editor.
 *
 * The variable below will use jQuery that has already been loaded into window and map it to $. Note, $ will only be
 * accessible in this module. It will not add it to window.
 */
const $ = window!.jQuery;

function gpascIsCheckboxChecked(sel: string) {
	return $(sel).is(':checked');
}

function gpascAddManageSettingsLink(sel: string, anchor: string) {
	$(sel)
		.parent()
		.after(
			`<span class="gpasc-manage-settings-link"><a href="#${anchor}">Manage Settings</a></span>`
		);
}

function gpascRemoveManageSettingsLink(sel: string) {
	$(sel).parent().siblings('.gpasc-manage-settings-link').remove();
}

function gpascInitShortcodeCopyButton() {
	const copyButton = $('#gpasc-copy-shortcode-button');

	const setCopyButtonText = (text: string) => {
		$(
			'#gpasc-copy-shortcode-button > .gpasc-shortcode-copy-button-text'
		).text(text);
	};

	copyButton.on('click', async (event) => {
		event.preventDefault();
		try {
			const shortcode = $('#gpasc-copy-shortcode-text').val() || '';
			/**
			 * This is a fairly well supported API for adding text to a clipboard buffer.
			 * For a good reference on adding to a system clipboard, see: https://stackoverflow.com/a/30810322
			 * Using window.navigator.writeText() was the initial approach, but it isn't ideal as window.navigator
			 * is undefined if the content is not served from a secure (HTTPS or localhost).
			 */
			copy(String(shortcode));

			setCopyButtonText('Copied!');

			setTimeout(() => {
				setCopyButtonText('Copy Shortcode');
			}, 1000);
		} catch (err) {
			// eslint-disable-next-line no-console
			console.error(err);
		}
	});
}

function gpascFormSettings() {
	const inputConfigs = [
		{
			selector: '#_gform_setting_auto_save_and_load_enabled',
			anchor: 'gform-settings-section-auto-save-and-load-settings',
		},
		{
			selector: '#_gform_setting_draft_management_enabled',
			anchor: 'gform-settings-section-draft-management-settings',
		},
	];

	for (const { selector, anchor } of inputConfigs) {
		if (gpascIsCheckboxChecked(selector)) {
			gpascAddManageSettingsLink(selector, anchor);
		}

		$(selector).on('change', () => {
			if (gpascIsCheckboxChecked(selector)) {
				gpascAddManageSettingsLink(selector, anchor);
			} else {
				gpascRemoveManageSettingsLink(selector);
			}
		});
	}

	gpascInitShortcodeCopyButton();
}

gpascFormSettings();
