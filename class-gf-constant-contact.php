<?php

GFForms::include_feed_addon_framework();

class GF_Constant_Contact extends GFFeedAddOn {

	/**
	 * @var string Version number of the Add-On
	 */
	protected $_version = GF_CONSTANT_CONTACT_VERSION;

	/**
	 * @var string Gravity Forms minimum version requirement
	 */
	protected $_min_gravityforms_version = '1.9.14.26';

	/**
	 * @var string URL-friendly identifier used for form settings, add-on settings, text domain localization...
	 */
	protected $_slug = 'gravity-forms-constant-contact';

	/**
	 * @var string Relative path to the plugin from the plugins folder. Example "gravityforms/gravityforms.php"
	 */
	protected $_path = 'gravity-forms-constant-contact/constantcontact.php';

	/**
	 * @var string Full path the the plugin. Example: __FILE__
	 */
	protected $_full_path = __FILE__;

	/**
	 * @var string URL to the Gravity Forms website. Example: 'http://www.gravityforms.com' OR affiliate link.
	 */
	protected $_url = 'https://katz.si/gravityforms';

	/**
	 * @var string Title of the plugin to be used on the settings page, form settings and plugins page. Example: 'Gravity Forms MailChimp Add-On'
	 */
	protected $_title = 'Gravity Forms Constant Contact Add-On';

	/**
	 * @var string Short version of the plugin title to be used on menus and other places where a less verbose string is useful. Example: 'MailChimp'
	 */
	protected $_short_title = 'Constant Contact';

	/**
     * @var CC_GF_SuperClass API wrapper for the Constant Contact API
     */
	protected $api = NULL;

	/**
     * @var GF_Constant_Contact The one true instance of this class
     */
	private static $_instance = NULL;

	/* Permissions */
	protected $_capabilities_settings_page = array( 'manage_options', 'gravityforms_constantcontact' );
	protected $_capabilities_form_settings = array( 'manage_options', 'gravityforms_constantcontact' );
	protected $_capabilities_uninstall = 'gravityforms_constantcontact_uninstall';

	/* Members plugin integration */
	protected $_capabilities = array( 'gravityforms_constantcontact', 'gravityforms_constantcontact_uninstall' );


	/**
	 * Get instance of this class.
	 *
	 * @access public
	 * @static
	 * @return GF_Constant_Contact
	 */
	public static function get_instance() {

		if ( self::$_instance == NULL ) {
			self::$_instance = new self;
		}

		return self::$_instance;
	}

	/**
	 * Register needed plugin hooks and PayPal delayed payment support.
	 *
	 * @access public
	 * @return void
	 */
	public function init() {

		parent::init();

		$this->add_delayed_payment_support(
			array(
				'option_label' => esc_html__( 'Subscribe contact to Constant Contact only when payment is received.', 'gravity-forms-constant-contact' ),
			)
		);

	}

	/**
	 * @return string Feed list title, with HTML of Constant Contact logo
	 */
	public function feed_list_title() {
		return $this->plugin_settings_icon() . parent::feed_list_title();
	}

	/**
	 * @return string Feed settings title, with HTML of Constant Contact logo
	 */
	public function feed_settings_title() {
	    return $this->plugin_settings_icon() . parent::feed_settings_title();
    }

	/**
	 * @return string HTML of Constant Contact logo
	 */
	public function plugin_settings_icon() {

	    ob_start();

	    ?>
        <div><a href='https://katz.si/6p' target='_blank' style="background: transparent url(<?php echo self::get_base_url() ?>/images/ctct_logo_600x90.png) left top no-repeat; background-size: contain; width:300px; height:45px; display:block; text-indent: -9999px; overflow:hidden; direction: ltr; margin: 0 0 1em;">Constant Contact</a></div>
        <?php

        $logo = ob_get_clean();

        return $logo;
    }

