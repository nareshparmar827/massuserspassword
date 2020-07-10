<?php
// If check class exists 'WP_List_Table'
if( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}
// If check class exists 'Mass_users_password_reset'
if ( ! class_exists( 'Mass_users_password_reset' ) ) {

	class Mass_users_password_reset extends WP_List_Table {

		/**
		 * Show per page
		 *
		 * @var $user_per_page integer
		 */
		public $user_per_page;

		/**
		 * Get current user ID
		 * @var $current_user_id integer
		 */
		public $exclude = array();

		/**
		 * Global wpdb
		 */
		public $wpdb;

		/**
		 * Get options
		 *
		 * @var $options
		 */
		private $options;

		/**
		 * Set default setting options
		 * @var $default_option
		 */
		public $default_option = array(
			'mupr_to_send_reset_link' 	=> 'no',
			'from_email'				=> '',
			'from_name'					=> '',
			'subject'					=> '',
			'message'					=> '',
			'mupr_testmode'				=> false,
			'mupr_testmode_email'		=> '',
			'mupr_per_page'				=> 20
		);

		// class construct
		function __construct() {
			global $wpdb;
			// Global $wpdb;
			$this->wpdb = $wpdb;
			// Options
			$option = get_option( 'mupr_email_options' );
			// Set default option using wp_parse_args function
			$this->options = wp_parse_args( $option, $this->default_option );
			// Set per page options
			$this->user_per_page = $this->options['mupr_per_page'];
			// Current user ID
			$this->exclude[] = get_current_user_id();
			// include in admin menu
			add_action( 'admin_menu', array( $this, 'mass_users_password_reset_menu' ) );
			if( isset( $_GET['page'] ) && $_GET['page'] == 'mass_users_password_reset_options' ) {
				add_action( 'admin_enqueue_scripts', array( $this, 'mupr_enqueue_scripts' ) );
				add_action( 'admin_footer', array( $this, 'mupr_dialog_box' ) );
				add_action( 'admin_init', array( $this, 'mupr_remove_admin_notice' ) );
			}
			//bulk action in users list page
			add_filter( 'bulk_actions-users', array( $this, 'register_reset_password_bulk_actions' ) );
			//callback for bulk action
			add_filter( 'handle_bulk_actions-users', array( $this, 'reset_password_bulk_action_handler' ), 10, 3 );
			// display notices for single and bulk users reset password
			add_action( 'admin_notices', array( $this, 'reset_password_bulk_action_admin_notice' ) );
			//display single user reset password option
			add_filter( 'user_row_actions', array( $this, 'reset_password_user_row_action' ), 10, 2 );
			//single user reset password
			add_action( 'admin_init', array( $this, 'single_user_reset_password' ) );
		}

		/**
		 * Remove all admin notices.
		 */
		function mupr_remove_admin_notice() {
			remove_all_actions( 'admin_notices' );
		}

		/**
		 * Register 'Reset Password' in bulk action
		 *
		 * @param      array  $bulk_actions  The bulk actions
		 *
		 * @return     array  ( description_of_the_return_value )
		 */
		function register_reset_password_bulk_actions( $bulk_actions ) {
			$bulk_actions['reset_password'] = __( 'Reset Password', 'mass-users-password-reset-pro');
			return $bulk_actions;
		}

		/**
		 * Action taken on selecting 'Reset password' bulk action
		 *
		 * @param      string  $redirect_to  The redirect to
		 * @param      string  $doaction     The doaction
		 * @param      array  $user_ids     The user identifiers
		 *
		 * @return     string  ( return redirect url )
		 */
		function reset_password_bulk_action_handler( $redirect_to, $doaction, $user_ids ) {
			if ( $doaction !== 'reset_password' ) {
				return $redirect_to;
			}
			foreach ( $user_ids as $user_id ) {
				self::send_email_format($user_id);
			}
			$redirect_to = add_query_arg( 'bulk_rp_users', count( $user_ids ), $redirect_to );
			return $redirect_to;
		}

		/**
		 * admin notices display while resetting single as well as multiple users password
		 */
		function reset_password_bulk_action_admin_notice() {
			// If check murp bulk action request
			if ( ! empty( $_REQUEST['bulk_rp_users'] ) ) {
				$rp_users_count = intval( $_REQUEST['bulk_rp_users'] );
				printf( '<div id="message" style="padding:9px 12px;" class="updated">' .
					_n( '%s user password is reset.',
						'%s users password are reset.',
						$rp_users_count,
						'mass-users-password-reset-pro'
						) . '</div>', $rp_users_count );
			}
			// If check current action
			if (isset($_REQUEST['action']) &&  $_REQUEST['action'] == 'mupr_srp' && ! empty( $_REQUEST['user'])){
				$user_meta = get_userdata($_REQUEST['user']);
				printf( '<div id="message" style="padding:9px 12px;" class="updated">'.__('%s password is reset','mass-users-password-reset-pro').'</div>',$user_meta->display_name);
			}
		}

		/**
		 * display single user 'RP' option in users page
		 *
		 * @param      array  $actions      The actions
		 * @param      object $user_object  The user object
		 *
		 * @return     array
		 */
		function reset_password_user_row_action( $actions, $user_object ) {
			if ( current_user_can( 'administrator', $user_object->ID ) ):
				$url = add_query_arg(
					array(
						'action' => 'mupr_srp',
						'user' => $user_object->ID
					)
				);
				$actions['reset_password'] = '<a href="'. esc_url ( $url ) .'">'.__('Reset Password','mass-users-password-reset-pro').'</a>';
			endif;
			return $actions;
		}

		/**
		 * action taken on single user 'RP' selection
		 */
		function single_user_reset_password() {
			if ( isset( $_REQUEST['action'] ) && $_REQUEST['action'] == 'mupr_srp' ) {
				$this->send_email_format( $_REQUEST['user'] );
			}
		}

		/**
		 * Adding plugin's page as submenu to users page
		 */
		function mass_users_password_reset_menu() {
			add_submenu_page( 'users.php', 'Mass Users Password Reset', 'Mass Users Password Reset Pro',
				'activate_plugins', 'mass_users_password_reset_options',
				array( $this, 'mupr_admin_page' )
			);
		}

		/**
		 * Enqueue scripts
		 */
		function mupr_enqueue_scripts() {
			// CSS
			wp_enqueue_style( 'google-montserrat', 'https://fonts.googleapis.com/css?family=Montserrat:300,400,500,600,700' );
			wp_enqueue_style( 'bootstrap-select', MASS_USERS_PASSWORD_RESET_PRO_PLUGIN_URL . 'assets/css/bootstrap-select.css' );
			wp_enqueue_style( 'main-css', MASS_USERS_PASSWORD_RESET_PRO_PLUGIN_URL . 'assets/css/mupr.css' );
			// JS
			// enqueue these scripts and styles before admin_head
			wp_enqueue_script( 'jquery-ui-dialog' );
			// jquery and jquery-ui should be dependencies, didn't check though.
			wp_enqueue_style( 'wp-jquery-ui-dialog' );

			wp_enqueue_script( 'bootstrap-select-script', MASS_USERS_PASSWORD_RESET_PRO_PLUGIN_URL . 'assets/js/bootstrap-select.js', array( 'jquery' ), false, true );
			wp_enqueue_script( 'dropdown-script', MASS_USERS_PASSWORD_RESET_PRO_PLUGIN_URL . 'assets/js/dropdown.js', array( 'jquery' ), false, true );
			wp_enqueue_script( 'mass-users-script', MASS_USERS_PASSWORD_RESET_PRO_PLUGIN_URL . 'assets/js/mupr.js', array( 'jquery' ), false, true );
			// Localize scripts.
			wp_localize_script( 'mass-users-script', 'MUPR',
				array(
					'ajax_url' 						=> admin_url( 'admin-ajax.php' ),
					'send_pwd_link_shortcode' 		=> __( 'Reset Password link of User', 'mass-users-password-reset-pro' ),
					'new_pwd_shortcode' 			=> __( 'New Password', 'mass-users-password-reset-pro' ),
					'woocommerce_reset_pwd_link' 	=> __( 'Woocommerce Reset Password url', 'mass-users-password-reset-pro' ),
					'nonce_error'					=> __( 'Something went wrong please try again', 'mass-users-password-reset-pro' ),
					'dialog_title'					=> __( 'Confirm Reset Password', 'mass-users-password-reset-pro' ),
					'reset_nonce'					=> wp_create_nonce( 'mupr_reset_' . get_current_user_id() ),
					'mupr_filter'					=> wp_create_nonce( 'mupr_filter_' . get_current_user_id()  ),
					'per_page'						=> $this->user_per_page,
					'force_reset_message'			=> wp_sprintf( '<p>%s</p><p>%s</p>', __( 'You are resetting the password of {{%s}} users.', 'mass-users-password-reset-pro' ), __( 'Choose YES to reset.', 'mass-users-password-reset-pro' ) )
				)
			);
		}

		/**
		 * Replacing placeholders with actual user data in sending mail
		 *
		 * @param      object  $user_info     The user information
		 * @param      HTML/string  $message_body  The message body
		 * @param      string  $send_link     The send link
		 *
		 * @return     HTML/string
		 */
		private function replace_user_info_in_mail( $user_info, $message_body, $send_link = 'no' ) {
			$new_password = wp_generate_password( apply_filters( 'mupr_password_length', 8 ), true, false );
			wp_set_password( $new_password, $user_info->ID );
			$str = array(
				'{USER_NAME}',
				'{FIRST_NAME}',
				'{LAST_NAME}',
				'{PROFILE_URL}',
				'{SITE_URL}',
				'{SITE_NAME}',
				'{USER_EMAIL}'
			);
			$replaceWith = array(
				$user_info->user_login,
				$user_info->first_name,
				$user_info->last_name,
				get_admin_url( '','profile.php' ),
				get_site_url(),
				get_bloginfo( 'name' ),
				$user_info->user_email
			);
			if ( $send_link == 'no' ) {
				array_push( $str, '{NEW_PASSWORD}' );
				array_push( $replaceWith, $new_password );
			} elseif ( $send_link == 'yes' ) {
				$user_login = $user_info->user_login;
				$key = get_password_reset_key( $user_info );
				$reseturl = network_site_url( "wp-login.php?action=rp&key=$key&login=" . rawurlencode( $user_login ), 'login' );
				array_push( $str, '{RESET_PASSWORD_URL}' );
				array_push ($replaceWith, $reseturl );
				if ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
					$myaccount_page_id = get_option( 'woocommerce_myaccount_page_id' );
					$lost_password_slug = get_option( 'woocommerce_myaccount_lost_password_endpoint' );
					if ( $myaccount_page_id && $lost_password_slug ) {
						$myaccount_page_url = get_permalink( $myaccount_page_id );
						$woocommerce_forgot_password_url = $myaccount_page_url . $lost_password_slug . '?key=' . $key . '&id=' . $user_info->ID;
						array_push( $str, '{WOO_RESET_PASSWORD_URL}' );
						array_push( $replaceWith,$woocommerce_forgot_password_url );
					}
				}
			}
			return str_replace( $str, $replaceWith, $message_body );
		}

		/**
		 * Sends an email format.
		 *
		 * @param      int  $user_id  The user identifier
		 *
		 * @return     boolen  ( return wp_mail response )
		 */
		function send_email_format( $user_id ) {
			$custom_email = $this->options;
			$user_info = get_userdata( $user_id );
			$email_content = $this->replace_user_info_in_mail( $user_info,$custom_email['message'], $custom_email['mupr_to_send_reset_link'] );

			if ( isset( $custom_email['mupr_testmode'] ) && $custom_email['mupr_testmode'] == true ) {
				$to = isset( $custom_email['mupr_testmode_email'] ) && ! empty( $custom_email['mupr_testmode_email'] ) ? $custom_email['mupr_testmode_email'] : $user_info->user_email;
			} else {
				$to = $user_info->user_email;
			}

			$subject = sanitize_text_field( $custom_email['subject'] );
			$body = $email_content;
			// To send HTML mail, the Content-type header must be set
			// Additional headers
			$headers = array(
				'Content-Type: text/html; charset=UTF-8',
				'From: '.sanitize_text_field( $custom_email['from_name'] ) . ' <'.sanitize_text_field($custom_email['from_email']).'>'
			);
			$result = wp_mail( $to, $subject, $body, $headers );
			return $result;
		}

		/**
		 * Display main page
		 */
		function mupr_admin_page() {
			require_once plugin_dir_path( __FILE__ ) . '../admin/template/mupr-admin.php';
		}

		/**
		 * User role filter
		 */
		private function mupr_user_role_filter() {
			$filter = '';
			$current_role = isset( $_REQUEST['role_filter'] ) ? $_REQUEST['role_filter'] : '';
			foreach ( get_editable_roles() as $role_name => $role_info ) {
				$filter .='<option value="' . $role_name . '" ' . selected( $role_name, $current_role , false ) . '>' . $role_info['name'] . '</option>';
			}
			return $filter;
		}

		/**
		 * Get user meta keys
		 * @return html
		 */
		private function mupr_custom_field_filter() {
			// exclude metakeys
			$exclude_metakeys = '("description","rich_editing","comment_shortcuts","admin_color","use_ssl","show_admin_bar_front","wp_user_level","dismissed_wp_pointers","show_welcome_panel","wp_dashboard_quick_press_last_post_id","wp_user-settings","wp_user-settings-time","default_password_nag")';
			// Get metakey list
			$metakey_list = $this->wpdb->get_results( 'SELECT distinct meta_key FROM ' . $this->wpdb->prefix .'usermeta WHERE meta_value NOT LIKE "a:%:{%}" AND meta_key NOT IN ' . $exclude_metakeys, ARRAY_A );
			$users_list = new WP_User_Query(
				array(
					'exclude' => $this->exclude
				)
			);
			$users_result = $users_list->get_results();
			$filter = '';
			foreach( $metakey_list as $k => $v ) {
				$metavalue = array();
				foreach( $users_result as $user_result ) {
					$users_metavalue = get_user_meta( $user_result->ID, $v['meta_key'], true );
					if ( $users_metavalue != '' ) {
						array_push( $metavalue, $users_metavalue );
					}
				}
				if ( ! empty( $metavalue ) ) {
					$meta_key = isset( $_REQUEST['key'] ) ? $_REQUEST['key'] : '';
					$filter .= '<option value="' . $v['meta_key'] . '" ' . selected( $v['meta_key'], $meta_key, false ) . '>' . $v['meta_key'] . '</option>';
				}
			}

			return $filter;
		}

		/**
		 * Get users
		 * @return array|object wp_user_query
		 */
		private function mupr_user_lists() {
			$privatdata = array();
			// Per page
			$users_per_page = $this->user_per_page;
			$paged = ( isset( $_REQUEST['paged'] ) ? $_REQUEST['paged'] : 1 );

			// user query
			$all_users_query = array(
				'exclude' => $this->exclude
			);
			$user_query = array(
				'exclude' 		=> $this->exclude,
				'number' 		=> $users_per_page,
				'offset' 		=> ( $paged - 1 ) * $users_per_page
			);
			// If check user role filter exists OR not
			if ( isset( $_REQUEST['role_filter'] ) ) {
				$role = sanitize_text_field( $_REQUEST['role_filter'] );
				if ( $role && $role != 'all' ) {
					$user_query['role'] = array(
						'role' => $role
					);
					// Pagination
					$all_users_query['role'] = array(
						'role' => $role
					);
				}
			}
			// If check user meta filter exists OR not
			if ( isset( $_REQUEST['value'] ) && isset( $_REQUEST['key'] ) ) {
				$metavalue = $_REQUEST['value'] != '' ? sanitize_text_field( $_REQUEST['value'] ) : '';
				$metakey = $_REQUEST['key'] != '' ? sanitize_text_field( $_REQUEST['key'] ) : '';
				if ( $metavalue ) {
					$user_query['meta_key'] = $metakey;
					$user_query['meta_value'] = $metavalue;
					$user_query['meta_compare'] = '=';
					// Pagination
					$all_users_query['meta_key'] = $metakey;
					$all_users_query['meta_value'] = $metavalue;
					$all_users_query['meta_compare'] = '=';

				}
			}
			// Get user's
			$data['list'] = new WP_User_Query( $user_query );
			$data['pagination'] = new WP_User_Query( $all_users_query );
			return $data;
		}

		/**
		 * Override pagination function
		 *
		 * @param string  $which  The which
		 *
		 * @return pagination
		 */
		function pagination( $which ) {
			if ( empty( $this->_pagination_args ) ) {
				return;
			}

			$total_items = $this->_pagination_args['total_items'];
			$total_pages = $this->_pagination_args['total_pages'];
			$infinite_scroll = false;
			if ( isset( $this->_pagination_args['infinite_scroll'] ) ) {
				$infinite_scroll = $this->_pagination_args['infinite_scroll'];
			}

			if ( 'top' === $which && $total_pages > 1 ) {
				$this->screen->render_screen_reader_content( 'heading_pagination' );
			}

			$output = '<span class="displaying-num">' . sprintf( _n( '%s item', '%s items', $total_items ), number_format_i18n( $total_items ) ) . '</span>';

			$current = $this->get_pagenum();
			$removable_query_args = wp_removable_query_args();
			$current_protocol = (stripos($_SERVER['SERVER_PROTOCOL'],'https') === true ? 'https://' : 'http://');
			$current_url = set_url_scheme( $current_protocol . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'] );
			$str_pos = strpos($current_url,'admin-ajax.php');
			//$current_url = substr_replace($current_url, 'users.php?page=mass_users_password_reset_options', $str_pos);
			$current_url = remove_query_arg( $removable_query_args, $current_url );

			$page_links = array();

			$total_pages_before = '<span class="paging-input">';
			$total_pages_after  = '</span></span>';

			$disable_first = $disable_last = $disable_prev = $disable_next = false;

			if ( $current == 1 ) {
				$disable_first = true;
				$disable_prev = true;
			}
			if ( $current == 2 ) {
				$disable_first = true;
			}
			if ( $current == $total_pages ) {
				$disable_last = true;
				$disable_next = true;
			}
			if ( $current == $total_pages - 1 ) {
				$disable_last = true;
			}

			if ( $disable_first ) {
				$page_links[] = '<span class="tablenav-pages-navspan" aria-hidden="true">&laquo;</span>';
			} else {
				$page_links[] = sprintf( "<a class='first-page' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
					esc_url( remove_query_arg( 'paged', $current_url ) ),
					__( 'First page' ),
					'&laquo;'
				);
			}

			if ( $disable_prev ) {
				$page_links[] = '<span class="tablenav-pages-navspan" aria-hidden="true">&lsaquo;</span>';
			} else {
				$page_links[] = sprintf( "<a class='prev-page' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
					esc_url( add_query_arg( 'paged', max( 1, $current-1 ), $current_url ) ),
					__( 'Previous page' ),
					'&lsaquo;'
				);
			}

			if ( 'bottom' === $which ) {
				$html_current_page  = $current;
				$total_pages_before = '<span class="screen-reader-text">' . __( 'Current Page' ) . '</span><span id="table-paging" class="paging-input"><span class="tablenav-paging-text">';
			} else {
				$html_current_page = sprintf( "%s<input class='current-page' id='current-page-selector' type='text' name='paged' value='%s' size='%d' aria-describedby='table-paging' /><span class='tablenav-paging-text'>",
					'<label for="current-page-selector" class="screen-reader-text">' . __( 'Current Page' ) . '</label>',
					$current,
					strlen( $total_pages )
				);
			}
			$html_total_pages = sprintf( "<span class='total-pages'>%s</span>", number_format_i18n( $total_pages ) );
			$page_links[] = $total_pages_before . sprintf( _x( '%1$s of %2$s', 'paging' ), $html_current_page, $html_total_pages ) . $total_pages_after;

			if ( $disable_next ) {
				$page_links[] = '<span class="tablenav-pages-navspan" aria-hidden="true">&rsaquo;</span>';
			} else {
				$page_links[] = sprintf( "<a class='next-page' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
					esc_url( add_query_arg( 'paged', min( $total_pages, $current + 1 ), $current_url ) ),
					__( 'Next page' ),
					'&rsaquo;'
				);
			}

			if ( $disable_last ) {
				$page_links[] = '<span class="tablenav-pages-navspan" aria-hidden="true">&raquo;</span>';
			} else {
				$page_links[] = sprintf( "<a class='last-page' href='%s'><span class='screen-reader-text'>%s</span><span aria-hidden='true'>%s</span></a>",
					esc_url( add_query_arg( 'paged', $total_pages, $current_url ) ),
					__( 'Last page' ),
					'&raquo;'
				);
			}

			$pagination_links_class = 'pagination-links';
			if ( ! empty( $infinite_scroll ) ) {
				$pagination_links_class = ' hide-if-js';
			}
			$output .= "\n<span class='$pagination_links_class'>" . join( "\n", $page_links ) . '</span>';

			if ( $total_pages ) {
				$page_class = $total_pages < 2 ? ' one-page' : '';
			} else {
				$page_class = ' no-pages';
			}
			$this->_pagination = "<div class='tablenav-pages mupr-pagination{$page_class}'>$output</div>";

			return $this->_pagination;
		}

		/**
		 * Session expired dialog box.
		 */
		function mupr_dialog_box() { ?>
			<div id="mupr-dialog" class="hidden" style="max-width:800px">
		  	<div id="dialog-message"></div>
		  	<a href="javascript:;" class="button-primary yes"><?php _e( 'Yes', 'mass-users-password-reset-pro' ); ?></a>&nbsp;&nbsp;<a href="javascript:;" class="button-primary cancel"><?php _e( 'Cancel', 'mass-users-password-reset-pro' ); ?></a>
			</div>
		<?php
		}
	}
}
