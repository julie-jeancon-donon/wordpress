<?php
/**
 * The WP_Members User Profile Class.
 *
 * @package WP-Members
 * @subpackage WP_Members Admin User Profile Object Class
 * @since 3.1.8
 */

// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

class WP_Members_User_Profile {
	
	/**
	 * Static function to display WP-Members fields on the admin/dashboard user profile.
	 *
	 * Function was created in 3.1.9 as a merge of wpmem_admin_fields()
	 * and wpmem_user_profile().
	 *
	 * @since 3.1.9
	 *
	 * @global object $current_screen
	 * @global string $user_ID
	 * @global object $wpmem
	 * @param  object $user_obj
	 */
	static function profile( $user_obj ) {
	
		global $current_screen, $user_ID, $wpmem;
		$user_id = ( 'profile' == $current_screen->id ) ? $user_ID : filter_var( $_REQUEST['user_id'], FILTER_SANITIZE_NUMBER_INT ); 
		$display = ( 'profile' == $current_screen->base ) ? 'user' : 'admin'; 
		$display = ( current_user_can( 'edit_users' ) ) ? 'admin' : $display; ?>

		<h3><?php
		$heading = ( 'admin' == $display ) ? __( 'WP-Members Additional Fields', 'wp-members' ) : __( 'Additional Information', 'wp-members' );
		/**
		 * Filter the heading for additional profile fields.
		 *
		 * @since 2.8.2 Admin Profile
		 * @since 2.9.1 Dashboard Profile
		 * @since 3.1.9 Merged admin/dashboard profile
		 *
		 * @param string The default additional fields heading.
		 */
		echo apply_filters( 'wpmem_' . $display . '_profile_heading', $heading ); ?></h3>   
		<table class="form-table">
			<?php
			// Get fields.
			$wpmem_fields = ( 'admin' == $display ) ? wpmem_fields( 'admin_profile' ) : wpmem_fields( 'dashboard_profile' );
			// Get excluded meta.
			$exclude = wpmem_get_excluded_meta( $display . '-profile' );
		
			// If tos is an active field, this is the dashboard profile, and user has current field value.
			if ( isset( $wpmem_fields['tos'] ) && get_user_meta( $user_ID, 'tos', true ) == $wpmem_fields['tos']['checked_value'] ) {
				unset( $wpmem_fields['tos'] );
			}

			/**
			 * Fires at the beginning of generating the WP-Members fields in the user profile.
			 *
			 * @since 2.9.3 Created for admin profile.
			 * @since 3.1.9 Added to dashboard profile.
			 *
			 * @param int   $user_id      The user's ID.
			 * @param array $wpmem_fields The WP-Members fields.
			 */
			do_action( 'wpmem_' . $display . '_before_profile', $user_id, $wpmem_fields );

			// Assemble form rows array.
			$rows = array();
			foreach ( $wpmem_fields as $meta => $field ) {

				$valtochk = ''; $values = '';

				// Determine which fields to show in the additional fields area.
				$show = ( ! $field['native'] && ! in_array( $meta, $exclude ) ) ? true : false;
				$show = ( 'tos' == $meta && $field['register'] ) ? null : $show;

				if ( $show ) {

					$val = get_user_meta( $user_id, $meta, true );
					$val = ( $field['type'] == 'multiselect' || $field['type'] == 'multicheckbox' ) ? $val : htmlspecialchars( $val );
					if ( $field['type'] == 'checkbox' ) {
						$valtochk = $val;
						$val = $field['checked_value'];
					}

					if ( 'multicheckbox' == $field['type'] || 'select' == $field['type'] || 'multiselect' == $field['type'] || 'radio' == $field['type'] ) {
						$values = $field['values'];
						$valtochk = $val;
					}

					// Is this an image or a file?
					if ( 'file' == $field['type'] || 'image' == $field['type'] ) {
						$empty_file = '<span class="description">' . __( 'None' ) . '</span>';
						if ( 'file' == $field['type'] ) {
							$attachment_url = wp_get_attachment_url( $val );
							$input = ( $attachment_url ) ? '<a href="' . $attachment_url . '">' . $attachment_url . '</a>' : $empty_file;
						} else {
							$attachment_url = wp_get_attachment_image( $val, 'medium' );
							if ( 'admin' == $display ) {
								$edit_url = admin_url( 'upload.php?item=' . $val );
								$input = ( $attachment_url ) ? '<a href="' . $edit_url . '">' . $attachment_url . '</a>' : $empty_file;
							} else {
								$input = ( $attachment_url ) ? $attachment_url : $empty_file;
							}
						}
						$input.= '<br />' . $wpmem->get_text( 'profile_upload' ) . '<br />';
						$input.= wpmem_form_field( array(
							'name'    => $meta, 
							'type'    => $field['type'], 
							'value'   => $val, 
							'compare' => $valtochk,
						) );
					} else {
						if ( 'select' == $field['type'] || 'radio' == $field['type'] ) {
							$input = wpmem_create_formfield( $meta, $field['type'], $values, $valtochk );
						} elseif( 'multicheckbox' == $field['type'] || 'multiselect' == $field['type'] ) {
							$input = $wpmem->forms->create_form_field( array( 'name'=>$meta, 'type'=>$field['type'], 'value'=>$values, 'compare'=>$valtochk, 'delimiter'=>$field['delimiter'] ) );
						} else {
							$field['type'] = ( 'hidden' == $field['type'] ) ? 'text' : $field['type'];
							$input = wpmem_create_formfield( $meta, $field['type'], $val, $valtochk );
						}
					}

					// Is the field required?
					$req = ( $field['required'] ) ? ' <span class="description">' . __( '(required)' ) . '</span>' : '';
					$label = '<label>' . __( $field['label'], 'wp-members' ) . $req . '</label>';

					// Build the form rows for filtering.
					$rows[ $meta ] = array(
						'meta'         => $meta,
						'type'         => $field['type'],
						'value'        => $val,
						'values'       => $values,
						'label_text'   => __( $field['label'], 'wp-members' ),
						'row_before'   => '<tr>',
						'label'        => '<th>' . $label . '</th>',
						'field_before' => '<td>',
						'field'        => $input,
						'field_after'  => '</td>',
						'row_after'    => '</tr>',
					);
				}
			}

			/**
			 * Filter for rows
			 *
			 * @since 3.1.0
			 * @since 3.1.6 Deprecated $order.
			 *
			 * @param array  $rows {
			 *     An array of the profile rows.
			 *
			 *     @type string $meta         The meta key.
			 *     @type string $type         The field type.
			 *     @type string $value        Value if set.
			 *     @type string $values       Possible values (select, multiselect, multicheckbox, radio).
			 *     @type string $label_text   Raw label text (no HTML).
			 *     @type string $row_before   HTML before the row.
			 *     @type string $label        HTML label.
			 *     @type string $field_before HTML before the field input tag.
			 *     @type string $field        HTML for field input.
			 *     @type string $field_after  HTML after the field.
			 *     @type string $row_after    HTML after the row.
			 * }
			 * @param string $tag adminprofile|userprofile
			 */
			$rows = apply_filters( 'wpmem_register_form_rows_' . $display, $rows, $display . 'profile' );

			// Handle form rows display from array.
			foreach ( $rows as $row ) {
				$show_field =
					$row['row_before'] . 
						$row['label'] .
						$row['field_before'] . $row['field'] . $row['field_after'] .
					$row['field_after'];

				/**
				 * Filter the profile field.
				 * 
				 * @since 2.8.2
				 * @since 3.1.1 Added $user_id and $row
				 *
				 * @param string $show_field The HTML string for the additional profile field.
				 * @param string $user_id
				 * @param array  $row
				 */
				echo apply_filters( 'wpmem_' . $display . '_profile_field', $show_field, $user_id, $row );
			}

			/**
			 * Fires after generating the WP-Members fields in the user profile.
			 *
			 * @since 2.9.3
			 *
			 * @param int   $user_id      The user's ID.
			 * @param array $wpmem_fields The WP-Members fields.
			 */
			do_action( 'wpmem_' . $display . '_after_profile', $user_id, $wpmem_fields ); ?>

		</table><?php
		
	}
	
