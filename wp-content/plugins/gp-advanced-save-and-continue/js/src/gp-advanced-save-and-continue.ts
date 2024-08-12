import tingle from 'tingle.js';

interface GPASCSettings {
	autoSaveEnabled: boolean;
	nonAuthedVisitorPromptEnabled: boolean;
	visitorPromptTitle: string;
	visitorPromptDescription: string;
	visitorPromptAcceptButtonLabel: string;
	visitorPromptDeclineButtonLabel: string;
}

interface GPAdvancedSaveAndContinueOpts {
	formId: number;
	ajaxUrl: string;
	nonce: string;
	settings: GPASCSettings;
	shouldDeferAutosaveInit: boolean;
	isUserLoggedIn: boolean;
}

interface GPASCSaveDraftResponse {
	resume_token?: string;
}

const $ = window.jQuery;

export function createGPAdvancedSaveAndContinue(
	opts: GPAdvancedSaveAndContinueOpts
) {
	const instanceKey: GPASCKey = `GPASC_${opts.formId}`;

	// avoid instantiating multiple instances during AJAX enabled page changes.
	if (window[instanceKey]) {
		return;
	}

	const instance = new GPAdvancedSaveAndContinue(opts);
	window[instanceKey] = instance;
	/**
	 * Perform an action or side effect after a form's GPAdvancedSaveAndContinue instance has been initialized.
	 *
	 * @since 1.0-beta-1.3
	 *
	 * @param {number}                    formId   The ID of the form.
	 * @param {GPAdvancedSaveAndContinue} instance The instance of GPAdvancedSaveAndContinue that was initialized.
	 */
	window.gform.doAction('gpasc_js_init', opts.formId, instance);

	return instance;
}

export class GPAdvancedSaveAndContinue {
	public static userShouldAutoSaveSettingName: string =
		'gpasc-user-should-auto-save';

	public formId: number;

	public ajaxUrl: string;

	public nonce: string;

	private $form: JQuery<HTMLFormElement>;

	private saveLoopStarted = false;

	// A boolean to indicate whether or not the form has unsaved data. The "save loop" utilizes
	// this to determine if it should send a save request.
	private shouldSave = false;

	public isUserLoggedIn: boolean;

	public settings: GPASCSettings;

	private autoSaveRequest: JQuery.jqXHR | null = null;

	constructor(opts: GPAdvancedSaveAndContinueOpts) {
		this.formId = opts.formId;
		this.ajaxUrl = opts.ajaxUrl;
		this.nonce = opts.nonce;
		this.isUserLoggedIn = opts.isUserLoggedIn;
		this.settings = opts.settings;
		this.isUserLoggedIn = opts.isUserLoggedIn;

		this.$form = $('#gform_' + opts.formId);

		if (!opts.shouldDeferAutosaveInit) {
			this.enable();
		}

		this.addHiddenNonceField();
		this.removeQueryParam('gpasc_new_draft');

		if (
			// @ts-ignore
			typeof window['GPASCDraftManagement_' + this.formId] === 'undefined'
		) {
			window[
				// @ts-ignore
				'GPASCDraftManagement_' + this.formId
			] = new window.GPAdvancedSaveAndContinueDraftManagement({
				formId: this.formId,
				ajaxUrl: this.ajaxUrl,
				nonce: this.nonce,
			});
		}
	}

	/**
	 * Enables the autosave functionality.
	 */
	public enable = () => {
		this.startSaveLoop();
		this.addEventListeners();
		this.maybeShowAutoSaveConfirmationModal();
	};

	/**
	 * Disables the auto save functionality.
	 *
	 * @param {Object}  opts
	 * @param {boolean} opts.resetCookieModal Allows you to invalidate the user's "accept cookie" modal choice. When invalidated, the user will be presented with the modal to accept/deny cookies once this.enable() is called again.
	 */
	public disable = ({
		resetCookieModal = false,
	}: { resetCookieModal?: boolean } = {}) => {
		this.stopSaveLoop();
		this.removeEventListeners();

		if (resetCookieModal) {
			this.settingShouldAutoSave = null;
		}
	};

