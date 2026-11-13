<?php
defined( 'ABSPATH' ) || exit;

$text = function ( $key ) {
    return clu_form_text( 'clu_register_form', $key );
};

// A template in a theme may be included directly, without a form: the main one.
$form   = isset( $attributes['form'] ) && is_array( $attributes['form'] ) ? $attributes['form'] : clu_get_register_form( 'main' );
$title  = $form['title'] !== '' ? $form['title'] : $text( 'register_title' );
$button = $form['button'] !== '' ? $form['button'] : $text( 'register_btn_text' );
?>
<div id="register-form" class="widecolumn clu-form-wrap clu-register-form--<?php echo esc_attr( $form['id'] ); ?>">

    <?php if ( $attributes['show_title'] ) : ?>
        <h3><?php echo esc_html( $title ); ?></h3>
    <?php endif; ?>

    <form id="signupform" class="form-clu form-validation" method="post" novalidate>
        <input type="hidden" name="action" value="ajax_register_user">
        <input type="hidden" name="clu_form" value="<?php echo esc_attr( $form['id'] ); ?>">
        <input type="hidden" name="register_nonce" value="<?php echo esc_attr( wp_create_nonce( 'register_form_nonce' ) ); ?>">

        <?php // Trap for bots: hidden from people, and registration stops if it is filled. ?>
        <div class="clu-hp" aria-hidden="true">
            <label for="clu_website">Website</label>
            <input type="text" name="clu_website" id="clu_website" value="" tabindex="-1" autocomplete="off">
        </div>

        <?php echo clu_render_form_fields( clu_get_register_form_fields( $form['id'] ), [], $text( 'required_field_error' ), $text( 'email_invalid_error' ) ); // phpcs:ignore WordPress.Security.EscapeOutput ?>

        <?php echo clu_captcha_html(); // phpcs:ignore WordPress.Security.EscapeOutput ?>

        <p class="signup-submit">
            <input type="submit" class="register-button" value="<?php echo esc_attr( $button ); ?>">
        </p>

        <div id="signupform-register-response" class="clu-response" role="status"></div>
    </form>
</div>