	/**
	 * Migrate the old feed structure to the new one
     *
     * @since 3.0
     *
     * @return void
	 */
	public function upgrade( $previous_version = '' ) {
		global $wpdb;

		$already_migrated = get_option( 'gravityforms_cc_migrated' );

		// Already processed
		if ( ! empty( $already_migrated ) ) {
			return;
		}

		//
		// First, encrypt the login and password
        //
		$settings = get_option( 'gf_constantcontact_settings' );

		if ( ! empty( $settings ) && empty( $settings['encrypted'] ) ) {

		    $settings = $this->update_plugin_settings( $settings );
		}

        //
        // Next, migrate old feeds
        //
		$old_addon_table_name = $wpdb->prefix . "rg_constantcontact";
        $form_table_name = GFFormsModel::get_form_table_name();

        $sql = "SELECT s.is_active, s.form_id, s.meta
            FROM $old_addon_table_name s
            INNER JOIN $form_table_name f ON s.form_id = f.id";

        // Prevent "table doesn't exist" error
        $suppress_errors_backup = $wpdb->suppress_errors;

		$wpdb->suppress_errors = true;

		$old_feeds = $wpdb->get_results( $sql, ARRAY_A );

        if( $old_feeds ) {

            // Migrate old feed to new feed strucutre
	        foreach ( $old_feeds as $old_feed ) {

		        $meta = maybe_unserialize( $old_feed['meta'] );

		        // Didn't use to have feed names
		        $meta['feed_name'] = $this->get_default_feed_name();

		        // Single list (string endpoint URL) => array of list IDs
		        $meta['lists'] = array(
			        $this->get_list_short_id( rgar( $meta, 'contact_list_id' ) )
		        );

		        // Update fields
		        foreach ( (array) rgar( $meta, 'field_map' ) as $key => $field_id ) {
			        $meta["fields_{$key}"] = $field_id;
			        unset( $meta['field_map'][ $key ] );
		        }

		        // Opt-in enabled
		        $meta['feed_condition_conditional_logic'] = (int) rgar( $meta, 'optin_enabled' );

		        $conditional_logic = array(
			        'actionType' => 'show',
			        'logicType'  => 'all',
			        'rules'      => array(
				        array(
					        'fieldId'  => rgar( $meta, 'optin_field_id' ),
					        'operator' => rgar( $meta, 'optin_operator' ),
					        'value'    => rgar( $meta, 'optin_value' ),
				        ),
			        ),
		        );

		        $meta['feed_condition_conditional_logic_object'] = array(
			        'conditionalLogic' => GFFormsModel::sanitize_conditional_logic( $conditional_logic ),
		        );

		        unset( $meta['id'], $meta['optin_enabled'], $meta['optin_field_id'], $meta['optin_operator'], $meta['optin_value'], $meta['field_map'], $meta['contact_list_id'], $meta['contact_list_name'] );

		        $feed_id = $this->insert_feed( $old_feed['form_id'], $old_feed['is_active'], $meta );

		        $this->log_debug( __METHOD__ . ': Migrated feed #' . $feed_id );
	        }
        } else {

	         $this->log_debug( __METHOD__ . ': No old feeds to migrate' );

        }

        // Then delete the old feeds table
        $dropped = $wpdb->query( "DROP TABLE IF EXISTS " . $old_addon_table_name );

        if ( ! $dropped ) {

            $this->log_error( __METHOD__ . ': Was not able to drop old addon table from DB' );

        } else {

            $this->log_debug( __METHOD__ . ': Successfully cleaned up old DB table' );

            add_option( 'gravityforms_cc_migrated', true );
        }

		// Restore previous setting
		$wpdb->suppress_errors = $suppress_errors_backup;
	}

	/**
	 * Convert CC endpoint id into a number id to be used on forms (avoid issues with more strict servers)
	 *
	 * @access public
	 * @static
	 * @param mixed $endpoint
	 * @return false|int $id
	 */
	public function get_list_short_id( $endpoint ) {

		if( empty( $endpoint ) ) {
			return false;
		}

		// Already short
		if ( is_numeric( $endpoint ) ) {
			return (int) $endpoint;
		}

		// Return the last characters; they are the list ID
		if( false !== ( $pos = strrpos( rtrim( $endpoint, '/' ), '/' ) ) ) {
			return (int) trim( substr( $endpoint, $pos + 1 ) );
		}

		// Nothing was valid
		return false;
	}

	/**
	 * Prepare settings to be rendered on plugin settings tab.
	 *
	 * @access public
	 * @return array
	 */
	public function plugin_settings_fields() {

		return array(
			array(
				'title'       => '',
				'description' => $this->plugin_settings_description(),
				'fields'      => array(
					array(
						'name'              => 'username',
						'label'             => __( 'Constant Contact Username', 'gravity-forms-constant-contact' ),
						'type'              => 'text',
						'class'             => 'medium',
						'error_message'     => __('The username and password combo provided is not valid', 'gravity-forms-constant-contact' ),
						'validation_callback' => array( $this, 'validate_api_settings' ),
                        'feedback_callback' => array( $this, 'feedback_api_settings' ),
					),
					array(
						'name'              => 'password',
						'label'             => __( 'Constant Contact Password', 'gravity-forms-constant-contact' ),
						'type'              => 'text',
						'input_type'        => 'password',
						'class'             => 'medium',
						'error_message'     => __('The username and password combo provided is not valid', 'gravity-forms-constant-contact' ),
						'validation_callback' => array( $this, 'validate_api_settings' ),
						'feedback_callback' => array( $this, 'feedback_api_settings' ),
					),
					array(
						'type'     => 'save',
						'messages' => array(
							'success' => esc_html__('Valid username and password. Configure integrations by going to Forms, click on a form, click Settings, then click Constant Contact.', 'gravity-forms-constant-contact'),
							'error' => esc_html__("Invalid username / password combo. Please try another combination. Please note: spaces in your username are not allowed. You can change your username in \"My Account\" in Constant Contact, and this may remedy the problem.", "gravity-forms-constant-contact"),
						),
					),
				),
			),
		);

	}

