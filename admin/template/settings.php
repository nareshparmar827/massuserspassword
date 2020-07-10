<?php $options = $this->options; ?>
<form method="post" id="mupr_options">
	<?php wp_nonce_field( 'mupr-save-option', 'mupr-nonce' ); ?>
	<div class="mupr-collapse-box">
		<div class="mupr-collapse-header">
			<strong><?php _e( 'Config', 'mass-users-password-reset-pro' ); ?></strong>
			<div class="mupr-collapse-button"></div>
		</div>
		<div class="mupr-collapse-body">
			<div class="mupr-mail-setting">
				<div class="mupr-mail-setting-left">
					<div class="mupr-form-wrap">
						<div class="mupr-row">
							<div class="mupr-column half">
								<div class="mupr-form-group">
									<label><?php _e( 'Users Per page', 'mass-users-password-reset-pro' ); ?></label>
									<input class="mupr-input" type="number" name="mupr_per_page" value="<?php echo ( $options['mupr_per_page'] != '' ? $options['mupr_per_page'] : '' ); ?>" min="1" onkeypress="return ( event.charCode == 8 || event.charCode == 0 ) ? null : event.charCode >= 48 && event.charCode <= 57;">
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="mupr-collapse-box">
		<div class="mupr-collapse-header">
			<strong><?php _e( 'Mail Settings', 'mass-users-password-reset-pro' ); ?></strong>
			<div class="mupr-toggle-switch">
				<input type="checkbox" id="switch" name="mupr_to_send_reset_link" value="yes"<?php echo ( isset( $options['mupr_to_send_reset_link'] ) && $options['mupr_to_send_reset_link'] == 'yes' ? ' checked' : '' ); ?>>
				<label for="switch"><?php _e( 'Toggle', 'mass-users-password-reset-pro' ); ?></label>
				<input type="hidden" value="<?php echo ( is_plugin_active( 'woocommerce/woocommerce.php' ) ? 'true' : 'false' ); ?>" name="mupr_plugin_activation">
			</div>
			<span><?php _e( 'Send Password Reset Link only to Users', 'mass-users-password-reset-pro' ); ?></span>
			<div class="mupr-collapse-button"></div>
		</div>
		<div class="mupr-collapse-body">
			<div class="mupr-mail-setting">
				<div class="mupr-mail-setting-left">
					<h3><?php _e( 'Custom Email', 'mass-users-password-reset-pro' ); ?></h3>
					<div class="mupr-form-wrap">
						<div class="mupr-row">
							<div class="mupr-column half">
								<div class="mupr-form-group">
									<label><?php _e( 'From Email', 'mass-users-password-reset-pro' ); ?></label>
									<input class="mupr-input" type="text" name="from_email" value="<?php echo ( $options['from_email'] != '' ? $options['from_email'] : get_option( 'admin_email' ) ); ?>" placeholder="<?php echo bloginfo( 'admin_email' ); ?>">
								</div>
							</div>
							<div class="mupr-column half">
								<div class="mupr-form-group">
									<label><?php _e( 'From Name', 'mass-users-password-reset-pro' ); ?></label>
									<input class="mupr-input" type="text" name="from_name" value="<?php echo ( $options['from_name'] != '' ? $options['from_name'] : get_bloginfo( 'name' ) ); ?>">
								</div>
							</div>
						</div>
						<div class="mupr-row">
							<div class="mupr-column full">
								<div class="mupr-form-group">
									<label><?php _e( 'Subject', 'mass-users-password-reset-pro' ); ?></label>
									<input class="mupr-input" type="text" name="subject" value="<?php echo ( $options['subject'] != '' ? $options['subject'] : 'Reset Password' ); ?>">
								</div>
							</div>
							<div class="mupr-column full">
								<div class="mupr-form-group">
									<?php
									$default_message_content = wp_sprintf(
'<p>%s {USER_NAME}, </p>
<p%s </p>
<p>%s : {NEW_PASSWORD}</p>', __( 'Dear', 'mass-users-password-reset-pro' ), __( 'Your Password has been changed.', 'mass-users-password-reset-pro' ), __( 'Your new password is', 'mass-users-password-reset-pro' ) );
										?>
										<label><?php _e( 'Message', 'mass-users-password-reset-pro' ); ?></label>
										<textarea class="mupr-textarea" name="message"><?php echo ! empty( $options['message'] ) ? $options['message'] : $default_message_content; ?></textarea>
									</div>
								</div>
							</div>
						</div>
				</div>
				<div class="mupr-mail-setting-right">
					<h3><?php _e( 'Custom Email Placeholders', 'mass-users-password-reset-pro' ); ?></h3>
					<ul id="mupr-shortcode">
						<?php
							echo wp_sprintf( '<li><strong>%s:</strong> <span>%s</span></li>', __( 'Username of User', 'mass-users-password-reset-pro' ), '{USER_NAME}' );
							echo wp_sprintf( '<li><strong>%s:</strong> <span>%s</span></li>', __( 'First Name of User', 'mass-users-password-reset-pro' ), '{FIRST_NAME}' );
							echo wp_sprintf( '<li><strong>%s:</strong> <span>%s</span></li>', __( 'Last Name of User', 'mass-users-password-reset-pro' ), '{LAST_NAME}' );
							echo wp_sprintf( '<li><strong>%s:</strong> <span>%s</span></li>', __( 'User Profile url', 'mass-users-password-reset-pro' ), '{PROFILE_URL}' );
							echo wp_sprintf( '<li><strong>%s:</strong> <span>%s</span></li>', __( 'Website url', 'mass-users-password-reset-pro' ), '{SITE_URL}' );
							echo wp_sprintf( '<li><strong>%s:</strong> <span>%s</span></li>', __( 'Website Name', 'mass-users-password-reset-pro' ), '{SITE_NAME}' );
							echo wp_sprintf( '<li><strong>%s:</strong> <span>%s</span></li>', __( 'User Email', 'mass-users-password-reset-pro' ), '{USER_EMAIL}' );
							// If check reset enabled OR not
							if ( $options['mupr_to_send_reset_link'] == 'yes' ) {
								echo wp_sprintf( '<li id="reset_link" class="mupr-change-pwd-option"><strong>%s:</strong> <span>%s</span></li>', __( 'Reset Password link of User', 'mass-users-password-reset-pro' ), '{RESET_PASSWORD_URL}' );
								// If check woocommerce enabled OR not
								if ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
									echo wp_sprintf( '<li class="mupr-woo-url"><strong>%s:</strong> <span>%s</span></li>', __( 'Woocommerce Reset Password url', 'mass-users-password-reset-pro' ), '{WOO_RESET_PASSWORD_URL}' );
								}
							} else {
								echo wp_sprintf( '<li id="new_password"><strong>%s:</strong> <span>%s</span></li>', __( 'New Password', 'mass-users-password-reset-pro' ), '{NEW_PASSWORD}' );
							}
						?>
					</ul>
				</div>
			</div>
		</div>
	</div>
	<div class="mupr-collapse-box">
		<div class="mupr-collapse-header">
			<strong><?php _e( 'Test Mode', 'mass-users-password-reset-pro' ); ?></strong>
			<div class="mupr-toggle-switch">
				<?php
					$test_on = isset( $options['mupr_testmode'] ) && $options['mupr_testmode'] == true ? ' checked' : '';
				?>
				<input type="checkbox" id="test-mod" name="mupr_testmode"<?php echo $test_on; ?>>
				<label for="test-mod"><?php _e( 'Toggle', 'mass-users-password-reset-pro' ); ?></label>
			</div>
			<span><?php _e( 'Enable Test', 'mass-users-password-reset-pro' ); ?></span>
			<div class="mupr-collapse-button"></div>
		</div>
		<div class="mupr-collapse-body">
			<div class="mupr-mail-setting">
				<div class="mupr-mail-setting-left">
						<div class="mupr-form-wrap">
							<div class="mupr-row">
								<div class="mupr-column half">
									<div class="mupr-form-group">
										<?php
										$mupr_testmode_email = isset( $options['mupr_testmode_email'] ) && ! empty( $options['mupr_testmode_email'] ) ? $options['mupr_testmode_email'] : '';
										?>
										<label><?php _e( 'To Email', 'mass-users-password-reset-pro' ); ?></label>
										<input type="text" class="mupr-input" placeholder="<?php echo bloginfo( 'admin_email' ); ?>" name="mupr_testmode_email" id="test-mod-email" value="<?php echo $mupr_testmode_email; ?>"<?php echo empty( $test_on ) ? ' readonly': ''; ?>>
									</div>
								</div>
							</div>
						</div>
				</div>
			</div>
		</div>
	</div>
</form>
<div class="tablenav mupr-tablenav">
	<?php submit_button( __( 'Save', 'mass-users-password-reset-pro' ), 'button mupr-btn', 'mupr-submit', '', '' ); ?>
</div>