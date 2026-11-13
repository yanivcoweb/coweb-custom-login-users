<?php

defined( 'ABSPATH' ) || exit;

// Save the automatic approval
add_action( 'wp_ajax_clu_save_roles', 'clu_save_roles_handler' );
function clu_save_roles_handler() {
    check_ajax_referer( 'clu_roles_nonce', 'nonce' );

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( [ 'message' => 'Unauthorized.' ] );
    }

    update_option( 'clu_auto_approve', clu_post_text( 'auto_approve' ) === '1' ? '1' : '' );

    wp_send_json_success( [ 'message' => 'Saved.' ] );
}

// Create a new role
add_action( 'wp_ajax_clu_create_role', 'clu_create_role_handler' );
function clu_create_role_handler() {
    check_ajax_referer( 'clu_roles_nonce', 'nonce' );

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( [ 'message' => 'Unauthorized.' ] );
    }

    $slug         = sanitize_key( clu_post_text( 'role_slug' ) );
    $display_name = clu_post_text( 'role_display_name' );

    if ( $slug === '' || $display_name === '' ) {
        wp_send_json_error( [ 'message' => 'Role slug and display name are required.' ] );
    }

    if ( get_role( $slug ) ) {
        wp_send_json_error( [ 'message' => 'A role with this slug already exists.' ] );
    }

    $result = add_role( $slug, $display_name, [
        'read'         => false,
        'edit_posts'   => false,
        'delete_posts' => false,
    ] );

    if ( $result === null ) {
        wp_send_json_error( [ 'message' => 'Failed to create role.' ] );
    }

    wp_send_json_success( [
        'message' => 'Role "' . $display_name . '" created successfully.',
        'slug'    => $slug,
        'name'    => $display_name,
    ] );
}

// Delete a role
add_action( 'wp_ajax_clu_delete_role', 'clu_delete_role_handler' );
function clu_delete_role_handler() {
    check_ajax_referer( 'clu_roles_nonce', 'nonce' );

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( [ 'message' => 'Unauthorized.' ] );
    }

    $slug = sanitize_key( clu_post_text( 'role_slug' ) );

    if ( $slug === '' ) {
        wp_send_json_error( [ 'message' => 'Role slug is required.' ] );
    }

    // Prevent deleting built-in WordPress roles
    $protected = [ 'administrator', 'editor', 'author', 'contributor', 'subscriber' ];
    if ( in_array( $slug, $protected, true ) ) {
        wp_send_json_error( [ 'message' => 'Built-in WordPress roles cannot be deleted.' ] );
    }

    if ( ! get_role( $slug ) ) {
        wp_send_json_error( [ 'message' => 'Role not found.' ] );
    }

    if ( $slug === (string) get_option( 'default_role' ) ) {
        wp_send_json_error( [ 'message' => 'This is the default role of the site (Settings → General), which new users get.' ] );
    }

    if ( get_users( [ 'role' => $slug, 'number' => 1, 'fields' => 'ID' ] ) ) {
        wp_send_json_error( [ 'message' => 'Users still have this role, and they would be left with none. Move them to another role first.' ] );
    }

    remove_role( $slug );

    wp_send_json_success( [
        'message' => 'Role "' . $slug . '" deleted successfully.',
        'slug'    => $slug,
    ] );
}