	/**
	 * Prepare plugin settings description.
	 *
	 * @access public
	 * @return string $description
	 */
	public function plugin_settings_description() {

		ob_start();
		?>
        <h2><?php printf( __( "If you don't have a Constant Contact account, you can %ssign up for one here%s.", 'gravity-forms-constant-contact' ), "<a href='https://katz.si/6p' target='_blank'>", "</a>" ); ?></h2>
        <p style="text-align: left; font-size:1.2em; line-height:1.4">
			<?php _e( "Constant Contact makes it easy to send email newsletters to your customers, manage your subscriber lists, and track campaign performance. Use Gravity Forms to collect customer information and automatically add them to your Constant Contact subscriber list.", "gravity-forms-constant-contact" ); ?>
        </p>
        <h4 class="gaddon-section-title gf_settings_subgroup_title"><?php esc_html_e( "Constant Contact Account Information", "gravity-forms-constant-contact" ); ?></h4>
		<?php
		return ob_get_clean();
	}

	/**
	 * Prepare settings to be rendered on feed settings tab.
	 *
	 * @access public
	 * @return array $fields - The feed settings fields
	 */
	public function feed_settings_fields() {

		$settings = array(
			array(
				'title'  => '',
				'fields' => array(
					array(
						'name'     => 'feed_name',
						'label'    => __( 'Name', 'gravity-forms-constant-contact' ),
						'type'     => 'text',
						'class'    => 'medium',
						'required' => true,
						'tooltip'  => '<h6>' . __( 'Name', 'gravity-forms-constant-contact' ) . '</h6>' . __( 'Enter a feed name to uniquely identify this setup.', 'gravity-forms-constant-contact' ),
					),
					array(
						'name'     => 'lists[]',
						'label'    => esc_html__( 'Constant Contact Lists', 'gravity-forms-constant-contact' ) .' (<a href="'. esc_url( add_query_arg( array( 'cache' => 0 ) ) ) . '">'. esc_html__('Refresh Lists', 'gravity-forms-constant-contact' ) . '</a>)',
						'type'     => 'select',
						'class'    => 'chosen',
						'after_select' => "<script>
                        jQuery(document).ready( function($) {
                        	if( jQuery().chosen ) {
                                $('select[multiple].chosen').chosen();
                            }
                        });
                        </script>",
						'multiple' => true,
						'required' => true,
						'choices'  => $this->lists_for_feed_setting(),
						'tooltip'  => '<h6>' . __( 'Constant Contact Lists', 'gravity-forms-constant-contact' ) . '</h6>' . __( 'Select which Constant Contact list this feed will add contacts to.', 'gravity-forms-constant-contact' ),
					),
					array(
						'name'      => 'fields',
						'label'     => __( 'Map Fields', 'gravity-forms-constant-contact' ),
						'type'      => 'field_map',
						'field_map' => $this->fields_for_feed_mapping(),
						'tooltip'   => '<h6>' . __( 'Map Fields', 'gravity-forms-constant-contact' ) . '</h6>' . __( 'Select which Gravity Form fields pair with their respective Constant Contact fields.', 'gravity-forms-constant-contact' ),
					),
					array(
						'name'    => 'custom_fields',
						'label'   => __( 'Custom Fields', 'gravity-forms-constant-contact' ),
						'type'    => 'dynamic_field_map',
						'disable_custom' => true,
						'tooltip' => '<h6>' . __( 'Custom Fields', 'gravity-forms-constant-contact' ) . '</h6>' . __( 'Select or create a new custom Constant Contact field to pair with Gravity Forms fields. Any non-alphanumeric characters in custom field names will be converted to underscores. If multiple custom fields use the same name, only the last one using the same name will be exported to Constant Contact.', 'gravity-forms-constant-contact' ),
                        'field_map' => $this->custom_fields_for_feed_mapping(),
					),
					array(
						'name'           => 'feedCondition',
						'label'          => __( 'Opt-In Condition', 'gravity-forms-constant-contact' ),
						'type'           => 'feed_condition',
						'checkbox_label' => __( 'Enable', 'gravity-forms-constant-contact' ),
						'instructions'   => __( 'Export to Constant Contact if', 'gravity-forms-constant-contact' ),
						'tooltip'        => '<h6>' . __( 'Opt-In Condition', 'gravity-forms-constant-contact' ) . '</h6>' . __( 'When the opt-in condition is enabled, form submissions will only be exported to Constant Contact when the condition is met. When disabled, all form submissions will be exported.', 'gravity-forms-constant-contact' ),

					),
				),
			),
		);

		return $settings;

	}

