<?php

use GP_Advanced_Save_And_Continue\Authenticated_User;
use GP_Advanced_Save_And_Continue\Unauthenticated_User;
use GP_Advanced_Save_And_Continue\Settings;
use GP_Advanced_Save_And_Continue\Shortcodes;
use GP_Advanced_Save_And_Continue\GPEB_Compat;
use GP_Advanced_Save_And_Continue\Utils;

if ( ! class_exists( 'GP_Plugin' ) ) {
	return;
}

class GP_Advanced_Save_And_Continue extends GP_Plugin {

	private static $instance = null;

	protected $_version     = GP_ADVANCED_SAVE_AND_CONTINUE_VERSION;
	protected $_path        = 'gp-advanced-save-and-continue/gp-advanced-save-and-continue.php';
	protected $_full_path   = __FILE__;
	protected $_slug        = GP_ADVANCED_SAVE_AND_CONTINUE_SLUG;
	protected $_title       = 'Gravity Wiz Advanced Save and Continue';
	protected $_short_title = 'Advanced Save and Continue';

	/**
	 * @since  1.0-beta-1.2
	 * @var    string $_capabilities_settings_page The capability needed to access the Add-On settings page.
	 */
	protected $_capabilities_settings_page = 'gp-advanced-save-and-continue_settings';

	/**
	 * @since  1.0-beta-1.2
	 * @var    string $_capabilities_form_settings The capability needed to access the Add-On form settings page.
	 */
	protected $_capabilities_form_settings = 'gp-advanced-save-and-continue_form_settings';

	/**
	 * @since  1.0-beta-1.2
	 * @var    string $_capabilities_uninstall The capability needed to uninstall the Add-On.
	 */
	protected $_capabilities_uninstall = 'gp-advanced-save-and-continue_uninstall';

	public $settings;

	public $shortcodes;

	/**
	 * @var int The ID of the user that originally created the draft that is being updated using the Save & Continue button.
	 *
	 * This property is needed as we get the ID before the draft is updated during `gform_pre_process` and then handle
	 * token storage during `gform_post_process`.
	 */
	public $save_and_continue_draft_created_by;

	public static function get_instance() {
		if ( self::$instance === null ) {
			self::$instance = new self;
		}

		return self::$instance;
	}

	public function minimum_requirements() {
		return array(
			'gravityforms' => array(
				'version' => '2.5',
			),
			'wordpress'    => array(
				'version' => '4.8',
			),
			'plugins'      => array(
				'gravityperks/gravityperks.php' => array(
					'name'    => 'Gravity Perks',
					'version' => '2.0',
				),
			),
		);
	}

	public function __construct() {
		parent::__construct();

		$this->settings   = new Settings();
		$this->shortcodes = new Shortcodes( $this->get_base_url(), $this->_version );

		add_action( 'init', array( $this, 'init' ) );
	}


	public function init() {
		parent::init();

		load_plugin_textdomain( $this->_slug, false, basename( dirname( __file__ ) ) . '/languages/' );

		// handle autoloading draft entries
		add_filter( 'gform_form_args', array( $this, 'auto_load_draft' ), 10 );

		// save token on submission - or - delete token on successful submission
		add_filter( 'gform_pre_process', array( $this, 'set_draft_submission_created_by' ) );
		add_action( 'gform_post_process', array( $this, 'handle_token_storage' ), 10, 2 );

		// a POST endpoint for deleting a draft entry
		add_action( 'wp_ajax_gpasc_delete_draft', array( $this, 'wp_ajax_delete_draft' ) );
		add_action( 'wp_ajax_nopriv_gpasc_delete_draft', array( $this, 'wp_ajax_delete_draft' ) );

		// add JS for the form view
		add_filter( 'gform_register_init_scripts', array( $this, 'add_init_scripts' ), 10, 2 );

		// handle displaying an inline confirmation on form submission
		add_filter( 'gform_form_args', array( $this, 'maybe_display_inline_confirmation' ), 10, 2 );

		// modify the target page number if the request is an auto-save request
		add_filter( 'gform_target_page', array( $this, 'filter_gform_target_page' ), 10, 4 );

		// get the resume token for GPNF
		add_filter( 'gpnf_save_and_continue_token', array( $this, 'gpnf_get_resume_token' ), 10, 2 );
		add_filter( 'gpnf_should_load_child_entries_from_session', array( $this, 'gpnf_disable_loading_child_entries_from_session' ), 10, 2 );

		// merge any resume tokens from cookies into the user meta table and associate them with the logged in user.
		add_filter( 'wp_login', array( $this, 'wp_login_store_resume_token_cookies_in_db' ), 10, 2 );

		// modify form setings with GPASC specific items.
		add_filter( 'gform_form_settings_fields', array( $this, 'filter_gform_form_settings_fields' ) );

		add_filter( 'gform_save_and_continue_resume_url', array( $this, 'remove_gpasc_new_draft_param_from_resume_link' ), 10, 4 );

		// handle removing the save and continue button from the form if the "hide_save_and_continue_link" setting is enabled.
		add_filter( 'gform_savecontinue_link', array( $this, 'maybe_remove_save_and_continue_button' ), 10, 2 );

		// handle addding draft links at the top of the form if "display_available_drafts_above_form" setting is enabled.
		add_filter( 'gform_get_form_filter', array( $this, 'maybe_add_draft_links_markup_to_form' ), 10, 2 );

		// sync our draft token state with that in the `x_gf_draft_tokens` table.
		add_filter( 'gform_purge_expired_incomplete_submissions_query', array( $this, 'filter_gform_purge_expired_incomplete_submissions_query' ), 10, 1 );

		// handle migrating the token cookie data structure from using one single cookie to using a separate cookie for each form.
		Unauthenticated_User::migrate_single_cookie_to_form_specific_cookies();
	}

	public function init_admin() {
		parent::init_admin();

		GravityPerks::enqueue_field_settings();
	}

	public function scripts() {
		$scripts = array(
			array(
				'handle'    => 'gp-advanced-save-and-continue-draft-management',
				'src'       => $this->get_base_url() . '/js/built/gp-advanced-save-and-continue-draft-management.js',
				'version'   => $this->_version,
				'deps'      => array( 'jquery' ),
				'in_footer' => true,
				'enqueue'   => array(
					array( $this, 'should_enqueue_frontend' ),
				),
				'callback'  => array( $this, 'localize_draft_management_scripts' ),
			),
			array(
				'handle'    => $this->_slug,
				'src'       => $this->get_base_url() . '/js/built/gp-advanced-save-and-continue.js',
				'version'   => $this->_version,
				'deps'      => array( 'gform_gravityforms', 'gp-advanced-save-and-continue-draft-management' ),
				'in_footer' => true,
				'enqueue'   => array(
					array( $this, 'should_enqueue_frontend' ),
				),
			),
			array(
				'handle'    => 'gp-advanced-save-and-continue-form-settings',
				'src'       => $this->get_base_url() . '/js/built/gp-advanced-save-and-continue-form-settings.js',
				'version'   => $this->_version,
				'deps'      => array( 'gform_gravityforms', 'jquery' ),
				'in_footer' => true,
				'enqueue'   => array(
					array( 'admin_page' => array( 'form_settings' ) ),
				),
			),
		);

		return array_merge( parent::scripts(), $scripts );
	}

