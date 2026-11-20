<?php

/**
 * Template file for the simple "I am not a robot" captcha
 *
 * Variables: $name (radio group), $items (radios and the label in their order), $for (id of the right radio),
 * $wrap, $radio, $label (css classes)
 *
 * @author   Patrick Mauersberger
 * @since    1.0.0
 *
 * @package  contact-form-7-simple-captcha
 */

?>

<div class="<?php echo esc_attr( $wrap ); ?>">
<?php foreach ( $items as $item ) : ?>
<?php if ( 'radio' === $item['type'] ) : ?>
    <input id="<?php echo esc_attr( $item['id'] ); ?>" class="<?php echo esc_attr( $radio ); ?>" type="radio" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $item['value'] ); ?>" autocomplete="off"<?php echo $item['checked'] ? ' checked="checked"' : ''; ?> />
<?php else : ?>
    <label for="<?php echo esc_attr( $for ); ?>" class="<?php echo esc_attr( $label ); ?>">
        <?php _e( 'I am not a robot', 'wpcf7sc' ); ?>
    </label>
<?php endif; ?>
<?php endforeach; ?>
</div>