	private function custom_fields_for_feed_mapping() {

		$field_map = array();

	    $i = 1;
	    while( $i <= 15 ) {
		    $field_map[] = array(
			    'name'       => 'custom_field_' . $i,
			    'value'      => 'custom_field_' . $i,
			    'label'      => sprintf( __( 'Custom Field %d (Up to 50 characters)', 'gravity-forms-constant-contact' ), $i ),
		    );
	        $i++;
        }

		return $field_map;
    }

	/**
	 * Prepare fields for field mapping feed settings field.
	 *
	 * @access public
	 * @return array $field_map
	 */
	public function fields_for_feed_mapping() {

		/* Setup initial field map */
		$field_map = array(
			array(
				'name'       => 'email_address',
				'label'      => __( 'Email Address', 'gravity-forms-constant-contact' ),
				'required'   => true,
				'field_type' => array( 'email' ),
			),
			array(
				'name'     => 'first_name',
				'label'    => __( 'First Name', 'gravity-forms-constant-contact' ),
				'field_type' => array( 'name', 'text' ),
			),
			array(
				'name'     => 'middle_name',
				'label'    => __( 'Middle Name', 'gravity-forms-constant-contact' ),
				'required' => false,
				'field_type' => array( 'name', 'text' ),
			),
            array(
				'name'     => 'last_name',
				'label'    => __( 'Last Name', 'gravity-forms-constant-contact' ),
				'required' => false,
				'field_type' => array( 'name', 'text' ),
			),
			array(
				'name'     => 'job_title',
				'label'    => __( 'Job Title', 'gravity-forms-constant-contact' ),
				'required' => false,
			),
			array(
				'name'     => 'company_name',
				'label'    => __( 'Company Name', 'gravity-forms-constant-contact' ),
				'required' => false,
			),
			array(
				'name'     => 'home_number',
				'label'    => __( 'Home Phone Number', 'gravity-forms-constant-contact' ),
				'required' => false,
				'field_type' => array( 'phone', 'text' ),
			),
			array(
				'name'     => 'work_number',
				'label'    => __( 'Work Phone Number', 'gravity-forms-constant-contact' ),
				'required' => false,
				'field_type' => array( 'phone', 'text' ),
			),
			array(
				'name'     => 'address_line_1',
				'label'    => __( 'Address', 'gravity-forms-constant-contact' ),
				'required' => false,
				'field_type' => array( 'address', 'text' ),
			),
			array(
				'name'     => 'address_line_2',
				'label'    => __( 'Address 2', 'gravity-forms-constant-contact' ),
				'required' => false,
				'field_type' => array( 'address', 'text' ),
			),
			array(
				'name'     => 'address_line_3',
				'label'    => __( 'Address 3', 'gravity-forms-constant-contact' ),
				'required' => false,
				'field_type' => array( 'address', 'text' ),
			),
			array(
				'name'     => 'city_name',
				'label'    => __( 'City', 'gravity-forms-constant-contact' ),
				'required' => false,
				'field_type' => array( 'address', 'text' ),
			),
			array(
				'name'     => 'state_name',
				'label'    => __( 'State', 'gravity-forms-constant-contact' ),
				'required' => false,
				'field_type' => array( 'address', 'text' ),
			),
			array(
				'name'     => 'zip_code',
				'label'    => __( 'ZIP Code', 'gravity-forms-constant-contact' ),
				'required' => false,
				'field_type' => array( 'address', 'text' ),
			),
			array(
				'name'     => 'country_name',
				'label'    => __( 'Country', 'gravity-forms-constant-contact' ),
				'required' => false,
				'field_type' => array( 'address', 'text' ),
			),
		);

		return $field_map;

	}

	/**
	 * Configures which columns should be displayed on the feed list page.
	 *
	 * @return array
	 */
	public function feed_list_columns() {

		return array(
			'feed_name' => __( 'Name', 'gravity-forms-constant-contact' ),
			'lists'      => __( 'Constant Contact Lists', 'gravity-forms-constant-contact' ),
		);

	}

	/**
	 * Set feed creation control.
	 *
	 * @access public
	 * @return bool
	 */
	public function can_create_feed() {
		return $this->initialize_api();
	}

	/**
	 * Enable feed duplication.
	 *
	 * @access public
	 *
	 * @param int $feed_id
	 *
	 * @return bool
	 */
	public function can_duplicate_feed( $feed_id ) {

		return true;

	}