	public function localize_draft_management_scripts() {
		static $draft_management_localized = false;

		if ( $draft_management_localized ) {
			return;
		}

		$draft_management_l10n = array(
			'strings' => array(
				'confirm_delete_draft' => __( 'Are you sure you want to delete this draft?', 'gp-advanced-save-and-continue' ),
			),
		);

		wp_localize_script( 'gp-advanced-save-and-continue-draft-management', 'GPASC_DRAFT_MANAGEMENT', $draft_management_l10n );

		$draft_management_localized = true;
	}

	public function styles() {
		parent::styles();

		$min = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG || isset( $_GET['gform_debug'] ) ? '' : '.min';

		$styles[] = array(
			'handle'  => 'tingle',
			'src'     => $this->get_base_url() . "/css/tingle{$min}.css",
			'version' => $this->_version,
			'enqueue' => array(
				array( $this, 'should_enqueue_frontend' ),
			),
		);

		$styles[] = array(
			'handle'  => 'gpasc-frontend',
			'src'     => $this->get_base_url() . "/css/styles{$min}.css",
			'version' => $this->_version,
			'enqueue' => array(
				array( $this, 'should_enqueue_frontend' ),
			),
		);

		$styles[] = array(
			'handle'  => 'dashicons',
			'enqueue' => array(
				array( $this, 'should_enqueue_frontend' ),
			),
		);

		$styles[] = array(
			'handle'  => 'form_settings',
			'src'     => $this->get_base_url() . "/css/form-settings{$min}.css",
			'version' => $this->_version,
			'enqueue' => array(
				array( 'admin_page' => array( 'form_settings' ) ),
			),
		);

		return array_merge( parent::styles(), $styles );
	}

	/**
	 * Add icon to the form settings menu.
	 *
	 * @return string
	 */
	public function get_menu_icon() {
		return $this->get_base_url() . '/assets/menu-icon.svg';
	}

	/**
	 * Determine if frontend scripts/styles should be enqueued.
	 *
	 * @param $form
	 *
	 * @return bool
	 */
	public function should_enqueue_frontend( $form ) {
		if ( GFForms::get_page() ) {
			return false;
		}

		return $this->is_applicable_form( $form );
	}

	public function add_init_scripts( $form ) {
		// If plugin is not enabled for this form or GPEB edit entry mode is active (not ASC), return.
		if ( ! $this->is_applicable_form( $form ) ) {
			return;
		}

		$args = array(
			'formId'                  => $form['id'],
			'ajaxUrl'                 => admin_url( 'admin-ajax.php' ),
			'nonce'                   => wp_create_nonce( $this->_slug ),
			'isUserLoggedIn'          => is_user_logged_in(),
			/**
			 * Disable immediate initialization of GPASC functionality.
			 *
			 * @param boolean $should_defer_init whether or not to defer initializing GPASC functionality (default is false)
			 * @param array $form the current form object being processed
			 *
			 * @since 1.0-beta-1.2
			 */
			'shouldDeferAutosaveInit' => gf_apply_filters( array( 'gpasc_should_defer_autosave_init', $form['id'] ), false, $form ),
			/**
			 * Filter the frontend init settings.
			 *
			 * @param array $settings The frontend init settings.
			 * @param array $form The current form.
			 *
			 * @since 1.0-beta-1
			 */
			'settings'                => gf_apply_filters( array( 'gpasc_init_settings', $form['id'] ), array(
				'autoSaveEnabled'                 => $this->settings->should_auto_save_draft( $form ),
				'nonAuthedVisitorPromptEnabled'   => $this->settings->visitor_prompt_enabled( $form ),
				'visitorPromptTitle'              => $this->settings->visitor_prompt_title( $form ),
				'visitorPromptDescription'        => $this->settings->visitor_prompt_description( $form ),
				'visitorPromptAcceptButtonLabel'  => $this->settings->visitor_prompt_accept_button_label( $form ),
				'visitorPromptDeclineButtonLabel' => $this->settings->visitor_prompt_decline_button_label( $form ),
			), $form ),
		);

		$script  = 'createGPAdvancedSaveAndContinue( ' . json_encode( $args ) . ' );';
		$form_id = $form['id'];
		$slug    = 'gp_advanced_save_and_continue_' . $form_id;
		GFFormDisplay::add_init_script( $form_id, $slug, GFFormDisplay::ON_PAGE_RENDER, $script );
	}

	/**
	 * Determine if the form uses the perk.
	 *
	 * @param array|int $form or $form_id
	 *
	 * @return bool
	 */
	public function is_applicable_form( $form_id ) {
		if ( $form_id === false || $form_id === null ) {
			return false;
		}

		$form = isset( $form_id['id'] ) ? $form_id : GFAPI::get_form( $form_id );

		if ( GPEB_Compat::is_editing_entry( $form['id'] ) ) {
			return false;
		}

		if ( function_exists( 'gravityview' ) && gravityview()->request->is_edit_entry() ) {
			return false;
		}

		return $this->is_plugin_enabled( $form );
	}

	public function is_auto_save_request() {
		return ! empty( rgget( 'gpasc_auto_save' ) );
	}

	public function filter_gform_target_page( $page_number, $form, $current_page, $field_values ) {
		if ( ! $this->is_auto_save_request() || ! $this->is_applicable_form( $form['id'] ) ) {
			return $page_number;
		}

		/**
		 * Set the "target page" equal to the "source page" during auto save requests.
		 *
		 * - "source page" is the page the user is currently on
		 * - "target page" is the page that the user is going to
		 *
		 * Setting the "target page" number to the "source page" does two things:
		 *
		 * 1. communicates that we are not switching pages with this request (we are autosaving and staying on the same page)
		 * 2. ensures that this value is never "0". This is important because "0" signals to Gravity Forms that the
		 *    form is being submitted which results in:
		 *        - Gravity Forms removes the resume_token from the database
		 *        - Gravity Forms generates a new one.
		 */
		return intval( rgpost( 'gform_source_page_number_' . $form['id'] ) );
	}

	public function filter_gform_purge_expired_incomplete_submissions_query( $query ) {
		global $wpdb;

		// Not preparing this as the necessary parts of $query are already passed to $wpdb->prepare().
		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$expired_uuids = $wpdb->get_col( sprintf( 'SELECT uuid %s %s', $query['from'], $query['where'] ) );

		$token_data = $this->get_all_resume_tokens_for_current_user();

		foreach ( $token_data as $token ) {
			if ( ! in_array( $token['token'], $expired_uuids ) ) {
				continue;
			}

			$this->delete_resume_token_for_current_user( $token['uuid'], GFAPI::get_form( $token['form_id'] ) );
		}

		return $query;
	}