	/**
	 * Static function to update admin/dashboard user profile.
	 *
	 * Function was created in 3.1.9 as a merge of wpmem_admin_update()
	 * and wpmem_profile_update().
	 *
	 * @since 3.1.9
	 *
	 * @global object $current_screen
	 * @global string $user_id
	 * @global object $wpmem
	 * @param  string $user_id
	 * @return
	 */
	static function update( $user_id ) {
		
		global $current_screen, $user_id, $wpmem;
		$display = ( 'profile' == $current_screen->base ) ? 'user' : 'admin';
	
		if ( ! $user_id ) {
			$user_id = filter_var( wpmem_get( 'user_id', -1, 'request' ), FILTER_SANITIZE_NUMBER_INT );
			if ( 1 > $user_id ) {
			// Still no user id? User cannot be updated.
			return;
			}
		}

		$wpmem_fields = ( 'admin' == $display ) ? wpmem_fields( 'admin_profile_update' ) : wpmem_fields( 'dashboard_profile_update' );
		
		// Check for password field before exclusions, just in case we are activating a user (otherwise password is removed on user/admin profiles).
		$chk_pass = ( array_key_exists( 'password', $wpmem_fields ) && true === $wpmem_fields['password']['register'] ) ? true : false;
	
		$exclude = wpmem_get_excluded_meta( $display . '-profile' );

		foreach ( $exclude as $excluded ) {
			unset( $wpmem_fields[ $excluded ] );
		}

		// If tos is an active field, this is the dashboard profile, and user has current field value.
		if ( isset( $wpmem_fields['tos'] ) && get_user_meta( $user_id, 'tos', true ) == $wpmem_fields['tos']['checked_value'] ) {
			unset( $wpmem_fields['tos'] );
		}

		/**
		 * Fires before the user profile is updated.
		 *
		 * @since 2.9.2 Added for admin profile update.
		 * @since 3.1.9 Added for user profile update.
		 *
		 * @param int   $user_id      The user ID.
		 * @param array $wpmem_fields Array of the custom fields.
		 */
		do_action( 'wpmem_' . $display . '_pre_user_update', $user_id, $wpmem_fields );

		$fields = array();
		foreach ( $wpmem_fields as $meta => $field ) {
			if ( ! $field['native']
				&& $field['type'] != 'password' 
				&& $field['type'] != 'checkbox' 
				&& $field['type'] != 'multiselect' 
				&& $field['type'] != 'multicheckbox' 
				&& $field['type'] != 'file' 
				&& $field['type'] != 'image'
			    && $field['type'] != 'textarea' ) {
				( isset( $_POST[ $meta ] ) && 'password' != $field['type'] ) ? $fields[ $meta ] = sanitize_text_field( $_POST[ $meta ] ) : false;
				
				// For user profile (not admin).
				$chk = false;
				if ( 'admin' != $display ) {
					// Check for required fields.
					if ( ! $field['required'] ) {
						$chk = 'ok';
					}
					if ( $field['required'] && $_POST[ $meta ] != '' ) {
						$chk = 'ok';
					}
				}
			} elseif ( $field['type'] == 'checkbox' ) {
				$fields[ $meta ] = ( isset( $_POST[ $meta ] ) ) ? sanitize_text_field( $_POST[ $meta ] ) : '';
			} elseif ( $field['type'] == 'multiselect' || $field['type'] == 'multicheckbox' ) {
				$fields[ $meta ] = ( isset( $_POST[ $meta ] ) ) ? implode( $field['delimiter'], wp_unslash( $_POST[ $meta ] ) ) : '';
			} elseif ( $field['type'] == 'textarea' ) {
				$fields[ $meta ] = ( isset( $_POST[ $meta ] ) ) ? sanitize_textarea_field( $_POST[ $meta ] ) : '';
			}
		}

		/**
		 * Filter the submitted field values for backend profile update.
		 *
		 * @since 2.8.2 Added for Admin profile update.
		 * @since 3.1.9 Added for User profile update.
		 *
		 * @param array $fields An array of the posted form values.
		 * @param int   $user_id The ID of the user being updated.
		 */
		$fields = apply_filters( 'wpmem_' . $display . '_profile_update', $fields, $user_id );

		// Handle meta update, skip excluded fields.
		foreach ( $fields as $key => $val ) {
			if ( ! in_array( $key, $exclude ) ) {
				if ( ( 'admin' != $display && 'ok' == $chk ) || 'admin' == $display ) {
					update_user_meta( $user_id, $key, $val );
				}
			}
		}

		if ( ! empty( $_FILES ) ) {
			$wpmem->user->upload_user_files( $user_id, $wpmem->fields );
		}	

		if ( 'admin' == $display || current_user_can( 'edit_users' ) ) {
			if ( $wpmem->mod_reg == 1 ) {

				$wpmem_activate_user = ( isset( $_POST['activate_user'] ) == '' ) ? -1 : filter_var( $_POST['activate_user'], FILTER_SANITIZE_NUMBER_INT );

				if ( $wpmem_activate_user == 1 ) {
					wpmem_activate_user( $user_id, $chk_pass );
				} elseif ( $wpmem_activate_user == 0 ) {
					wpmem_deactivate_user( $user_id );
				}
			}

			if ( defined( 'WPMEM_EXP_MODULE' ) && $wpmem->use_exp == 1 ) {
				if ( function_exists( 'wpmem_a_extenduser' ) ) {
					wpmem_a_extend_user( $user_id );
				}
			}
			
			if ( 1 == $wpmem->enable_products ) {
				// Update products.
				if ( isset( $_POST['_wpmem_membership_product'] ) ) {
					foreach ( $_POST['_wpmem_membership_product'] as $product_key => $product_value ) {
						// Enable or Disable?
						if ( 'enable' == $product_value ) {
							// Does product require a role?
							if ( false !== $wpmem->membership->products[ $product_key ]['role'] ) {
								wpmem_update_user_role( $user_id, $wpmem->membership->products[ $product_key ]['role'], 'add' );
							}
							$wpmem->user->set_user_product( $product_key, $user_id );
						}
						if ( 'disable' == $product_value ) {
							$wpmem->user->remove_user_product( $product_key, $user_id );
						}
					}	
				}
			}
		}

		/**
		 * Fires after the user profile is updated.
		 *
		 * @since 2.9.2
		 *
		 * @param int $user_id The user ID.
		 */
		do_action( 'wpmem_' . $display . '_after_user_update', $user_id );

		return;
	}
	