	/**
	 * Returns the value to be displayed in the list name column.
	 *
	 * @access public
	 *
	 * @param array $feed The feed being included in the feed list.
	 *
	 * @return string
	 */
	public function get_column_value_lists( $feed ) {

		/* If Constant Contact instance is not initialized, return campaign ID. */
		if ( ! $this->initialize_api() ) {
			return $feed['meta']['lists'];
		}

		$feed_list_ids = rgars( $feed, 'meta/lists' );

		/* Get campaign and return name */
		$lists = $this->get_cc_lists( $feed_list_ids );

		if( empty( $lists ) ) {
		    return $feed_lists;
        }

        $output = '<ul class="description-list">';
		foreach ( $lists as $list ) {
			$output .= '<li>' . rgar( $list, 'title' ) . '</li>';
		}
		$output .= '</ul>';

		return $output;

	}

	/**
	 * Given a list short id (just the numeric part) return the list details (endpoint and title)
	 *
	 * @param int[] $list_ids ID of the list, or full endpoint URL
	 *
	 * @return false|array Array with id, title keys, or false if list not found.
	 */
	public function get_cc_lists( $list_ids = array() ) {

		if ( empty( $list_ids ) ) {
			$this->log_error( __METHOD__ .': List is empty' );
			return false;
		}

		$this->log_error( __METHOD__ .': Fetching lists' );

		$lists = $this->api->lists();

        if ( ! $lists ) {
            $this->log_error( __METHOD__ . ': Lists not loaded' );

            return false;
        }

		$return = array();

		foreach ( $lists as $list ) {

			foreach( $list_ids as $list_id ) {
				if ( intval( $list_id ) === intval( $list['id'] ) || intval( $list_id ) === $this->get_list_short_id( $list['id'] ) ) {
					$return[] = $list;
				}
			}
		}

		if( ! empty( $return ) ) {
			return $return;
		}

		$this->log_error( __METHOD__ . sprintf( ': List with list ID of %s not found', print_r( $list_id, true ) ) );

		return false;
	}

	/**
	 * Prepare Constant Contact lists for feed settings field.
	 *
	 * @access public
	 * @return array $choices - An array of Constant Contact lists formatted for select settings field.
	 */
	public function lists_for_feed_setting() {

		/* If Constant Contact API instance is not initialized, return an empty array. */
		if ( ! $this->initialize_api() ) {
			return array();
		}

		/* Get the lists */
		$lists   = $this->api->lists();
		$choices = array();

		/* Add lists to the choices array */
		if ( ! empty( $lists ) ) {

			foreach ( $lists as $list ) {

				$choices[] = array(
					'label' => esc_html( $list['title'] ),
					'value' => $this->get_list_short_id( $list['id'] ),
				);

			}

		}

		return $choices;
	}

	/**
	 * Processes the feed, subscribes the user to the list.
	 *
	 * @access public
	 *
	 * @param array $feed The feed object to be processed.
	 * @param array $entry The entry object currently being processed.
	 * @param array $form The form object currently being processed.
	 *
	 * @return array|null|false|WP_Error False: List not configured; NULL:
	 */
	public function process_feed( $feed, $entry, $form ) {

		/* If a list is chosen for this feed, add it to the audience member array. */
		$add_list = rgars($feed, 'meta/lists', false );

		if ( ! $add_list ) {

		    $this->log_error( __METHOD__ . '(): List not configured in feed.' );
            return false;
		}

		/* If Constant Contact instance is not initialized, exit. */
		if ( ! $this->initialize_api() ) {

			$this->log_error( __METHOD__ . '(): Constant Contact API not configured; feed not processed.' );

			return $entry;
		}

		$this->log_debug( __METHOD__ . '(): Processing feed.' );

		$subscriber_details = $this->build_subscriber_details( $feed, $entry, $form );

		if ( ! $subscriber_details ) {

		    $this->log_error( __METHOD__ . '(): Subscriber email invalid.' );

			return NULL;
		}

		$subscription_results = $this->subscribe_to_list( $add_list, $subscriber_details );

		if( ! is_wp_error( $subscription_results ) && !empty( $subscription_results ) ) {
			$this->add_note( $entry["id"], __('Successfully added/updated in Constant Contact.', 'gravity-forms-constant-contact' ), 'gravity-forms-constant-contact' );
		} else {

			$error = '';
			/** @var WP_Error $subscription_results */
			if( is_wp_error( $subscription_results ) ) {
				$error = ': '.$subscription_results->get_error_message();
			}

			$this->add_note( $entry["id"], __('Errors when adding/updating in Constant Contact', 'gravity-forms-constant-contact') . $error, 'gravity-forms-constant-contact' );
		}

		return $subscription_results;
	}

	/**
     * Only add note if filter passes
     *
	 * @param int $entry_id
	 * @param string $note
	 * @param null $note_type
	 */
	public function add_note( $entry_id, $note, $note_type = NULL ) {

		/**
         * @since 3.0 Added $entry_id and $note params
		 * @param bool $add_notes Whether to add notes after error/success of adding subscriber
         * @param int $entry_id GF Entry ID
         * @param string $note Note to be added
		 */
		$add_notes = apply_filters( 'gravityforms_constant_contact_add_notes_to_entries', true, $entry_id, $note );

		if ( $add_notes ) {
		    parent::add_note( $entry_id, $note, $note_type );
		}
	}