	public function get_all_resume_tokens_for_current_user() {
		if ( is_user_logged_in() ) {
			return Authenticated_User::get_all_resume_tokens();
		} else {
			return Unauthenticated_User::get_all_resume_tokens();
		}
	}

	public function delete_resume_token_for_current_user( $resume_token, $form, $user_id = null ) {
		if ( is_user_logged_in() ) {
			Authenticated_User::delete_resume_token( $resume_token, $form, $user_id );
		} else {
			Unauthenticated_User::delete_resume_token( $resume_token, $form );
		}
	}

	/**
	 * Automatically load a draft entry if one exists. If gf_token is present in the query params, that value will
	 * be used. Otherwise, it will attempt to find the token for the most recent draft entry. If no token is found,
	 * then no draft entry will be loaded.
	 *
	 * @param array $args
	 */
	public function auto_load_draft( $args ) {
		$is_submitting = rgpost( "is_submit_{$args['form_id']}" );

		if ( ! $this->is_applicable_form( $args['form_id'] ) || $is_submitting ) {
			return $args;
		}

		$form = GFAPI::get_form( $args['form_id'] );

		// Skipping autoload allows users to start a blank draft.
		if ( rgget( 'gpasc_new_draft' ) === 'true' ) {
			/**
			 * This results in the initial resume token getting set in the hidden, gform_resume_token_FORMID
			 * field. We do this instead of setting it on $_GET['gf_token'] because that would result in
			 * gravity forms loading up a blank draft and then overwriting any dynamic values coming from
			 * GPPA.
			 */
			$_POST['gform_resume_token'] = $this->create_initial_resume_token( $form );
			return $args;
		}

		// Use the existing token from query params if it exists.
		$token = rgget( 'gf_token' );

		if ( ! $this->settings->should_auto_load_draft( $form ) && ! $token ) {
			if ( $this->settings->should_auto_save_draft( $form ) ) {
				$token            = $this->create_initial_resume_token( $form );
				$_GET['gf_token'] = $token;
			}

			return $args;
		}

		// If the token is expired, then set it to null so that we can then default to the most recent draft.
		if ( ! empty( $token ) && Utils::is_resume_token_expired( $token ) ) {
			$token = null;
		}

		// If no token was found in the query params, then attempt to find the token from the most recently edited draft.
		if ( empty( $token ) ) {
			$token = $this->get_current_user_most_recent_resume_token( $args['form_id'] );
		}

		/**
		 * Set the token in the query params array so that the form will be populated with the draft values.
		 * This is a bit of a hack, but it makes loading draft entry values into the form really easy since
		 * this will trigger Gravity Forms to load the draft entry values automatically.
		 */
		if ( ! empty( $token ) ) {
			$_GET['gf_token'] = $token;
			/**
			 * This is for GP_Map_Field::get_prepopulate_coords() compatiblity since it tries to get
			 * gf_token from _REQUEST instead of _GET.
			 */
			$_REQUEST['gf_token'] = $token;

			add_filter( 'gform_get_form_filter', array( $this, 'add_auto_load_notice_markup' ), 8, 2 );

			return $args;
		}

		/**
		 * This results in the initial resume token getting set in the hidden, gform_resume_token_FORMID
		 * field. We do this instead of setting it on $_GET['gf_token'] because that would result in
		 * gravity forms loading up a blank draft and then overwriting any dynamic values coming from
		 * GPPA.
		 */
		$_POST['gform_resume_token'] = $this->create_initial_resume_token( GFAPI::get_form( $args['form_id'] ) );

		return $args;
	}

	/**
	 * Sets the original created_by on the draft submission into an instance property, so it can be used to look up the
	 * user who originally created the draft.
	 *
	 * This way, if using the user_id shortcode attribute, we can update that user's meta instead of the current user's.
	 *
	 * @param array $form
	 *
	 * @return array Unmodified form.
	 */
	public function set_draft_submission_created_by( $form ) {
		if ( ! $this->is_applicable_form( $form['id'] ) ) {
			return $form;
		}

		$resume_token = rgpost( 'gform_resume_token' );

		if ( ! $resume_token ) {
			return $form;
		}

		$draft = gp_advanced_save_and_continue()->get_draft_entry_by_resume_token( $resume_token );

		if ( $draft ) {
			$submission = \GFCommon::maybe_decode_json( rgar( $draft, 'submission' ) );
			$created_by = rgars( $submission, 'partial_entry/created_by' );

			if ( $created_by ) {
				$this->save_and_continue_draft_created_by = $created_by;
			}
		}

		return $form;
	}

	public function handle_token_storage( $form, $page_number ) {
		if ( ! $this->is_applicable_form( $form['id'] ) ) {
			return;
		}

		$is_auto_saving = $this->is_auto_save_request();
		if ( ! $this->is_valid_nonce() && $is_auto_saving ) {
			wp_die( __( 'Invalid nonce.', 'gp-advanced-save-and-continue' ) );

			return;
		}

		$submission                       = GFFormDisplay::$submission[ $form['id'] ];
		$is_successful_submission         = ! $is_auto_saving && $page_number == 0 && $submission['is_valid'];
		$logged_in                        = is_user_logged_in();
		$should_auto_save_non_authed_user = rgar( $_COOKIE, 'gpasc-user-should-auto-save' ) === 'true';
		$user_id                          = null;

		/*
		 * If this token belongs to a different user, find that user and update their meta as long as the current
		 * user has the correct capabilities.
		 */
		if (
			$this->shortcodes->can_view_any_entry()
			&& $this->save_and_continue_draft_created_by
			&& $this->save_and_continue_draft_created_by !== get_current_user_id()
		) {
			$user_id = $this->save_and_continue_draft_created_by;
		}

		// Check to see if an unauthenticated user has opted not to participate in autosave.
		if ( ! $should_auto_save_non_authed_user && ! $logged_in ) {
			if ( $is_auto_saving ) {
				// send null since callers are expecting a valid JSON value
				wp_send_json( null );
			}
			return;
		}

		delete_transient( self::cache_key_initial_resume_token( $form ) );

		/**
		 * Deletes any save and continue resume tokens associated with the new entry submission as
		 * the draft submission has been completed and is no longer in "draft" state.
		 */
		if ( $is_successful_submission ) {
			$resume_token = rgpost( 'gform_resume_token' );
			$this->delete_resume_token_for_current_user( $resume_token, $form );
			return;
		}

		$saved_for_later = rgar( $submission, 'saved_for_later' );

		// Build out the path to the form where the draft submission was made so that we can display/load it with a shortcode later.
		$request_referer = rgar( $_SERVER, 'HTTP_REFERER' );
		$form_path       = $this->get_auto_save_form_path( $request_referer );

		if ( $saved_for_later ) {
			$resume_token = $submission['resume_token'];
			if ( ! $logged_in ) {
				// Saves a resume token for an unauthenticated user in an HTTP cookie for the current entry draft context if the "save and continue" button was clicked.
				Unauthenticated_User::save_resume_token( $resume_token, $form, $form_path );
				return;
			} else {
				// Saves a resume token for the current entry draft context if the "save and continue" button was clicked.
				Authenticated_User::save_resume_token( $resume_token, $form, $form_path, $user_id );
				return;
			}
		}

		$should_auto_save = $this->settings->should_auto_save_draft( $form );

		// This block is evaluated when navigating between form form pages or when auto-saving via AJAX.
		if ( $should_auto_save ) {
			$resume_token = $this->auto_save_entry_draft( $form, $page_number );

			if ( ! $resume_token ) {
				if ( $is_auto_saving ) {
					wp_send_json( null );
				} else {
					return;
				}
			}

			$_POST['gform_resume_token'] = $resume_token;

			if ( ! $logged_in ) {
				// Saves a save and continue resume token for the current entry draft when navigating between pages if the user is not logged in.
				Unauthenticated_User::save_resume_token( $resume_token, $form, $form_path );
			} else {
				// Saves a save and continue resume token for the current entry draft when navigating between pages.
				Authenticated_User::save_resume_token( $resume_token, $form, $form_path, $user_id );
			}

			// This happens when the form is auto-saved via JS (e.g. **not** when a form page changes)
			if ( $is_auto_saving ) {
				wp_send_json( array( 'resume_token' => $resume_token ) );
			}
		}
	}