	private addEventListeners = () => {
		const namespace = `.gpascForm${this.formId}`;
		$(document).on(`gform_page_loaded${namespace}`, () => {
			this.addHiddenNonceField();
		});

		this.$form.on(`change${namespace}`, (event) => {
			this.shouldSave = true;
		});

		this.$form.on(
			// Keyup is helpful for paragraph fields or other large form fields where the user may type for a while
			// before the input loses focus.
			`keyup${namespace}`,
			'textarea',
			(event) => {
				this.shouldSave = true;
			}
		);

		this.$form.on(`submit${namespace}`, (event) => {
			this.disable();
			this.removeQueryParam('gf_token');
		});

		/*
		 * Multi-file upload fields.
		 *
		 * Add a MutationObserver to the `gform_uploaded_files_FORMID` hidden input to watch for files being re-ordered
		 * and deleted.
		 */
		const fileUploadInputSelector = `#gform_uploaded_files_${this.formId}`;
		const fileUploadInput = document.querySelector(fileUploadInputSelector);

		if (fileUploadInput) {
			const observer = new MutationObserver((mutationsList) => {
				for (const mutation of mutationsList) {
					if (
						mutation.type === 'attributes' &&
						mutation.attributeName === 'value'
					) {
						this.shouldSave = true;
					}
				}
			});

			observer.observe(fileUploadInput, { attributes: true });
		}

		/*
		 * Listen to Plupload events for multi-file uploads as well.
		 *
		 * We originally thought that just listening to the hidden field would be enough for uploads as well, but on
		 * slower sites, it did not work as expected. The sent FormData would not include the newly uploaded file.
		 */
		const pluploadInstances = window?.gfMultiFileUploader ?? {
			uploaders: {},
		};

		for (const [instanceKey, instance] of Object.entries(
			pluploadInstances?.uploaders
		)) {
			// Example: gform_multifile_upload_5_3
			const instanceKeyBits = instanceKey.split('_');
			const formId = instanceKeyBits[3];

			if (formId !== this.formId.toString()) {
				continue;
			}

			instance.bind(
				'FilesAdded FilesRemoved StateChanged Refresh',
				() => {
					this.shouldSave = true;
				}
			);
		}
	};

	private removeEventListeners = () => {
		this.$form.off(`.gpascForm${this.formId}`);
	};

	private addHiddenNonceField = () => {
		const nonceFieldId = '#gpasc-ajax-nonce';
		if ($(nonceFieldId).length) {
			return;
		}

		$('<input>')
			.attr({
				type: 'hidden',
				name: 'nonce',
				id: nonceFieldId,
			})
			.appendTo(`#gform_${this.formId}`)
			.val(this.nonce);
	};

	/**
	 * Starts an infinite loop that handles sending form auto save requests when "this.shouldSave" is true.
	 *
	 * This approach works nicely as it essentially functions as a cheap and low complexity
	 * debounce. It also allows us to easily avoid sending concurrent auto save requests.
	 */
	private startSaveLoop = async () => {
		if (this.saveLoopStarted || !this.shouldAutoSave()) {
			return;
		}

		this.saveLoopStarted = true;

		while (this.saveLoopStarted) {
			if (!this.saveLoopStarted) {
				break;
			}

			if (!this.shouldSave) {
				await this.wait(1000);
				continue;
			}

			await this.handleSave();
			this.shouldSave = false;
		}
	};

	private stopSaveLoop = () => {
		this.saveLoopStarted = false;
	};

	public getFormData = () => {
		const data = new FormData(this.$form[0]);

		/*
		 * Preserve rich text editor formatting.
		 *
		 * Gravity Forms syncs only `text` and not the HTML when syncing to textarea prior to submission.
		 * See https://github.com/gravityforms/gravityforms/blame/5911eb588aad423eb6dd43a2f22a417caf53f154/includes/fields/class-gf-field-textarea.php#L134		this.$form.find('textarea').each(function () {
		 */
		this.$form.find('textarea').each(function () {
			if (window.tinymce?.get($(this).attr('id'))) {
				data.set(
					$(this).attr('name') || '',
					window.tinymce.get($(this).attr('id')).getContent()
				);
			}
		});

		/**
		 * Filters the form `formData` before auto save.
		 *
		 * @since 1.0.22
		 *
		 * @param {Object}                    data     The form formData.
		 * @param {GPAdvancedSaveAndContinue} instance The instance of GPAdvancedSaveAndContinue.
		 */
		return window.gform.applyFilters(
			'gpasc_auto_save_form_data',
			data,
			this
		);
	};

	private handleSave = async () => {
		if (!this.shouldAutoSave()) {
			return;
		}

		const params = new URLSearchParams();

		params.append('action', 'gpasc_save_form');
		params.append('gpasc_auto_save', '1');
		params.append('gpasc_auto_save_source_url', window.location.href);
		params.append('nonce', this.nonce);

		let autoSaveSucceeded = true;
		let autoSaveErr = null;
		try {
			this.autoSaveRequest = $.ajax({
				url: `${this.ajaxUrl}?${params.toString()}`,
				type: 'POST',
				processData: false,
				contentType: false,
				data: this.getFormData(),
			});

			window.gform.doAction('gpasc_auto_save_started', this.formId, this);

			const resp: GPASCSaveDraftResponse = await this.autoSaveRequest;

			if (resp?.resume_token) {
				this.updateResumeTokenHiddenField(resp.resume_token);
				this.setResumeTokenQueryParam(resp.resume_token);
			}
		} catch (err) {
			autoSaveSucceeded = false;
			autoSaveErr = err;
			// eslint-disable-next-line no-console
			console.warn(err);
		} finally {
			window.gform.doAction(
				'gpasc_auto_save_finished',
				this.formId,
				this,
				{ success: autoSaveSucceeded, error: autoSaveErr }
			);

			this.autoSaveRequest = null;
		}
	};