	/**
     * Subscribe a contact to lists
     *
	 * @param array $list_ids Array of
	 * @param array $passed_subscriber_details
	 *
	 * @return true|WP_Error
	 */
	public function subscribe_to_list( $list_ids = array(), $passed_subscriber_details = array() ) {

		if( ! $this->initialize_api() ) {

		    $this->log_debug( __METHOD__ . ': API not initialized.' );

		    return new WP_Error( 'cc_api', 'Constant Contact API was not initialized properly.' );
		}

	    $subscriber_details = $passed_subscriber_details;

		foreach ( $subscriber_details as $key => $detail ) {

		    $detail = trim( $detail );

			if ( rgblank( $detail ) ) {
				unset( $subscriber_details[ $key ] );
			}
		}

		$subscriber_details['lists'] = (array) $this->fix_lists( $list_ids );

		// Check if email already exists; update if it does
		if ( $existing_id = $this->api->subscriber_exists( $subscriber_details['email_address'] ) ) {

			$this->log_debug( __METHOD__ . ': Subscriber exists in Constant Contact (ID #' . $existing_id );

			// Get the current subscriber data
			$contactInfo = $this->api->get_subscriber_details( $subscriber_details['email_address'] );

			// Merge the lists together
			$subscriber_details['lists'] = array_merge( $subscriber_details['lists'], $contactInfo['lists'] );

			$subscriber_details['lists'] = array_unique( $subscriber_details['lists'] );

			$this->log_debug( __METHOD__ . ': Final customer parameters used to update contact' . print_r( $subscriber_details, true ) );

			$contactXML = $this->api->CC_Contact()->createContactXML( (string) $existing_id, $subscriber_details );

			$return = $this->api->CC_Contact()->editSubscriber( (string) $existing_id, (string) $contactXML );
		} else {

			$this->log_debug( __METHOD__ . ': Subscriber does not exist in Constant Contact' );

			$this->log_debug( __METHOD__ . ': Final customer parameters used to update contact' . print_r( $subscriber_details, true ) );

			$contactXML = $this->api->CC_Contact()->createContactXML( NULL, $subscriber_details );

			$return     = $this->api->CC_Contact()->addSubscriber( "{$contactXML}" );
		}

		return $return;

	}


	/**
     * Convert list IDs to list URLs; set scheme to HTTPS; remove duplicates
     *
	 * @param int[]|string[] $lists Array of list IDs or list URLs
	 *
	 * @return array Array of URLs to the lists
	 */
	public function fix_lists( $lists = array() ) {

		$final_lists = array();

		// Make sure each list is HTTPS
		foreach ( (array) $lists as $key => $list ) {

			if ( is_numeric( $list ) ) {
				$final_lists[ $key ] = sprintf( trailingslashit( $this->api->apiPath ) . 'lists/%d', $list );
			} else {
				$final_lists[ $key ] = set_url_scheme( $list, 'https' );
			}
		}

		$final_lists = array_unique( $final_lists );

		return $final_lists;
	}

