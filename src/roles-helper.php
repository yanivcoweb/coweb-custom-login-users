<?php

defined( 'ABSPATH' ) || exit;

/*
 * Since 2.0 the plugin has no roles of its own. Where a registration stands is a status
 * on the user (see user-status.php), and a new user gets the site's default role.
 */

/**
 * Returns the roles versions before 2.0 used to mark where an account stands, and the
 * status each one became.
 *
 * @return array<string,string> role slug => status
 */
function clu_old_state_roles() {
    return [
        'unconfirmed_user' => 'unconfirmed',
        'pending_user'     => 'pending',
        'rejected_user'    => 'rejected',
        'approved_user'    => 'approved',
    ];
}

/**
 * Tells whether a role is too powerful to be handed to whoever fills in the public form.
 *
 * @param string $slug
 * @return bool
 */
function clu_role_is_privileged( $slug ) {
    $role = get_role( $slug );

    if ( ! $role ) {
        return false;
    }

    $capabilities = (array) apply_filters( 'clu_privileged_capabilities', [
        'manage_options',
        'activate_plugins',
        'install_plugins',
        'edit_files',
        'create_users',
        'edit_users',
        'delete_users',
        'promote_users',
        'unfiltered_html',
        'edit_others_posts',
        'manage_woocommerce',
    ] );

    foreach ( $capabilities as $capability ) {
        if ( $role->has_cap( $capability ) ) {
            return true;
        }
    }

    return false;
}

/**
 * Returns the role for a public sign-up: the site's default role (Settings → General).
 *
 * Checked here, because that setting may name a role that can manage the site, or one
 * that no longer exists.
 *
 * @return string
 */
function clu_get_registration_role() {
    $role = (string) apply_filters( 'clu_registration_role', (string) get_option( 'default_role', 'subscriber' ) );

    if ( get_role( $role ) && ! clu_role_is_privileged( $role ) && ! isset( clu_old_state_roles()[ $role ] ) ) {
        return $role;
    }

    return 'subscriber';
}

/**
 * Moves a site from the roles of versions before 2.0 to the status. Runs once, on update.
 *
 * Whoever holds one of the four old roles gets the matching status and the site's
 * default role instead, and the roles are removed. A site that had chosen other roles
 * as its pending or approved role keeps those roles on its users, who get the status
 * next to them, and its approved role becomes the default role of the site. A status a
 * user already has is left as it is.
 */
function clu_migrate_roles_to_status() {
    $default = clu_get_registration_role();

    if ( isset( clu_old_state_roles()[ (string) get_option( 'default_role' ) ] ) ) {
        update_option( 'default_role', $default );
    }

    $saved  = get_option( 'clu_roles' );
    $custom = [];

    foreach ( [ 'pending_role' => 'pending', 'approved_role' => 'approved' ] as $slot => $status ) {
        $slug = is_array( $saved ) && ! empty( $saved[ $slot ] ) ? (string) $saved[ $slot ] : '';

        if ( $slug !== '' && ! isset( clu_old_state_roles()[ $slug ] ) && get_role( $slug ) ) {
            $custom[ $slug ] = $status;
        }
    }

    // The strongest last, so that someone approved who also kept an older role ends approved.
    foreach ( $custom + clu_old_state_roles() as $slug => $status ) {
        $own = isset( clu_old_state_roles()[ $slug ] );

        foreach ( get_users( [ 'role' => $slug ] ) as $user ) {
            if ( $own || get_user_meta( $user->ID, 'clu_status', true ) === '' ) {
                update_user_meta( $user->ID, 'clu_status', $status );
            }

            if ( ! $own ) {
                continue;
            }

            // They signed up through the plugin, so its emails still follow their status.
            update_user_meta( $user->ID, 'clu_signup', time() );
            $user->remove_role( $slug );

            if ( ! array_diff( (array) $user->roles, array_keys( clu_old_state_roles() ) ) ) {
                $user->set_role( $default );
            }
        }
    }

    foreach ( array_keys( clu_old_state_roles() ) as $slug ) {
        if ( get_role( $slug ) && ! get_users( [ 'role' => $slug, 'number' => 1, 'fields' => 'ID' ] ) ) {
            remove_role( $slug );
        }
    }

    // A site whose approved users had a role of their own, such as Customer: new users
    // get that role from now on, since nothing else hands it out any more.
    $approved_role = (string) array_search( 'approved', $custom, true );

    if ( $approved_role !== '' && ! clu_role_is_privileged( $approved_role ) ) {
        update_option( 'default_role', $approved_role );
    }

    delete_option( 'clu_roles' );
    delete_transient( 'clu_pending_count' );
    delete_transient( 'clu_status_pending_count' );
}