	/**
	 * This function is run by Gravity Forms every time form settings are saved.
	 */
	public function save_form_settings( $form, $settings ) {
		// This is needed to ensure that Gravity Forms knows what values to use for form settings on subsequent form settings loads.
		$form[ $this->_slug ] = $settings;

		$form['save']['enabled'] = rgar( $settings, 'save_and_continue_enabled' ) === '1';
		$form['saveEnabled']     = $form['save']['enabled'];

		$form['save']['button']['text'] = rgar( $settings, 'save_and_continue_button_text' );
		$form['saveButtonText']         = $form['save']['button']['text'];

		$form['save']['button']['type'] = 'text';

		/**
		 * add or remove confirmations and notifications if save/continue has been enabled or disabled.
		 * this functionality is duplicated from gravityforms/form_settings.php to achieve feature parity
		 * with save and continue functionality when this plugin is deactivated.
		*/
		if ( $form['save']['enabled'] ) {
			$form = GFFormSettings::activate_save( $form );
		} else {
			$form = GFFormSettings::deactivate_save( $form );
		}

		$result = GFFormsModel::update_form_meta( $form['id'], $form );

		return ! ( $result === false );
	}

	public function generate_settings_tooltip( $heading, $body ) {
		$markup = sprintf( '<strong>%s</strong>%s', $heading, $body );
		return gform_tooltip( $markup, '', true );
	}

