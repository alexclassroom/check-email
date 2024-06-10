<?php namespace CheckEmail\Core\Request;

use CheckEmail\Core\Loadie;
use CheckEmail\Core\UI\Page\Check_Email_Log_List_Page;

/**
 * Actions performed in Log List.
 */
class Check_Email_Log_List_Action implements Loadie {

	public function load() {
		add_action( 'wp_ajax_check-email-log-list-view-message', array( $this, 'view_log_message' ) );

		add_action( 'check-email-log-list-delete', array( $this, 'delete_logs' ) );
		add_action( 'check-email-log-list-delete-all', array( $this, 'delete_all_logs' ) );
		add_action( 'check-email-log-list-manage-user-roles-changed', array( $this, 'update_capabilities_for_user_roles' ), 10, 2 );
		add_action( 'admin_init', array( $this, 'deleted_logs_message' ) );
	}

	public function view_log_message() {
		if ( ! current_user_can( 'manage_check_email' ) ) {
			wp_die();
		}

		$id = isset( $_GET['log_id'] ) ? absint( $_GET['log_id'] ) : 0 ;

		if ( $id <= 0 ) {
			wp_die();
		}

		$log_items = $this->get_table_manager()->fetch_log_items_by_id( array( $id ) );
		if ( count( $log_items ) > 0 ) {
			$log_item = $log_items[0];

			$headers = array();
			if ( ! empty( $log_item['headers'] ) ) {
				$parser  = new \CheckEmail\Util\Check_Email_Header_Parser();
				$headers = $parser->parse_headers( $log_item['headers'] );
			}

			$active_tab = '0';
			if ( isset( $headers['content_type'] ) && 'text/html' === $headers['content_type'] ) {
				$active_tab = '1';
			}

			?>
			<table style="width: 100%;" id="my_table">
				<tr style="background: #eee;">
					<td style="padding: 5px;"><b><?php esc_html_e( 'Sent at', 'check-email' ); ?></b>:</td>
					<td style="padding: 5px;"><?php echo esc_html( $log_item['sent_date'] ); ?></td>
				</tr>
				<tr style="background: #eee;">
					<td style="padding: 5px;"><b><?php esc_html_e( 'To', 'check-email' ); ?></b>:</td>
					<td style="padding: 5px;"><?php echo esc_html( $log_item['to_email'] ); ?></td>
				</tr>
				<tr style="background: #eee;">
					<td style="padding: 5px;"><b><?php esc_html_e( 'Subject', 'check-email' ); ?></b>:</td>
					<td style="padding: 5px;"><?php echo esc_html( $log_item['subject'] ); ?></td>
				</tr>
                <tr style="background: #eee;">
					<td style="padding: 5px;"><b><?php esc_html_e( 'From', 'check-email' ); ?></b>:</td>
					<td style="padding: 5px;"><?php echo esc_html( $headers['from'] ); ?></td>
				</tr>
				<tr style="background: #eee;">
					<td style="padding: 5px;"><b><?php esc_html_e( 'Reply To', 'check-email' ); ?></b>:</td>
					<td style="padding: 5px;"><?php echo esc_html( $headers['reply_to'] ); ?></td>
				</tr>
				<tr style="background: #eee;">
					<td style="padding: 5px;"><b><?php esc_html_e( 'Headers', 'check-email' ); ?></b>:</td>
					<td style="padding: 5px;"><?php echo esc_html( $log_item['headers'] ); ?></td>
				</tr>

				<?php do_action( 'check_email_view_log_after_headers', $log_item ); ?>

			</table>

			<div id="tabs">
				<ul data-active-tab="<?php echo absint( $active_tab ); ?>" class="check_mail_non-printable">
					<li><a href="#tabs-text" onclick='hidePrint();'><?php esc_html_e( 'Raw Email Content', 'check-email' ); ?></a></li>
					<li><a href="#tabs-preview" onclick='showPrint();'><?php esc_html_e( 'Preview Content as HTML', 'check-email' ); ?></a></li>
					<li><a href="#tabs-trigger-data" onclick='hidePrint();'><?php esc_html_e( 'Triggered Form', 'check-email' ); ?></a></li>
				</ul>

				<div id="tabs-text">
					<pre class="tabs-text-pre"><?php echo esc_textarea( $log_item['message'] ); ?></pre>
				</div>

				<div id="tabs-preview">
					<?php echo wp_kses( $log_item['message'], $this->check_email_kses_allowed_html( 'post' ) ); ?>
					<?php
					if (!empty($log_item['attachment_name'])) {
						$attachments = explode(',',$log_item['attachment_name']);
						if ($attachments) {
							?>
							<h4>Attachments</h4>
							<?php
							foreach ($attachments as $key => $attachment) {
								?>
								<img src="<?php echo $attachment ?>" height="100px" width="100px" />
								<?php
							}
						}
					}
					?>
				</div>
				
				<div id="tabs-trigger-data">
					<?php 
					if(!defined('CK_MAIL_PRO_VERSION')){
					?>
						<p><?php esc_html_e( 'Triggered data helps you in debugging by showing the exact code that is sending that email ', 'check-email' ); ?><a href="https://check-email.tech/docs/knowledge-base/how-to-use-the-trigger-option-to-debug-emails-by-identifying-the-exact-code/" target="_blank"><?php esc_html_e(' Learn More'); ?></a></p>
						<p id="check-email-trigger-data-free-note"> <?php esc_html_e( 'This Feature requires the Premium Version', 'check-email' ); ?> <a href="https://check-email.tech/pricing/#pricings" target="_blank" class="check-mail-premium-btn"><span><?php esc_html_e('Upgrade Now', 'check-email'); ?><span></a> </p>
					<?php
					}else{
						do_action('check_email_pro_log_tabs_content', $id);
					}
					?>
				</div>
			</div>

			<div id="view-message-footer" class="check_mail_non-printable">
				<a href="#" class="button action" id="thickbox-footer-close"><?php esc_html_e( 'Close', 'check-email' ); ?></a>
				<bitton type="button" class="button button-primary" id="check_mail_print_button" style="margin-top: 10px; display:none;" onclick='printLog();'><?php esc_html_e( 'Print', 'check-email' ); ?></a>
			</div>
			<?php
		}

		wp_die(); // this is required to return a proper result.
	}