	/**
	 * Sets user profile update to multipart form data.
	 *
	 * If the fields array has a file or image field, this will echo the 
	 * necessary "multipart/form-data" enctype for the form tag.
	 *
	 * @since 3.1.8 (as wpmem_profile_multipart()).
	 * @since 3.1.9 Moved to User Profile object.
	 */
	public static function add_multipart() {
		$has_file = false;
		foreach ( wpmem_fields() as $field ) {
			if ( $field['type'] == 'file' || $field['type'] == 'image' ) {
				$has_file = true;
				break;
			}
		}
		echo ( $has_file ) ? " enctype=\"multipart/form-data\"" : '';
	}
	
	/**
	 * Adds user activation to the user profile.
	 *
	 * @since 3.1.1
	 * @since 3.2.0 Moved to WP_Members_User_Profile object
	 *
	 * @global object $wpmem
	 * @param  int    $user_id
	 */
	public static function _show_activate( $user_id ) {
		global $wpmem;
		// See if reg is moderated, and if the user has been activated.
		if ( $wpmem->mod_reg == 1 ) {
			$user_active_flag = get_user_meta( $user_id, 'active', true );
			switch( $user_active_flag ) {

				case '':
					$label  = __( 'Activate this user?', 'wp-members' );
					$action = 1;
					break;

				case 0:
					$label  = __( 'Reactivate this user?', 'wp-members' );
					$action = 1;
					break;

				case 1:
					$label  = __( 'Deactivate this user?', 'wp-members' );
					$action = 0;
					break;

			} ?>
			<tr>
				<th><label><?php echo $label; ?></label></th>
				<td><input id="activate_user" type="checkbox" class="input" name="activate_user" value="<?php echo $action; ?>" /></td>
			</tr>
		<?php }
	}

