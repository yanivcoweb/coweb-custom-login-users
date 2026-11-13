<?php

defined( 'ABSPATH' ) || exit;

/*
 * A site can have several register forms, for example one for private customers and
 * one for businesses. Each form has a role, which whoever signs up through it gets, and
 * its own choice from the fields of the Register Fields tab.
 *
 * The main form is always there. Its fields are the ones turned on on the Register
 * Fields tab, and its texts are those of the Register Form tab. The other forms are
 * made on the Register Forms tab and shown with [clu_register_form form="its-id"].
 */

/**
 * Returns the register forms, the main one first.
 *
 * @return array[] Form id => [ id, name, role, title, button, fields ]. An empty role
 *                 means the default role of the site. Fields are key => 'required' or
 *                 'optional', and are not used for the main form.
 */
function clu_get_register_forms() {
    $forms = [
        'main' => [
            'id'     => 'main',
            'name'   => 'Main form',
            'role'   => '',
            'title'  => '',
            'button' => '',
            'fields' => [],
        ],
    ];

    $saved = get_option( 'clu_register_forms' );

    foreach ( is_array( $saved ) ? $saved : [] as $row ) {
        $id = is_array( $row ) && isset( $row['id'] ) ? sanitize_key( (string) $row['id'] ) : '';

        if ( $id === '' || ( $id !== 'main' && isset( $forms[ $id ] ) ) ) {
            continue;
        }

        $role = isset( $row['role'] ) ? sanitize_key( (string) $row['role'] ) : '';

        if ( $id === 'main' ) {
            $forms['main']['role'] = $role;
            continue;
        }

        $fields = [];

        foreach ( isset( $row['fields'] ) && is_array( $row['fields'] ) ? $row['fields'] : [] as $key => $state ) {
            $fields[ (string) $key ] = $state === 'required' ? 'required' : 'optional';
        }

        $forms[ $id ] = [
            'id'     => $id,
            'name'   => (string) ( $row['name'] ?? $id ),
            'role'   => $role,
            'title'  => (string) ( $row['title'] ?? '' ),
            'button' => (string) ( $row['button'] ?? '' ),
            'fields' => $fields,
        ];
    }

    return $forms;
}

/**
 * Returns one register form.
 *
 * @param string $id Form id; empty for the main form.
 * @return array|null Null when there is no such form.
 */
function clu_get_register_form( $id ) {
    $id = is_string( $id ) && $id !== '' ? $id : 'main';

    return clu_get_register_forms()[ $id ] ?? null;
}

/**
 * Tells whether a role may be given to whoever fills in a public form.
 *
 * @param string $slug
 * @return bool
 */
function clu_role_fits_register_form( $slug ) {
    return (bool) get_role( $slug ) && ! clu_role_is_privileged( $slug ) && ! isset( clu_old_state_roles()[ $slug ] );
}

/**
 * Returns the role of whoever signs up through a form: the role chosen for the form,
 * or the default role of the site when none was chosen.
 *
 * Checked here and not only when the settings are saved, because a role can gain
 * capabilities, or be deleted, after it was chosen.
 *
 * @param array $form
 * @return string
 */
function clu_register_form_role( $form ) {
    /**
     * Filters the role of whoever signs up through a register form.
     *
     * @param string $role Role slug; empty for the default role of the site.
     * @param array  $form
     */
    $role = (string) apply_filters( 'clu_register_form_role', (string) $form['role'], $form );

    return $role !== '' && clu_role_fits_register_form( $role ) ? $role : clu_get_registration_role();
}

/**
 * Returns the roles a form can be given, for the settings screen.
 *
 * @return array<string,string> Role slug => name.
 */
function clu_register_form_role_choices() {
    $choices = [];

    foreach ( wp_roles()->roles as $slug => $role ) {
        if ( clu_role_fits_register_form( $slug ) ) {
            $choices[ $slug ] = translate_user_role( $role['name'] );
        }
    }

    return $choices;
}

/**
 * Returns the form a user signed up through.
 *
 * @param int $user_id
 * @return array|null Null for a user who did not sign up through a form of the plugin,
 *                    or whose form was removed since.
 */