	/**
	 * This function is run by Gravity Forms every time form settings are loaded and is
	 * used to add a new "settings" tab to the form settings.
	 */
	public function form_settings_fields( $form ) {
		return array(
			// Save and Continue settings as copied from gravity-forms/form_settings.php
			array(
				'title'  => __( 'General Settings', 'gp-advanced-save-and-continue' ),
				'fields' => array(
					array(
						'name'          => 'save_and_continue_enabled',
						'type'          => 'toggle',
						'tooltip'       => $this->generate_settings_tooltip(
							__( 'Save and Continue Later', 'gp-advanced-save-and-continue' ),
							__( 'Enable this setting to allow users to save their form progress and continue it at a later time.', 'gp-advanced-save-and-continue' )
						),
						'label'         => __( 'Enable Save and Continue', 'gp-advanced-save-and-continue' ),
						// this value should map to the standard save and continue setting.
						'default_value' => rgars( $form, 'save/enabled', false ),
					),
					array(
						'name'       => 'save_and_continue_warning_html',
						'type'       => 'html',
						'html'       => sprintf(
							'<div class="alert warning"><p>%s</p></div>',
							__( 'This feature stores potentially private and sensitive data on this server and protects it with a unique link which is displayed to the user on the page in plain, unencrypted text. The link is similar to a password so it&#039;s strongly advisable to ensure that the page enforces a secure connection (HTTPS) before activating this setting.</p><p>When this setting is activated two confirmations and one notification are automatically generated and can be modified in their respective editors. When this setting is deactivated the confirmations and the notification will be deleted automatically and any modifications will be lost.', 'gp-advanced-save-and-continue' )
						),
						'dependency' => array(
							'live'   => true,
							'fields' => array(
								array(
									'field' => 'save_and_continue_enabled',
									'value' => true,
								),
							),
						),
					),
					array(
						'name'          => 'save_and_continue_button_text',
						'type'          => 'text',
						'default_value' => 'Save and Continue Later',
						'label'         => __( 'Link Text', 'gp-advanced-save-and-continue' ),
						'tooltip'       => $this->generate_settings_tooltip(
							__( 'Save and Continue Button Text', 'gp-advanced-save-and-continue' ),
							__( 'Customize the text displayed in the "Save and "Continue" button.', 'gp-advanced-save-and-continue' )
						),
						'dependency'    => array(
							'live'   => true,
							'fields' => array(
								array(
									'field' => 'save_and_continue_enabled',
									'value' => true,
								),
							),
						),
					),
				),
			),
			array(
				'title'      => __( 'Advanced Settings', 'gp-advanced-save-and-continue' ),
				'dependency' => array(
					'live'   => true,
					'fields' => array(
						array(
							'field' => 'save_and_continue_enabled',
							'value' => true,
						),
					),
				),
				'fields'     => array(
					array(
						'name'          => 'auto_save_and_load_enabled',
						'type'          => 'toggle',
						'label'         => __( 'Enable Auto Save and Load', 'gp-advanced-save-and-continue' ),
						'tooltip'       => $this->generate_settings_tooltip(
							__( 'Enable Auto Save and Load', 'gp-advanced-save-and-continue' ),
							__( 'For more granular control, this setting can be filtered to enable/disable <a href="https://gravitywiz.com/documentation/gpasc_should_auto_save/" target="blank" rel="noopener noreferrer">auto-save</a> or <a href="https://gravitywiz.com/documentation/gpasc_should_auto_load/" target="blank" rel="noopener noreferrer">auto-load</a> independently.', 'gp-advanced-save-and-continue' )
						),
						'default_value' => false,
					),
					array(
						'name'          => 'draft_management_enabled',
						'type'          => 'toggle',
						'label'         => __( 'Enable Draft Management', 'gp-advanced-save-and-continue' ),
						'tooltip'       => $this->generate_settings_tooltip(
							__( 'Enable Draft Management', 'gp-advanced-save-and-continue' ),
							__( 'Enable this setting to allow users to manage their own drafts. Additional configuration required in the "Draft Management Settings" below.', 'gp-advanced-save-and-continue' )
						),
						'default_value' => false,
					),
				),
			),
			array(
				'title'      => __( 'Auto Save and Load Settings', 'gp-advanced-save-and-continue' ),
				'fields'     => array(
					// ----------------------------
					// The "Resuming Draft Message" is hardcoded for now and will be included as a setting in a future release.
					// ----------------------------
					array(
						'label'   => __( 'Visitor Prompt', 'gp-advanced-save-and-continue' ),
						'type'    => 'html',
						'tooltip' => $this->generate_settings_tooltip(
							__( 'Visitor Prompt', 'gp-advanced-save-and-continue' ),
							__( 'Unauthenticated users will be prompted via a modal to confirm if their progress should be saved automatically. The following settings control the content of that modal.', 'gp-advanced-save-and-continue' )
						),
						'fields'  => array(
							array(
								'label'         => __( 'Prompt Title', 'gp-advanced-save-and-continue' ),
								'type'          => 'text',
								'default_value' => $this->settings->default_visitor_prompt_title,
								'name'          => 'visitor_prompt_title',
								'tooltip'       => $this->generate_settings_tooltip(
									__( 'Visitor Prompt: Title', 'gp-advanced-save-and-continue' ),
									__( 'Customize the prompt title displayed to visitors.', 'gp-advanced-save-and-continue' )
								),
							),
							array(
								'label'         => __( 'Prompt Description', 'gp-advanced-save-and-continue' ),
								'type'          => 'textarea',
								'default_value' => $this->settings->default_visitor_prompt_description,
								'name'          => 'visitor_prompt_description',
								'tooltip'       => $this->generate_settings_tooltip(
									__( 'Visitor Prompt: Description', 'gp-advanced-save-and-continue' ),
									__( 'Customize the prompt description displayed to visitors.', 'gp-advanced-save-and-continue' )
								),
							),
							array(
								'label'         => __( 'Accept Button Label', 'gp-advanced-save-and-continue' ),
								'type'          => 'text',
								'default_value' => $this->settings->default_visitor_prompt_accept_button_label,
								'name'          => 'visitor_prompt_accept_button_label',
								'tooltip'       => $this->generate_settings_tooltip(
									__( 'Visitor Prompt: Accept Button Label', 'gp-advanced-save-and-continue' ),
									__( 'Customize the button label displayed to visitors to accept automatically saving their progress.', 'gp-advanced-save-and-continue' )
								),
							),
							array(
								'label'         => __( 'Decline Button Label', 'gp-advanced-save-and-continue' ),
								'type'          => 'text',
								'default_value' => $this->settings->default_visitor_prompt_decline_button_label,
								'name'          => 'visitor_prompt_decline_button_label',
								'tooltip'       => $this->generate_settings_tooltip(
									__( 'Visitor Prompt: Accept Button Label', 'gp-advanced-save-and-continue' ),
									__( 'Customize the button label displayed to visitors to decline automatically saving their progress.', 'gp-advanced-save-and-continue' )
								),
							),
						),
					),
					array(
						'label'         => __( 'Hide Save and Continue Link', 'gp-advanced-save-and-continue' ),
						'name'          => 'hide_save_and_continue_link',
						'default_value' => '0',
						'type'          => 'toggle',
						'tooltip'       => $this->generate_settings_tooltip(
							__( 'Save and Continue Link', 'gp-advanced-save-and-continue' ),
							__( 'Enable this setting to hide the Save and Continue link displayed in the form footer. This allows you to rely exclusively on automatic save-and-continue.', 'gp-advanced-save-and-continue' )
						),
					),
					array(
						'name'          => 'inline_save_and_continue_confirmation_enabled',
						'type'          => 'toggle',
						'label'         => __( 'Display Save and Continue Confirmation Inline', 'gp-advanced-save-and-continue' ),
						'default_value' => '0',
						'tooltip'       => $this->generate_settings_tooltip(
							__( 'Inline Confirmation', 'gp-advanced-save-and-continue' ),
							__( 'Enable this setting to display the Save and Continue confirmation inline rather than on a new page.', 'gp-advanced-save-and-continue' )
						),
						'dependency'    => array(
							'live'   => true,
							'fields' => array(
								array(
									'field' => 'save_and_continue_enabled',
									'value' => true,
								),
							),
						),
					),
				),
				'dependency' => array(
					'live'   => true,
					'fields' => array(
						array(
							'field' => 'auto_save_and_load_enabled',
							'value' => true,
						),
						array(
							'field' => 'save_and_continue_enabled',
							'value' => true,
						),
					),
				),
			),
			array(
				'title'      => __( 'Draft Management Settings', 'gp-advanced-save-and-continue' ),
				'fields'     => array(
					array(
						'label'         => __( 'Display Available Drafts Above Form', 'gp-advanced-save-and-continue' ),
						'name'          => 'display_available_drafts_above_form',
						'default_value' => true,
						'type'          => 'toggle',
						'tooltip'       => $this->generate_settings_tooltip(
							__( 'Display Drafts Above Form', 'gp-advanced-save-and-continue' ),
							__( 'Display a list of a user\'s Save and Continue drafts above the form. If there are no drafts, nothing will be displayed.', 'gp-advanced-save-and-continue' )
						),
					),
					array(
						'label'   => __( 'Shortcode', 'gp-advanced-save-and-continue' ),
						'name'    => 'copy_short_code',
						'type'    => 'html',
						'html'    => $this->generate_shortcode_copier_html( $form ),
						'tooltip' => $this->generate_settings_tooltip(
							__( 'Display Drafts Above Form', 'gp-advanced-save-and-continue' ),
							__( 'Display a list of the current user\'s Save and Continue drafts above the form. If there are no drafts, nothing will be displayed.', 'gp-advanced-save-and-continue' )
						),
					),
				),
				'dependency' => array(
					'live'   => true,
					'fields' => array(
						array(
							'field' => 'draft_management_enabled',
							'value' => true,
						),
						array(
							'field' => 'save_and_continue_enabled',
							'value' => true,
						),
					),
				),
			),
		);
	}

	public function add_form_settings_menu( $tabs, $form_id ) {
		$tabs[] = array(
			'name'         => $this->_slug,
			'label'        => 'Save and Continue',
			'query'        => array( 'fid' => null ),
			'capabilities' => $this->_capabilities_form_settings,
			'icon'         => $this->get_menu_icon(),
		);

		return $tabs;
	}