	public function delete_logs( $data ) {
		if ( ! is_array( $data ) || ! array_key_exists( 'check-email-log', $data ) ) {
			return;
		}

		$ids = $data['check-email-log'];
		if ( ! is_array( $ids ) ) {
			$ids = array( $ids );
		}

		$ids     = array_map( 'absint', $ids );
		$id_list = implode( ',', $ids );

		$logs_deleted = $this->get_table_manager()->delete_logs( $id_list );
		if( isset( $_REQUEST['_wp_http_referer'] ) ){
			wp_redirect( wp_unslash( $_REQUEST['_wp_http_referer'] ) . '&deleted_logs=' . $logs_deleted ); exit;
		}else{
			// phpcs:ignore
			wp_redirect( wp_unslash( $_SERVER['HTTP_REFERER'] ) . '&deleted_logs=' . $logs_deleted ); exit;
		}
	}

	public function delete_all_logs() {
		$logs_deleted = $this->get_table_manager()->delete_all_logs();
		if( isset($_REQUEST['_wp_http_referer'] ) ){
			wp_redirect( wp_unslash( $_REQUEST['_wp_http_referer'] ) . '&deleted_logs=' . $logs_deleted ); exit;
		}
	}

	public function deleted_logs_message(){
		if( isset( $_GET['deleted_logs'] ) ){
			$this->render_log_deleted_notice( intval( $_GET['deleted_logs'] ) );
		}
	}
	public function update_capabilities_for_user_roles( $old_roles, $new_roles ) {
		foreach ( $old_roles as $old_role ) {
			$role = get_role( $old_role );

			if ( ! is_null( $role ) ) {
				$role->remove_cap( Check_Email_Log_List_Page::CAPABILITY );
			}
		}

		foreach ( $new_roles as $new_role ) {
			$role = get_role( $new_role );

			if ( ! is_null( $role ) ) {
				$role->add_cap( Check_Email_Log_List_Page::CAPABILITY );
			}
		}
	}

	protected function render_log_deleted_notice( $logs_deleted ) {
		$message = esc_html__( 'There was some problem in deleting the email logs', 'check-email' );
		$type    = 'error';

		if ( absint( $logs_deleted ) > 0 ) {
			$message = sprintf( esc_html( _n( '1 email log deleted.', '%s email logs deleted', $logs_deleted, 'check-email' )), $logs_deleted );
			$type    = 'updated';
		}

		add_settings_error(
			'log-list',
			'deleted-check-email-logs',
			$message,
			$type
		);
	}

	protected function get_table_manager() {
		$check_email = wpchill_check_email();

		return $check_email->table_manager;
	}

	protected function check_email_kses_allowed_html( $context = 'post' ) {
		$allowed_tags = wp_kses_allowed_html( $context );

		$allowed_tags['link'] = array(
			'rel'   => true,
			'href'  => true,
			'type'  => true,
			'media' => true,
		);

		return $allowed_tags;
	}
}
