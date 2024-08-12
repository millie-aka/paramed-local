interface GPASCDeleteDraftResponse {
	error?: string;
}

interface DraftsOpts {
	formId: number;
	nonce: string;
	ajaxUrl: string;
	userId?: number; // Used by shortcode user_id attribute which requires GFCommon::current_user_can_any( 'gravityforms_view_entries' )
}

const $ = window.jQuery;
const { strings } = window?.GPASC_DRAFT_MANAGEMENT;

export class DraftManagement {
	public formId: DraftsOpts['formId'];
	public nonce: DraftsOpts['nonce'];
	public ajaxUrl: DraftsOpts['ajaxUrl'];
	public userId?: DraftsOpts['userId'];

	public $container: JQuery<HTMLUListElement>;

	constructor(opts: DraftsOpts) {
		this.formId = opts.formId;
		this.nonce = opts.nonce;
		this.ajaxUrl = opts.ajaxUrl;
		this.userId = opts.userId;

		this.$container = $(
			!this.userId
				? `#gpasc_resume_token_list_${opts.formId}:not([data-user-id])`
				: `#gpasc_resume_token_list_${opts.formId}[data-user-id="${opts.userId}"]`
		);

		this.bind();
	}

	bind() {
		this.$container.on(
			'click',
			'.gpasc-delete-draft-button',
			async (event) => {
				// eslint-disable-next-line no-alert
				const shouldDelete = window.confirm(
					strings.confirm_delete_draft
				);

				if (!shouldDelete) {
					return;
				}

				const resumeToken =
					event.currentTarget.dataset.gpascResumeToken;
				const button = event.currentTarget;
				const $iconSpan = $(button).children('span');

				// replace trash icon with loading spinner
				$iconSpan.removeClass('dashicons-trash');
				$iconSpan.addClass('dashicons-update gpasc-loader-spin');

				const params = new URLSearchParams();
				params.append('action', 'gpasc_delete_draft');
				params.append('nonce', this.nonce);

				try {
					const resp: GPASCDeleteDraftResponse = await $.ajax({
						url: `${this.ajaxUrl}?${params.toString()}`,
						type: 'POST',
						data: {
							resumeToken,
							formId: this.formId,
							userId: this.userId,
						},
					});

					// throw so that all UI state error handling can be done in the same place.
					if (resp.error) {
						throw new Error(resp.error);
					}

					$(button).parents('li').remove();
				} catch (err) {
					// eslint-disable-next-line no-console
					console.error(err);

					// @ts-ignore
					// eslint-disable-next-line no-alert
					alert(`Failed to delete draft: ${err.message}`);

					// replace loading spinner with trash icon
					$iconSpan.removeClass('dashicons-update gpasc-loader-spin');
					$iconSpan.addClass('dashicons-trash');
				}
			}
		);
	}
}

window.GPAdvancedSaveAndContinueDraftManagement = DraftManagement;