	public function filter_gform_form_settings_fields( $fields ) {

		if ( rgget( 'subview' ) !== 'settings' ) {
			return $fields;
		}

		$form              = $this->get_current_form();
		$save_and_continue = &$fields['save_and_continue'];

		$html  = __( 'All Save and Continue settings are managed via the Save and Continue page when the <b>GP Advanced Save and Continue</b> plugin is active.', 'gp-advanced-save-and-continue' ) . '</br></br>';
		$html .= sprintf( '<a href="' . admin_url( 'admin.php?page=gf_edit_forms&view=settings&subview=' . $this->_slug . '&id=' . $form['id'] ) . '" class="gform-button gform-button--white">%s</a>', esc_html__( 'Manage Save and Continue', 'gp-advanced-save-and-continue' ) );

		$save_and_continue['fields'] = array(
			array(
				'name' => 'save_and_continue_settings_moved_warning',
				'type' => 'html',
				'html' => $html,
			),
			array(
				'name' => 'saveEnabled',
				'type' => 'hidden',
			),
			array(
				'name' => 'saveButtonText',
				'type' => 'hidden',
			),
		);

		return $fields;
	}

	public function remove_gpasc_new_draft_param_from_resume_link( $resume_url ) {
		return remove_query_arg( 'gpasc_new_draft', $resume_url );
	}

	/**
	 * Determines if the GPASC is enabled for a given form.
	 */
	public function is_plugin_enabled( $form ) {
		return $this->settings->save_and_continue_enabled( $form );
	}

	public function get_current_user_resume_tokens( $form_id, $user_id = null ) {
		if ( is_user_logged_in() ) {
			return Authenticated_User::get_form_resume_tokens( $form_id, $user_id );
		}

		return Unauthenticated_User::get_form_resume_tokens( $form_id );
	}

	public function get_current_user_most_recent_resume_token( $form_id ) {
		if ( ! is_user_logged_in() ) {
			return Unauthenticated_User::get_most_recent_resume_token( $form_id );
		}

		return Authenticated_User::get_most_recent_resume_token( $form_id );
	}

	/**
	 * Given a url, removes the gpasc_new_draft param and returns the path and query string
	 * without a protocol, host or port.
	 */
	public function get_auto_save_form_path( $original_url ) {
		$url_components = parse_url( $original_url );
		$path           = rgar( $url_components, 'path', '' );
		$query          = rgar( $url_components, 'query', '' );

		parse_str( $query, $query_str_map );
		unset( $query_str_map['gpasc_new_draft'] );
		return $path . '?' . http_build_query( $query_str_map );
	}

	/**
	 * Builds a URL from an associate array of $pieces such as parse_url() returns
	 */
	public function build_url( $pieces ) {
		$url = '';

		$scheme = rgar( $pieces, 'scheme', '' );
		$url   .= $scheme ? $scheme . '://' : '';

		$url .= rgar( $pieces, 'host', '' );

		$port = rgar( $pieces, 'port', '' );
		$url .= $port ? ':' . $port : '';

		$url .= rgar( $pieces, 'path', '' );

		$query = rgar( $pieces, 'query', '' );
		$url  .= $query ? '?' . $query : '';

		return $url;
	}

	public function gpnf_get_resume_token( $token, $form_id ) {
		$form            = GFAPI::get_form( $form_id );
		$should_autoload = $this->settings->should_auto_load_draft( $form ) && ! rgget( 'gpasc_new_draft' );

		if ( ! $token && $should_autoload ) {
			$token = Authenticated_User::get_most_recent_resume_token( $form_id );
		}

		return $token;
	}

	public function gpnf_disable_loading_child_entries_from_session( $load_from_session, $form ) {
		return $this->settings->should_auto_load_draft( $form ) ? false : $load_from_session;
	}

	/**
	 * Stores any S&C resume tokens found in HTTP cookies in the user meta table when a user logs in.
	 */
	public function wp_login_store_resume_token_cookies_in_db( $username, $user ) {
		$tokens = Unauthenticated_User::get_all_resume_tokens();

		foreach ( $tokens as $token_data ) {
			$form_id = $token_data['form_id'];
			$meta    = Authenticated_User::get_form_resume_tokens( $token_data['form_id'], $user->ID );

			$meta_exists = false;

			// If the resume token has already been added to the db, then update the updated_at timestamp.
			foreach ( $meta as $key => &$value ) {
				if ( $value['token'] === $token_data['token'] ) {
					$meta_exists         = true;
					$value['updated_at'] = max( $value['updated_at'], $token_data['updated_at'] );
					break;
				}
			}

			// If the resume token has not been added to the db, add it.
			if ( ! $meta_exists ) {
				$meta[] = array(
					'token'      => $token_data['token'],
					'updated_at' => $token_data['updated_at'],
					'form_path'  => $token_data['form_path'],
				);
			}

			update_user_meta( $user->ID, Authenticated_User::get_token_meta_key( $form_id ), $meta );
		}

		Unauthenticated_User::delete_resume_token_cookies();
	}

	public  function is_valid_nonce() {
		$nonce = rgpost( 'nonce' );

		if ( ! $nonce ) {
			$nonce = rgget( 'nonce' );
		}

		return wp_verify_nonce( $nonce, $this->_slug );
	}

	public function wp_ajax_delete_draft() {
		if ( ! $this->is_valid_nonce() ) {
			wp_send_json( array(
				'error' => __( 'Invalid nonce.', 'gp-advanced-save-and-continue' ),
			) );

			return;
		}

		$resume_token = rgpost( 'resumeToken' );
		$form_id      = rgpost( 'formId' );
		$user_id      = rgpost( 'userId' ); // Used by shortcode, requires 'gravityforms_view_entries' capability.
		$form         = GFAPI::get_form( $form_id );

		$this->delete_resume_token_for_current_user( $resume_token, $form, $user_id );
		delete_transient( self::cache_key_initial_resume_token( $form ) );

		/**
		 * Note that this does not delete the token from the wp_gf_draft_submissions table
		 * because the user may have saved the "Save and Continue" link and want to get to
		 * the draft that way. This essentially ensures that the GPASC functionality does
		 * not interfere with the vanilla "Save and Continue" functionality.
		 */
		return;
	}

	public static function get_ip_identifier( $form ) {
		// use the sha1 of the IP address if the form is configured to prevent IP address storage so that this plugin honors that setting.
		return rgars( $form, 'personalData/preventIP' ) ? sha1( GFFormsModel::get_ip() ) : GFFormsModel::get_ip();
	}

	public static function cache_key_initial_resume_token( $form ) {
		$ip = self::get_ip_identifier( $form );
		return 'gpasc_form_load_resume_token_' . $form['id'] . '_' . $ip;
	}

	public function create_initial_resume_token( $form ) {
		$form_unique_id = GFFormsModel::get_form_unique_id( $form['id'] );
		$ip             = self::get_ip_identifier( $form );
		$source_url     = GFFormsModel::get_current_page_url();
		$source_url     = esc_url_raw( $source_url );
		$cache_key      = self::cache_key_initial_resume_token( $form );
		$existing_token = get_transient( $cache_key );

		if ( $existing_token ) {
			return $existing_token;
		}

		$token = GFFormsModel::save_draft_submission(
			$form,
			array(),
			array(),
			1,
			array(),
			$form_unique_id,
			$ip,
			$source_url,
			''
		);

		$one_day = 60 * 60 * 24;

		set_transient( $cache_key, $token, $one_day );

		return $token;
	}