	/**
     * Create array of subscriber details using the submitted values
     *
     * Use filter `gravity_forms_constant_contact_email_type` to modify the type of email sent to subscriber
     *
	 * @param array $feed GF Feed
	 * @param array $entry GF Entry
	 * @param array $form GF Form
	 *
	 * @return array|null Array of subscriber details using CTCT keys. NULL if email isn't valid, email isn't provided, subscriber details are empty.
	 */
	function build_subscriber_details( $feed, $entry, $form ) {

	    /* Prepare audience member import array. */
		$subscriber_details = array();

		/* Find all fields mapped and push them to the audience member array. */
		foreach ( $this->get_field_map_fields( $feed, 'fields' ) as $field_name => $field_id ) {

			$field_value = $this->get_field_value( $form, $entry, $field_id );

			if ( ! rgblank( $field_value ) ) {

				$field = GFFormsModel::get_field( $form, $field_id );

				if( 'date_created' === $field_id || ( $field && 'date' === $field->type ) ) {

					/**
                     * Support modifying the date format; backward compatible with 2.x
					 * @param string $field_value Date string to be modified
					 */
					$field_value = apply_filters( 'gravityforms_constant_contact_change_date_format', $field_value );
				}

				/**
                 * If this is an address field and it's the State input, convert to US code. If there's no state code match, the original value is returned.
                 * @see https://github.com/katzwebservices/Gravity-Forms-Constant-Contact-Add-on/issues/6
                 * @var GF_Field_Address $field
                 */
				if( $field && 'address' === $field->type ) {

					$field_value = trim( $field_value );

					if( $field_id === $field->id . '.4' && 'us' === $field->addressType ) {

						$maybe_state_code = $field->get_us_state_code( $field_value );

						// Use state code, if exists. Otherwise, use default `state_name` and existing value
						if( GFCommon::safe_strtoupper( $field_value ) !== $maybe_state_code ) {
							$field_name = 'state_code';
							$field_value = $maybe_state_code;
						}

                    } elseif( $field_id === $field->id . '.6' ) {

					    // Convert country name to country code
						$maybe_country_code = GFCommon::get_country_code( $field_value );

						// If country not matched, then it's not in the GF country list
						if ( ! empty( $maybe_country_code ) ) {
							$field_name = 'country_code';
							$field_value = function_exists( 'mb_strtolower' ) ? mb_strtolower( $maybe_country_code ) : strtolower( $maybe_country_code );
						} else {
                            $field_name = 'country_name';
                        }

                    }
                }


				$subscriber_details[ $field_name ] = $field_value;
			}

		}

		// Having both the country code and name can cause problems; only push the code, if set
		if ( ! empty( $subscriber_details['country_name'] ) && ! empty( $subscriber_details['country_code'] ) ) {
			unset( $subscriber_details['country_name'] );
		}

		/* If email address is empty, return. */
		if ( GFCommon::is_invalid_or_empty_email( rgar( $subscriber_details, 'email_address' ) ) ) {

			$this->log_error( __METHOD__ . '(): Email address not provided or invalid ("' . rgar( $subscriber_details, 'email_address' ) . '")' );

			return NULL;
		}

		/* Push any custom fields to the audience member array. */
		if ( ! empty( $feed['meta']['custom_fields'] ) ) {

			foreach ( $feed['meta']['custom_fields'] as $custom_field ) {

				/* If field map field is not paired to a form field, skip. */
				if ( rgblank( $custom_field['value'] ) ) {
					continue;
				}

				$field_value = $this->get_field_value( $form, $entry, $custom_field['value'] );

				if ( ! rgblank( $field_value ) ) {

					$field_name                     = ( $custom_field['key'] == 'gf_custom' ) ? $custom_field['custom_key'] : $custom_field['key'];
					$subscriber_details[ $field_name ] = $field_value;

				}
			}
		}

		if ( GFCommon::is_empty_array( $subscriber_details ) ) {

			$this->log_error( __METHOD__ . '(): Empty subscriber details');

            return NULL;
		}

		/**
		 * Modify the type of email to be sent to the subscriber
		 * @param string "html" or "text" allowed
		 */
		$subscriber_details['mail_type'] = apply_filters( 'gravity_forms_constant_contact_email_type', 'html' );

		return $subscriber_details;
    }

	/**
	 * Add GravityView promotional message on Settings page
     *
     * @return void
	 */
    public function render_uninstall() {

	    /**
	     * To hide the add, you can add a filter:
	     *
	     * `add_filter('hide_gravityview_promotion-gravity-forms-constant-contact', '__return_true');`
	     *
	     * @param boolean
	     */
	    $hide_promo = apply_filters( 'hide_gravityview_promotion-gravity-forms-constant-contact', class_exists('GravityView_Plugin') );

	    if( empty( $hide_promo ) ) {
		    include_once $this->get_base_path() . '/gravityview-info.php';
	    }

	    parent::render_uninstall();
    }

	/**
	 * Delete settings on uninstall
     *
     * @since 3.0
	 */
	public function uninstall() {
		delete_option( 'gf_constantcontact_migrated' );
		delete_option( 'gf_constantcontact_settings' );
		delete_option( 'gravity_forms_cc_valid_api' );
		delete_option( "gf_constantcontact_version");
    }

	/**
	 * Returns the message that will be displayed if the current version of Gravity Forms is not supported.
	 *
     * @since 3.0
	 */
	public function plugin_message() {

		$message = sprintf( esc_html__( 'Gravity Forms %s is required. Activate it now or %spurchase it today!%s', 'gravityforms' ), $this->_min_gravityforms_version, "<a href='https://katz.si/gravityforms'>", '</a>' );

		return $message;
	}


	/**
     *
	 * @return array
	 */
	public function get_plugin_settings() {

	    // Get the settings directly if submitted. They haven't been encrypted yet.
	    if( $this->is_save_postback() && $this->is_plugin_settings() ) {

	        // The API may no longer be valid; force re-checking
	        delete_transient( 'gravity_forms_cc_valid_api' );

            $settings = $this->get_posted_settings();

	    } else {

		    $settings = get_option( 'gf_constantcontact_settings' );

		    $settings = $this->decrypt( $settings );
	    }

		$settings = array_map( 'trim', (array) $settings );

		return $settings;
	}

