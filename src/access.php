<?php

defined( 'ABSPATH' ) || exit;

/*
 * Where a registration stands, for the plugin and for the site's own code.
 *
 * Since 2.0 the plugin stops nobody: a user whose registration is pending, rejected or
 * not confirmed can log in, reset a password and open wp-admin as far as their role
 * allows, and no content is hidden. A site that wants any of that does it in its theme,
 * with these functions and clu_get_user_status(), for example on the `authenticate` and
 * `allow_password_reset` filters.
 */

/**
 * Tells whether a user is still waiting for approval.
 *
 * @param mixed $user WP_User, or anything else, which never counts as pending.
 * @return bool
 */
function clu_user_is_pending( $user ) {
    $pending = $user instanceof WP_User && $user->exists() && clu_get_user_status( $user->ID ) === 'pending';

    /**
     * Filters whether a user counts as waiting for approval.
     *
     * @param bool  $pending
     * @param mixed $user
     */
    return (bool) apply_filters( 'clu_user_is_pending', $pending, $user );
}

/**
 * Tells whether a user's registration was rejected.
 *
 * @param mixed $user WP_User, or anything else, which never counts as rejected.
 * @return bool
 */
function clu_user_is_rejected( $user ) {
    return $user instanceof WP_User && $user->exists() && clu_get_user_status( $user->ID ) === 'rejected';
}

/**
 * Tells whether a user has not confirmed their email address yet. Until then the
 * registration has not reached the admin.
 *
 * @param mixed $user WP_User, or anything else, which never counts as unconfirmed.
 * @return bool
 */
function clu_user_is_unconfirmed( $user ) {
    return $user instanceof WP_User && $user->exists() && clu_get_user_status( $user->ID ) === 'unconfirmed';
}

/**
 * Returns the key of the message that says where an account stands, for a theme that
 * turns such accounts away: clu_render_message() gives the text.
 *
 * @param mixed $user
 * @return string account_rejected, account_unconfirmed, account_pending, or '' for any other account.
 */
function clu_account_state_message( $user ) {
    if ( clu_user_is_rejected( $user ) ) {
        return 'account_rejected';
    }

    if ( clu_user_is_unconfirmed( $user ) ) {
        return 'account_unconfirmed';
    }

    return clu_user_is_pending( $user ) ? 'account_pending' : '';
}

/**
 * Tells whether a user signed up through the plugin, so that its emails follow their
 * status. Someone whose status was set for another reason gets none of them.
 *
 * @param int $user_id
 * @return bool
 */
function clu_user_signed_up_here( $user_id ) {
    return (string) get_user_meta( (int) $user_id, 'clu_signup', true ) !== '';
}

/**
 * Starts the approval of a new account: asks it to confirm its address first if the site
 * wants that, otherwise marks it pending and tells the admin (or approves it at once
 * with automatic approval).
 *
 * @param WP_User $user
 * @return bool False only when the confirmation email could not be sent.
 */
function clu_start_registration( $user ) {
    update_user_meta( $user->ID, 'clu_signup', time() );

    if ( clu_email_confirmation_is_on() ) {
        return clu_ask_to_confirm_email( $user );
    }

    clu_set_user_status( $user->ID, 'pending' );
    clu_send_registration_emails( get_userdata( $user->ID ) );

    return true;
}

/**
 * Sends sign-ups from the form built into WordPress through approval too. On a site that
 * takes registrations that form is always there at wp-login.php?action=register, and it
 * would create an account that no admin hears about.
 */
add_action( 'register_new_user', 'clu_send_core_signup_to_approval', 1 );
function clu_send_core_signup_to_approval( $user_id ) {
    $user = get_userdata( $user_id );

    if ( ! $user ) {
        return;
    }

    // Its emails would tell the user to choose a password; the approval email does that.
    remove_action( 'register_new_user', 'wp_send_new_user_notifications' );

    if ( clu_get_user_status( $user->ID ) !== '' ) {
        return;
    }

    clu_start_registration( $user );
}
