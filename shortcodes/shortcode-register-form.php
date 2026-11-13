<?php

defined( 'ABSPATH' ) || exit;

/**
 * Renders a registration form: the main one, or with form="its-id" another form of
 * the Register Forms tab.
 *
 * @param array|string $attributes Shortcode attributes.
 * @return string
 */
add_shortcode( 'custom-register-form', 'clu_render_register_form' );
add_shortcode( 'clu_register_form', 'clu_render_register_form' );
function clu_render_register_form( $attributes ) {
    // Where the link that confirms an email address leads. The registration is done.
    $notice = clu_email_confirmation_notice();

    if ( $notice !== '' ) {
        wp_enqueue_style( 'clu-forms' );
        return $notice;
    }

    if ( is_user_logged_in() ) {
        return clu_render_message( 'user_logged_in_register' );
    }

    if ( ! get_option( 'users_can_register' ) ) {
        return clu_render_message( 'registration_disabled' );
    }

    $attributes = clu_parse_form_attributes( $attributes );
    $form       = clu_get_register_form( sanitize_key( $attributes['form'] ) );

    // A form removed since the page was made. Only whoever can fix it is told.
    if ( ! $form ) {
        return current_user_can( 'manage_options' ) ? '<p>' . esc_html( sprintf( 'Custom Login Users: there is no register form "%s". See the Register Forms tab of the settings.', $attributes['form'] ) ) . '</p>' : '';
    }

    $attributes['form'] = $form;

    clu_enqueue_form_assets();
    clu_enqueue_region_script( clu_get_register_form_fields( $form['id'] ) );

    return clu_get_template_html( 'form_register', $attributes );
}