	/**
	 * Updates plugin settings with the provided settings
	 *
	 * @param array $settings - Plugin settings to be saved
     *
     * @return array $settings
	 */
	public function update_plugin_settings( $settings ) {

		$settings = $this->encrypt( $settings );

		update_option( 'gf_constantcontact_settings', $settings, false );

		return $settings;
	}

	/**
     * Encrypt settings with support for GFCommon::encrypt as well as GFCommon::openssl_encrypt
     *
     * @since 3.1
     *
	 * @param $settings
	 *
	 * @return array
	 */
	private function encrypt( $settings ) {

	    if(  ! method_exists( 'GFCommon', 'encrypt') && ! method_exists( 'GFCommon', 'openssl_encrypt') ) {
	        return $settings;
        }


	    if( method_exists( 'GFCommon', 'openssl_encrypt') ) {
		    $settings = array_map( array( 'GFCommon', 'openssl_encrypt' ), $settings );
		    $settings['encryption-method'] = 'openssl_encrypt';
	    } else {
		    $settings = array_map( array( 'GFCommon', 'encrypt' ), $settings );
		    $settings['encryption-method'] = 'encrypt';
        }

        $settings['encrypted'] = 1;

	    return $settings;
    }

	/**
	 * Decrypt settings with support for GFCommon::decrypt as well as GFCommon::openssl_decrypt
	 *
	 * @since 3.1
	 *
	 * @param $settings
	 *
	 * @return array
	 */
	private function decrypt( $settings ) {

		if(  ! method_exists( 'GFCommon', 'encrypt') && ! method_exists( 'GFCommon', 'openssl_encrypt') ) {
			return $settings;
		}

		// Not encrypted, so don't decrypt!
		if ( ! isset( $settings['encrypted'] ) ) {
            return $settings;
		}

		$encryption_method = isset( $settings['encryption-method'] ) ? $settings['encryption-method'] : 'encrypt';

		/**
         * No need to decrypt, so unset for now and set again below
         * @see https://wordpress.org/support/topic/errors-on-all-constant-contact-settings-pages/
         */
		unset( $settings['encrypted'], $settings['encryption-method'] );

		switch ( $encryption_method ) {
            case 'openssl_encrypt':
                $settings = array_map( array( 'GFCommon', 'openssl_decrypt' ), $settings );
	            break;

            case 'encrypt':
            default:
                $settings = array_map( array( 'GFCommon', 'decrypt' ), $settings );
                break;
		}

		$settings['encryption-method'] = $encryption_method;
		$settings['encrypted'] = 1;

        return $settings;
	}

	/**
     * Show field feedback (on load, not only on save)
     *
	 * @param $field_value
	 * @param array $field_settings
	 *
	 * @return bool
	 */
	public function feedback_api_settings( $field_value, $field_settings = array() ) {
		return $this->validate_api_settings( $field_settings, $field_value );
    }

	/**
     * Validate saved settings
     *
	 * @param array $field_settings
	 * @param string $field_value
	 *
	 * @return bool
	 */
	public function validate_api_settings( $field_settings = array(), $field_value = '' ) {

	    if( ! $this->initialize_api() ) {
		    $this->set_field_error( $field_settings, rgar( $field_settings, 'error_message' ) );
		    return false;
        }

		return true;
	}

	/**
	 * Initializes Constant Contact API if credentials are valid.
	 *
	 * @access public
	 * @return bool|null
	 */
	public function initialize_api() {

		if ( ! is_null( $this->api ) ) {
			return true;
		}

		/* Get plugin settings */
		$settings = $this->get_plugin_settings();

		/* If the API key or email address is not set, do not run a validation check. */
		if ( empty( $settings['username'] ) || empty( $settings['password'] ) ) {

			delete_transient( 'gravity_forms_cc_valid_api' );

			return NULL;
		}

		$this->log_debug( __METHOD__ . "(): Validating API info for {$settings['username']}." );


		/* Load the API library. */
		if ( ! class_exists( "CC_Utility" ) ) {
			require_once( self::get_base_path() . "/api/cc_class.php" );
			require_once( self::get_base_path(). "/api/class.cc_gf_superclass.php");
		}

		$api = CC_GF_SuperClass::get_instance( $settings['username'], $settings['password'], $this );

		$is_valid_login = $api->is_valid_login();

		if ( ! $api || ! $is_valid_login ) {

			/* Log that test failed. */
			$this->log_error( __METHOD__ . '(): API credentials are invalid.' );

			set_transient( 'gravity_forms_cc_valid_api', '0', DAY_IN_SECONDS );

			return false;
		}

		/* Log that test passed. */
		$this->log_debug( __METHOD__ . '(): API credentials are valid.' );

		/* Assign Constant Contact object to the class. */
		$this->api = $api;

		set_transient( 'gravity_forms_cc_valid_api', '1', DAY_IN_SECONDS );

		return true;

	}

}