function clu_get_user_register_form( $user_id ) {
    $id = get_user_meta( (int) $user_id, 'clu_register_form', true );

    return is_string( $id ) && $id !== '' ? clu_get_register_form( $id ) : null;
}

/**
 * Cleans the forms sent by the settings screen.
 *
 * @param array $rows
 * @return array[]|WP_Error The list to save, the main form first.
 */
function clu_sanitize_register_forms( $rows ) {
    $existing = clu_get_register_forms();
    $known    = array_column( clu_get_register_fields(), 'key' );
    $taken    = array_keys( $existing );
    $main     = [ 'id' => 'main', 'role' => '' ];
    $clean    = [];
    $seen     = [];

    foreach ( $rows as $row ) {
        if ( ! is_array( $row ) ) {
            continue;
        }

        $id   = isset( $row['id'] ) && is_string( $row['id'] ) ? $row['id'] : '';
        $role = isset( $row['role'] ) && is_string( $row['role'] ) ? sanitize_key( $row['role'] ) : '';
        $name = isset( $row['name'] ) && is_string( $row['name'] ) ? sanitize_text_field( $row['name'] ) : '';

        if ( $role !== '' && ! get_role( $role ) ) {
            return new WP_Error( 'clu_form_role', sprintf( 'The role "%s" does not exist.', $role ) );
        }

        if ( $role !== '' && ! clu_role_fits_register_form( $role ) ) {
            return new WP_Error( 'clu_form_role', sprintf( 'The role "%s" can manage the site or its users, so a public form cannot hand it out.', $role ) );
        }

        if ( $id === 'main' ) {
            $main['role'] = $role;
            continue;
        }

        if ( $name === '' ) {
            return new WP_Error( 'clu_form_name', 'Every form needs a name.' );
        }

        // An id comes only from a form that already exists, never from the request.
        if ( ! isset( $existing[ $id ] ) || isset( $seen[ $id ] ) ) {
            $id      = clu_new_register_form_id( $name, $taken );
            $taken[] = $id;
        }

        $seen[ $id ] = true;
        $fields      = [];

        foreach ( isset( $row['fields'] ) && is_array( $row['fields'] ) ? $row['fields'] : [] as $key => $state ) {
            if ( in_array( (string) $key, $known, true ) && $key !== 'email' ) {
                $fields[ (string) $key ] = $state === 'required' ? 'required' : 'optional';
            }
        }

        $clean[] = [
            'id'     => $id,
            'name'   => $name,
            'role'   => $role,
            'title'  => isset( $row['title'] ) && is_string( $row['title'] ) ? sanitize_text_field( $row['title'] ) : '',
            'button' => isset( $row['button'] ) && is_string( $row['button'] ) ? sanitize_text_field( $row['button'] ) : '',
            'fields' => $fields,
        ];
    }

    return array_merge( [ $main ], $clean );
}

/**
 * Builds the id of a new form, which its shortcode names. It stays the same when the
 * form is renamed later, so that the pages showing the form keep working.
 *
 * @param string   $name
 * @param string[] $taken Ids already in use.
 * @return string
 */
function clu_new_register_form_id( $name, $taken ) {
    // A name in another script such as Hebrew leaves nothing to build an id from.
    $slug = trim( sanitize_key( str_replace( ' ', '-', remove_accents( $name ) ) ), '_-' );
    $base = $slug !== '' ? substr( $slug, 0, 40 ) : 'form';
    $id   = $base;
    $next = 2;

    while ( in_array( $id, $taken, true ) ) {
        $id = $base . '-' . $next;
        $next++;
    }

    return $id;
}

/**
 * Returns the form a user signed up through as a line for the people who decide on the
 * registration, on a site that has more than the main form.
 *
 * @param WP_User $user
 * @return array[] In the shape of clu_get_user_details(): empty, or one line.
 */
function clu_get_user_form_detail( $user ) {
    $form = $user && count( clu_get_register_forms() ) > 1 ? clu_get_user_register_form( $user->ID ) : null;

    if ( ! $form ) {
        return [];
    }

    return [
        'clu_register_form' => [
            'label' => __( 'Registration form', 'custom-login-users' ),
            'value' => $form['id'] === 'main' ? __( 'Main form', 'custom-login-users' ) : $form['name'],
        ],
    ];
}