	private updateResumeTokenHiddenField = (resumeToken: string) => {
		$(`#gform_resume_token_${this.formId}`).val(resumeToken);
	};

	private setResumeTokenQueryParam = (resumeToken: string) => {
		const { pathname, search } = window.location;
		const params = new URLSearchParams(search);

		if (params.get('gf_token')) {
			return;
		}

		params.delete('gpasc_new_draft');
		params.append('gf_token', resumeToken);

		history.pushState(null, '', `${pathname}?${params.toString()}`);
	};

	private removeQueryParam = (param: string) => {
		const { pathname, search } = window.location;
		const params = new URLSearchParams(search);

		params.delete(param);

		let nextPathname = pathname;
		if (params.toString().length) {
			nextPathname = `${pathname}?${params.toString()}`;
		}

		history.pushState(null, '', nextPathname);
	};

	private wait = async (delay: number = 1000) => {
		return new Promise((resolve) => setTimeout(resolve, delay));
	};

	private maybeShowAutoSaveConfirmationModal = () => {
		if (this.isUserLoggedIn) {
			return;
		}

		if (!this.settings.nonAuthedVisitorPromptEnabled) {
			this.settingShouldAutoSave = true;
			return;
		}

		// if this is a boolean, then the user has already made a choice
		if (typeof this.settingShouldAutoSave === 'boolean') {
			return;
		}

		this.showAutoSaveConfirmationModal();
	};

	private showAutoSaveConfirmationModal = () => {
		const modal = new tingle.modal({
			footer: true,
			closeMethods: ['overlay', 'button', 'escape'],
		});

		let content = '';
		content += `<h2>${this.settings.visitorPromptTitle}</h2>`;
		content += `<p>${this.settings.visitorPromptDescription}</p>`;

		modal.setContent(content);

		modal.addFooterBtn(
			this.settings.visitorPromptAcceptButtonLabel,
			'tingle-btn tingle-btn--pull-right tingle-btn--primary',
			() => {
				this.settingShouldAutoSave = true;
				modal.close();
			}
		);

		modal.addFooterBtn(
			this.settings.visitorPromptDeclineButtonLabel,
			'tingle-btn tingle-btn--pull-right tingle-btn--default',
			() => {
				this.settingShouldAutoSave = false;
				modal.close();
			}
		);

		modal.open();
	};

	shouldAutoSave = () => {
		if (!this.settings.autoSaveEnabled) {
			return false;
		}

		if (!this.isUserLoggedIn && this.settingShouldAutoSave === false) {
			return false;
		}

		return true;
	};

	/**
	 * Sets the localstorage value for the user selection of whether or not they
	 * want to auto save.
	 */
	set settingShouldAutoSave(autoSaveEnabled: boolean | null) {
		if (autoSaveEnabled === null) {
			this.removeCookie(
				GPAdvancedSaveAndContinue.userShouldAutoSaveSettingName
			);

			return;
		}

		this.setCookie(
			GPAdvancedSaveAndContinue.userShouldAutoSaveSettingName,
			autoSaveEnabled
		);
	}

	/**
	 * Gets the localstorage value for the user selection of whether or not they
	 * want to auto save.
	 */
	get settingShouldAutoSave(): boolean | null {
		let shouldAutoSave: boolean | null = null;

		const storageVal = this.getCookieValue(
			GPAdvancedSaveAndContinue.userShouldAutoSaveSettingName
		);

		switch (storageVal) {
			case 'true':
				shouldAutoSave = true;
				break;
			case 'false':
				shouldAutoSave = false;
				break;
			default:
				shouldAutoSave = null;
				break;
		}

		return shouldAutoSave;
	}

	public getCookieMap() {
		return document.cookie
			.split(';')
			.filter(Boolean)
			.reduce((acc, cookie) => {
				const [key, value] = cookie.split('=');
				acc.set(key.trim(), value);
				return acc;
			}, new Map<string, any>());
	}

	public getCookieValue(key: string) {
		const cookieMap = this.getCookieMap();
		return cookieMap.get(key) || null;
	}

	public setCookie(key: string, value: any, path: string = '/') {
		document.cookie = `${key}=${value}; path=${path}`;
	}

	public removeCookie(key: string, path: string = '/') {
		document.cookie = `${key}=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=${path};`;
	}
}