	/**
	 * Adds user expiration to the user profile.
	 *
	 * @since 3.1.1
	 * @since 3.2.0 Moved to WP_Members_User_Profile object
	 *
	 * @global object $wpmem
	 * @param  int    $user_id
	 */
	public static function _show_expiration( $user_id ) {

	global $wpmem;
		/*
		 * If using subscription model, show expiration.
		 * If registration is moderated, this doesn't show 
		 * if user is not active yet.
		 */
		if ( defined( 'WPMEM_EXP_MODULE' ) && $wpmem->use_exp == 1 ) {
			if ( ( $wpmem->mod_reg == 1 &&  get_user_meta( $user_id, 'active', true ) == 1 ) || ( $wpmem->mod_reg != 1 ) ) {
				if ( function_exists( 'wpmem_a_extenduser' ) ) {
					wpmem_a_extenduser( $user_id );
				}
			}
		} 
	} 


	/**
	 * Adds user registration IP to the user profile.
	 *
	 * @since 3.1.1
	 * @since 3.2.0 Moved to WP_Members_User_Profile object
	 *
	 * @param  int    $user_id
	 */
	public static function _show_ip( $user_id ) { ?>
		<tr>
			<th><label><?php _e( 'IP @ registration', 'wp-members' ); ?></label></th>
			<td><?php echo get_user_meta( $user_id, 'wpmem_reg_ip', true ); ?></td>
		</tr>
		<?php
	}

