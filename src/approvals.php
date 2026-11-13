<?php

defined( 'ABSPATH' ) || exit;

/*
 * What follows a change of status for a user who signed up through the plugin, and the
 * list of users awaiting approval. The Approve and Reject links themselves are the
 * status links of user-status.php.
 */

/**
 * Sends the emails of a registration when its status changes, whoever changed it: the
 * links and bulk actions of the users list, the field on the user's screen, a confirmed
 * email address, or other code calling clu_set_user_status().
 */
add_action( 'clu_user_status_changed', 'clu_on_registration_status_change', 5, 3 );
function clu_on_registration_status_change( $user_id, $status, $old ) {
    if ( ! clu_user_signed_up_here( $user_id ) ) {
        return;
    }

    if ( $status === 'approved' && in_array( $old, [ 'pending', 'rejected', 'unconfirmed' ], true ) ) {
        clu_send_set_password_email( $user_id );
    }

    // The address is confirmed, so the registration reaches the admin now.
    if ( $status === 'pending' && $old === 'unconfirmed' ) {
        clu_send_registration_emails( get_userdata( $user_id ) );
    }

    if ( $status === 'rejected' && $old === 'pending' ) {
        clu_send_rejection_email( $user_id );
    }
}

/* ─── The list of users awaiting approval ────────────────────────────── */

/**
 * Returns the address of the users list filtered to those awaiting approval, relative
 * to wp-admin.
 *
 * @return string
 */
function clu_pending_list_path() {
    return 'users.php?clu_status=pending';
}

/**
 * Returns how many users await approval.
 *
 * @return int
 */
function clu_get_pending_count() {
    return clu_status_pending_count();
}

/**
 * Shows the number of users awaiting approval on the Users menu, and adds a link to them.
 */
add_action( 'admin_menu', 'clu_add_pending_menu', 99 );
function clu_add_pending_menu() {
    global $menu;

    if ( ! current_user_can( 'promote_users' ) ) {
        return;
    }

    $count = clu_get_pending_count();

    if ( ! $count ) {
        return;
    }

    $badge = sprintf( ' <span class="awaiting-mod count-%1$d"><span class="pending-count">%2$s</span></span>', $count, esc_html( number_format_i18n( $count ) ) );

    foreach ( (array) $menu as $position => $item ) {
        if ( ( $item[2] ?? '' ) === 'users.php' ) {
            $menu[ $position ][0] .= $badge; // phpcs:ignore WordPress.WP.GlobalVariablesOverride
            break;
        }
    }

    $title = __( 'Awaiting Approval', 'custom-login-users' );

    add_submenu_page( 'users.php', $title, esc_html( $title ) . $badge, 'promote_users', clu_pending_list_path() );
}

add_filter( 'submenu_file', 'clu_highlight_pending_menu' );
function clu_highlight_pending_menu( $submenu_file ) {
    global $pagenow;

    if ( $pagenow === 'users.php' && clu_query_text( 'clu_status' ) === 'pending' ) {
        return clu_pending_list_path();
    }

    return $submenu_file;
}