	public function auto_save_entry_draft( $form, $page_number ) {
		$resume_token   = sanitize_key( rgpost( 'gform_resume_token' ) );
		$entry          = GFFormsModel::get_current_lead();
		$form_unique_id = GFFormsModel::get_form_unique_id( $form['id'] );
		$ip             = self::get_ip_identifier( $form );
		$source_url     = GFFormsModel::get_current_page_url();
		$source_url     = esc_url_raw( $source_url );

		$next_resume_token = GFFormsModel::save_draft_submission(
			$form,
			$entry,
			rgpost( 'gform_field_values' ),
			$page_number,
			rgar( GFFormsModel::$uploaded_files, $form['id'] ),
			$form_unique_id,
			$ip,
			$source_url,
			$resume_token
		);

		/**
		 * If $resume_token exists AND is different than $next_resume_token, then we know that
		 * the form was submitted while this auto save request was in flight. In this case, we
		 * want to scrap the newly created draft and return null to signal that no draft was
		 * saved.
		 */
		if ( $resume_token && $next_resume_token !== $resume_token ) {
			GFFormsModel::delete_draft_submission( $next_resume_token );
			return null;
		}

		return $next_resume_token;
	}

	/**
	 * Query the draft submission table for a row by a given resume token (e.g. the "uuid" column of the table).
	 */
	public function get_draft_entry_by_resume_token( $resume_token ) {
		global $wpdb;

		$table = version_compare( GFFormsModel::get_database_version(), '2.3-dev-1', '<' )
			? GFFormsModel::get_incomplete_submissions_table_name()
			: GFFormsModel::get_draft_submissions_table_name();

		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$res = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE uuid = %s", $resume_token ), ARRAY_A );
		return $res;
	}

	/**
	 * Summary of generate_draft_tokens_markup
	 *
	 * @param int $form_id The id of the form to get drafts for.
	 * @param string $link_form_path (optional) The path to the form to link to. If null, the "New Draft Button" will not be displayed
	 * @param string $title (optional) The title to display above the form.
	 *
	 * @return string
	 */
	public function generate_draft_tokens_markup( $form_id, $link_form_path = null, $title = null, $user_id = null ) {
		$token_data  = $this->get_current_user_resume_tokens( $form_id, $user_id );
		$token_count = count( $token_data );

		if ( $token_count == 0 ) {
			return '';
		}

		if ( ! $title ) {
			$title = esc_html__( 'Drafts', 'gp-advanced-save-and-continue' );
		}

		/**
		 * Filters the Drafts section title
		 *
		 * @param string $title   The title of the Drafts section title.
		 * @param int    $form_id The ID of the form the draft is for.
		 *
		 * @since 1.0.16
		 */
		$title = gf_apply_filters( array( 'gpasc_draft_title', $form_id ), $title, $form_id );

		$markup = '';

		$current_draft_token = rgget( 'gf_token' );
		$current_draft_token = $current_draft_token ? $current_draft_token : rgpost( 'gform_resume_token' );

		foreach ( $token_data as $data ) {
			$is_current_draft = $current_draft_token === $data['token'];

			$token     = $data['token'];
			$form_path = $data['form_path'];

			$display_name = $this->get_draft_display_name( $form_id, $data );
			$form_path    = add_query_arg( 'gf_token', $token, $form_path );

			$list_item_class = 'gpasc-draft-link';
			if ( $is_current_draft ) {
				$list_item_class .= ' gpasc-current-draft';
			}

			$icon_markup   = '<span class="dashicons dashicons-trash gpasc-icon-margin-left"></span>';
			$link_markup   = sprintf( $is_current_draft ? '%2$s' : '<a href="%1$s">%2$s</a>', $form_path, $display_name );
			$button_markup = sprintf( '<button class="gpasc-delete-draft-button" data-gpasc-resume-token="%s">%s</button>', $token, $icon_markup );
			$markup       .= '<li class="' . $list_item_class . '"><span class="gpasc-draft-link-content">' . $link_markup . $button_markup . '</span></li>';
		}

		$new_draft_button = '';
		$form             = GFFormsModel::get_form( $form_id );

		/**
		 * Allows the form path used in the "New Draft" button to be modified.
		 *
		 * This can be useful when the form is loaded with an AJAX request.
		 *
		 * @param string $form_path The path to the current form.
		 * @param array  $form      The current form being processed.
		 */
		$form_path = gf_apply_filters( array( 'gpasc_new_draft_form_path', $form_id ), $link_form_path, $form );

		if ( $form_path ) {
			$form_path        = html_entity_decode( $form_path );
			$form_path        = add_query_arg( 'gpasc_new_draft', 'true', $form_path );
			$text             = $this->get_new_draft_link_text( $form );
			$new_draft_button = sprintf(
				'<button class="gpasc-new-draft-button" onclick="location.href=\'%s\'">%s</button>',
				$form_path,
				$text
			);
		}

		$data_user_id_attr = $user_id ? sprintf( ' data-user-id="%d"', $user_id ) : '';

		return sprintf( '<div class="gpasc-drafts"><h4>%s</h4><ul id="%s" class="gpasc-draft-links"%s>%s</ul>%s</div>', $title, 'gpasc_resume_token_list_' . $form_id, $data_user_id_attr, $markup, $new_draft_button );
	}

	public function get_draft_display_name( $form_id, $resume_token_data ) {
		$date_format      = get_option( 'date_format' );
		$time_format      = get_option( 'time_format' );
		$date_time_format = "{$date_format} {$time_format}";

		$display_name = wp_date( $date_time_format, $resume_token_data['updated_at'] );

		/**
		 * Filters the display name of a draft.
		 *
		 * @param string $display_name      The display name of the draft.
		 * @param int    $form_id           The ID of the form the draft is for.
		 * @param array  $resume_token_data The data for the draft.
		 *
		 * @since 1.0-beta-1
		 */
		$filtered_display_name = gf_apply_filters( array( 'gpasc_draft_display_name', $form_id ), $display_name, $form_id, $resume_token_data );

		if ( ! empty( $filtered_display_name ) ) {
			$display_name = $filtered_display_name;
		}

		return $display_name;
	}

	public function maybe_display_inline_confirmation( $args ) {
		$form              = GFAPI::get_form( $args['form_id'] );
		$draft_value_saved = rgars( GFFormDisplay::$submission, "{$args['form_id']}/saved_for_later" );

		if ( $this->settings->should_show_inline_confirmation( $form ) && $draft_value_saved ) {
			// Previously we unset the entire $submission, but this caused the form to always reset to the first page.
			// This approach posts the form back to the same page.
			GFFormDisplay::$submission[ $args['form_id'] ]['saved_for_later']      = false;
			GFFormDisplay::$submission[ $args['form_id'] ]['confirmation_message'] = false;
			add_filter( 'gform_get_form_filter_' . $args['form_id'], array( $this, 'prepend_inline_confirmation' ), 10, 2 );
		}

		return $args;
	}