	/**
	 * Add user product access to user profile.
	 *
	 * @since 3.2.0
	 *
	 * @global object $wpmem
	 * @param  int    $user_id
	 */
	public static function _show_product( $user_id ) { 
		// If product enabled
		global $wpmem;
		$user_products = $wpmem->user->get_user_products( $user_id ); ?>
		<tr>
			<th><label><?php _e( 'Product Access', 'wp-members' ); ?></label></th>
			<td><table><?php
			foreach ( $wpmem->membership->products as $key => $value ) {
				$checked = ( $user_products && array_key_exists( $key, $user_products ) ) ? "checked" : "";
				echo "<tr>";
				echo '<td style="padding:5px 5px;">
				<select name="_wpmem_membership_product[' . $key . ']">
					<option value="">----</option>
					<option value="enable">'  . __( 'Enable', 'wp-members'  ) . '</option>
					<option value="disable">' . __( 'Disable', 'wp-members' ) . '</option>
				</select></td><td style="padding:0px 0px;">' . $value['title'] . '</td>
				<td style="padding:0px 0px;">';
				if ( isset( $user_products[ $key ] ) ) {
					echo '<span id="wpmem_product_enabled" class="dashicons dashicons-yes"></span>';
					if ( $user_products[ $key ] !== true ) {
						echo __( 'Expires:', 'wp-members' ) . ' ' . $user_products[ $key ];
					} else {
						_e( 'Enabled', 'wp-members' );
					}
				} else {
					echo "&nbsp;";
				}
				echo '</td></tr>';
			}
				?></table></td>
		</tr>
		<?php	
	}
}