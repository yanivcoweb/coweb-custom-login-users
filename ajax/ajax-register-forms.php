<?php

defined( 'ABSPATH' ) || exit;

add_action( 'wp_ajax_clu_save_register_forms', 'clu_save_register_forms_handler' );
function clu_save_register_forms_handler() {
    check_ajax_referer( 'clu_register_forms_nonce', 'nonce' );

    if ( ! current_user_can( 'manage_options' ) ) {
        wp_send_json_error( [ 'message' => 'Unauthorized.' ] );
    }

    $rows = isset( $_POST['forms'] ) && is_string( $_POST['forms'] ) ? json_decode( wp_unslash( $_POST['forms'] ), true ) : null; // phpcs:ignore WordPress.Security

    if ( ! is_array( $rows ) ) {
        wp_send_json_error( [ 'message' => 'The form list could not be read.' ] );
    }

    $forms = clu_sanitize_register_forms( $rows );

    if ( is_wp_error( $forms ) ) {
        wp_send_json_error( [ 'message' => $forms->get_error_message() ] );
    }

    update_option( 'clu_register_forms', $forms );

    wp_send_json_success( [
        'message' => 'Register forms saved.',
        // Lets the screen learn the ids of new forms, to show their shortcodes and so
        // that saving again updates them. The main form is not among them.
        'ids'     => array_values( array_diff( array_column( $forms, 'id' ), [ 'main' ] ) ),
    ] );
}