	public function prepend_inline_confirmation( $markup, $form ) {
		$confirmation = wp_filter_object_list( $form['confirmations'], array( 'event' => 'form_saved' ) );
		$confirmation = reset( $confirmation );

		$resume_token = null;
		if ( is_user_logged_in() ) {
			$resume_token = Authenticated_User::get_most_recent_resume_token( $form['id'] );
		} else {
			/**
			 * TODO this doesn't work on the very first "Save and Continue" click of an un-authenticated session
			 * as demonstrated here: https://www.loom.com/share/d32f83528449479a9ce2c05ba8a4a27b?highlightComment=9841517&t=84
			 */
			$resume_token = Unauthenticated_User::get_most_recent_resume_token( $form['id'] );
		}

		$message = GFCommon::maybe_sanitize_confirmation_message( $confirmation['message'] );
		$message = GFFormDisplay::replace_save_variables( $message, $form, $resume_token, rgpost( 'gform_resume_email' ) ?? null );
		$message = GFCommon::gform_do_shortcode( $message );
		$message = sprintf( "<div class='gf_browser_chrome gform_wrapper gpasc-inline-confirmation'><div class='form_saved_message'><span>%s</span></div></div>", $message );

		/**
		 * Filter how the inline confirmation message is attached to the form.
		 *
		 * @since 1.0.13
		 *
		 * @param string $attached The complete form markup with the inline confirmation attached. Defaults to `null`. If `null`, the inline confirmation will be prepended to the form markup by default.
		 * @param string $message  The inline confirmation message.
		 * @param string $markup   The form markup.
		 * @param array  $form     The current form.
		 */
		$attached = gf_apply_filters( array( 'gpasc_attach_inline_confirmation_message', $form['id'] ), null, $message, $markup, $form );
		if ( $attached ) {
			return $attached;
		}

		return $markup . $message;
	}

	public function maybe_remove_save_and_continue_button( $button_markup, $form ) {
		if ( $this->settings->hide_save_and_continue_link( $form ) ) {
			return '';
		}

		return $button_markup;
	}

	public function maybe_add_draft_links_markup_to_form( $form_markup, $form ) {
		if ( ! $this->is_applicable_form( $form ) || ! $this->settings->display_available_drafts_above_form( $form ) ) {
			return $form_markup;
		}

		$url_parts = parse_url( $_SERVER['REQUEST_URI'] );
		$form_path = $url_parts['path'];
		$query_str = rgar( $url_parts, 'query', null );

		if ( $query_str ) {
			$form_path .= '?' . $query_str;
		}

		$form_path = remove_query_arg( 'gf_token', $form_path );

		return $this->generate_draft_tokens_markup( $form['id'], $form_path ) . $form_markup;
	}

	public function add_auto_load_notice_markup( $form_markup, $form ) {
		if ( ! $this->is_applicable_form( $form ) ) {
			return $form_markup;
		}

		$all_resume_token_data = $this->get_current_user_resume_tokens( $form['id'] );
		if ( empty( $all_resume_token_data ) ) {
			return $form_markup;
		}

		$resume_token_data = null;

		foreach ( $all_resume_token_data as $data ) {
			if ( $data['token'] === $_GET['gf_token'] ) {
				$resume_token_data = $data;
				break;
			}
		}

		if ( $resume_token_data === null ) {
			return $form_markup;
		}

		$display_name = $this->get_draft_display_name( $form['id'], $resume_token_data );

		// translators: placeholder is a draft name
		$message = sprintf( esc_html__( 'You are resuming your draft "%s".', 'gp-advanced-save-and-continue' ), $display_name );

		/**
		 * Filters the resume notice for a draft.
		 *
		 * @param string $message           The resume notice.
		 * @param array  $form              The current form.
		 * @param string $display_name      The display name of the draft.
		 * @param array  $resume_token_data The data for the draft.
		 *
		 * @since 1.0-beta-1
		 */
		$message = gf_apply_filters( array( 'gpasc_resume_notice_message', $form['id'] ), $message, $form, $display_name, $resume_token_data );

		$new_draft_link = '';
		if ( ! $this->settings->display_available_drafts_above_form( $form ) ) {
			$new_draft_path = $_SERVER['REQUEST_URI'];
			$new_draft_link = remove_query_arg( 'gf_token', $new_draft_path );
			$new_draft_link = add_query_arg( 'gpasc_new_draft', 'true', $new_draft_link );

			$new_draft_link_text = esc_html__( 'Start new draft', 'gp-advanced-save-and-continue' );

			$new_draft_link_text = $this->get_new_draft_link_text( $form );

			$new_draft_link = $new_draft_link_text ? sprintf( '<a href="%s">%s</a>', $new_draft_link, $new_draft_link_text ) : '';
		}

		$new_markup = sprintf( '<div id="gpasc-auto-load-notice" class="gpasc-auto-load-notice warning"><p>%s %s</p></div>', $message, $new_draft_link );

		return $new_markup . $form_markup;
	}

	public function get_new_draft_link_text( $form ) {
		$new_draft_link_text = esc_html__( 'Start new draft', 'gp-advanced-save-and-continue' );
		$form                = (array) $form;
		/**
		 * Filters the text for the new draft link.
		 *
		 * @param string $new_draft_link_text The text for the new draft link.
		 * @param array $form_id The current form.
		 *
		 * @since 1.0-beta-1
		 */
		return gf_apply_filters( array( 'gpasc_new_draft_link_text', $form['id'] ), $new_draft_link_text, $form['id'] );
	}


	public function generate_shortcode_copier_html( $form ) {
		$input = sprintf( '<input id="gpasc-copy-shortcode-text" type="text" value="[gpasc_drafts form_id=&quot;%s&quot;]" disabled="">', $form['id'] );

		$copy_button_icon = '<span class="dashicons dashicons-clipboard"></span>';
		$copy_button_text = sprintf( '<span class="gpasc-shortcode-copy-button-text">%s</span>', esc_html__( 'Copy Shortcode', 'gp-advanced-save-and-continue' ) );

		$copy_button = sprintf( '<button id="gpasc-copy-shortcode-button" class="button button-secondary">%s%s</button>', $copy_button_icon, $copy_button_text );

		$markup = "<div class=\"gpasc-shortcode-copier-wrapper\">{$input}{$copy_button}</div>";
		return $markup;
	}

	public function tooltips( $tooltips ) {
		return $tooltips;
	}
}

function gp_advanced_save_and_continue() {
	return GP_Advanced_Save_And_Continue::get_instance();
}

GFAddOn::register( 'GP_Advanced_Save_And_Continue' );
