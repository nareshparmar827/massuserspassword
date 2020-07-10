<div class="mupr-wrap">
	<div class="spinner"></div>
	<div class="wrap">
		<?php
			if( isset( $_GET[ 'tab' ] ) ) {
				$active_tab = $_GET[ 'tab' ];
			} else {
				$active_tab = 'user-list';
			}
		?>
		<!-- mupr header start -->
		<div class="mupr-header">
			<div class="mupr-header-right">
				<div class="logo">
					<a href="<?php echo esc_url( 'https://codecanyon.net/item/mass-users-password-reset-pro/20809350' ); ?>" target="_blank"><img src="<?php echo MASS_USERS_PASSWORD_RESET_PRO_PLUGIN_URL; ?>assets/images/mupr-logo.png" alt="Mass User Password Reset"></a>
				</div>
			</div>
			<div class="mupr-header-left">
				<div class="nav-tab-wrapper mupr-tab">
					<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'mass_users_password_reset_options', 'tab' => 'user-list' ), admin_url( 'users.php' ) ) ); ?>" class="nav-tab<?php echo $active_tab == 'user-list' ? ' active' : ''; ?>"><span class="dashicons dashicons-admin-users"></span> <?php _e( 'User List', 'mass-users-password-reset-pro' ); ?></a>
					<a href="<?php echo esc_url( add_query_arg( array( 'page' => 'mass_users_password_reset_options', 'tab' => 'settings' ), admin_url( 'users.php' ) ) ); ?>" class="nav-tab<?php echo $active_tab == 'settings' ? ' active' : ''; ?>"><span class="dashicons dashicons-admin-generic"></span> <?php _e( 'Settings', 'mass-users-password-reset-pro' ); ?></a>
				</div>
			</div>
		</div>
		<!-- mupr header end -->
		<!-- mupr body start -->
		<div class="mupr-body">
			<div class="notice notice-success is-dismissible mupr-hidden">
				<p><strong><?php echo esc_html( 'Settings saved.' ); ?></strong></p>
				<button type="button" class="notice-dismiss">
					<span class="screen-reader-text"><?php _e( 'Dismiss this notice.', 'mass-users-password-reset-pro' ); ?></span>
				</button>
			</div>
			<div class="notice notice-error is-dismissible mupr-hidden">
				<p><strong><?php echo esc_html( 'Something went wrong please try again.' ); ?></strong></p>
				<button type="button" class="notice-dismiss">
					<span class="screen-reader-text"><?php _e( 'Dismiss this notice.', 'mass-users-password-reset-pro' ); ?></span>
				</button>
			</div>
			<?php if ( isset( $this->options['mupr_testmode'] ) && $this->options['mupr_testmode'] == true ) : ?>
				<div class="notice notice-info">
					<p><strong><?php _e( 'Test mode is enabled.' ); ?></strong></p>
				</div>
			<?php endif;
				// Require files.
				require_once $active_tab . '.php';
			?>
		</div>
		<!-- mupr body end -->
	</div>
</div>