<?php

/**
 * Captcha class
 *
 * @author   Patrick Mauersberger
 * @since    1.0.0
 *
 * @package  contact-form-7-simple-captcha
 */

namespace WPCF7_SimpleCaptcha;

class Captcha {

    /**
     * Number of radio buttons in the captcha, the first and the last one are always wrong
     * @since   1.0.0
     */

    const Radios = 5;


    /**
     * Number of valid values per day, a render picks one of them at random
     * @since   1.0.0
     */

    const Slots = 4;


    /**
     * Was a captcha rendered in this request? The script for the keyboard is only printed then.
     * @since   1.1.0
     */

    protected static $rendered = false;


    /**
     * @since   1.1.0
     * @access  public
     * @static
     *
     * @return  bool
     */

    public static function was_rendered() {
        return self::$rendered;
    }


    /**
     * The secret of this site: a random value in its own option, never part of the code. Deleting the option
     * revokes every value that was handed out, a new secret is created at the next call.
     * @since   1.0.0
     * @access  protected
     * @static
     *
     * @return  string  Hex string
     */

    protected static function secret() {

        static $secret = null;

        if ( null !== $secret )
            return $secret;

        $secret = get_option( WPCF7SC_PREFIX . 'secret' );

        if ( ! is_string( $secret ) || strlen( $secret ) < 64 ) {

            // add_option does nothing if another request was faster, so the value is read again
            add_option( WPCF7SC_PREFIX . 'secret', bin2hex( random_bytes( 32 ) ), '', true );

            $secret = (string) get_option( WPCF7SC_PREFIX . 'secret' );
        }

        return $secret;
    }


    /**
     * Derive a value from the secret
     * @since   1.0.0
     * @access  protected
     * @static
     *
     * @param   string  $_context  What the value is used for
     * @param   int     $_length   Length of the hex string
     * @return  string
     */

    protected static function derive( $_context, $_length = 12 ) {
        return substr( hash_hmac( 'sha256', $_context, self::secret() ), 0, $_length );
    }


    /**
     * Make a hex string usable as name, id or class: it must not start with a digit
     * @since   1.0.0
     * @access  protected
     * @static
     *
     * @param   string  $_hex
     * @return  string
     */

    protected static function opaque( $_hex ) {
        return strtr( substr( $_hex, 0, 1 ), '0123456789', 'ghijklmnop' ) . substr( $_hex, 1 );
    }


    /**
     * The name of the radio group, the same on every request of this site
     * @since   1.0.0
     * @access  public
     * @static
     *
     * @return  string
     */

    public static function field_name() {
        return self::opaque( self::derive( 'name' ) );
    }


    /**
     * The css class for one part of the captcha ( 'wrap', 'radio' or 'label' ), the same on every request of this site
     * @since   1.0.0
     * @access  public
     * @static
     *
     * @param   string  $_part
     * @return  string
     */

    public static function css_class( $_part ) {
        return self::opaque( self::derive( 'class-' . $_part, 10 ) );
    }


    /**
     * Number of days a value stays valid. A render uses a value of the current day, so a cached page is good for
     * this number of days minus one.
     * @since   1.0.0
     * @access  public
     * @static
     *
     * @return  int
     */

    public static function lifetime_days() {

        $days = (int) get_option( WPCF7SC_PREFIX . 'lifetime_days', 7 );

        return max( 2, min( 90, (int) apply_filters( 'wpcf7sc_lifetime_days', $days ) ) );
    }


    /**
     * Replace the secret by a new one: every value that was handed out becomes invalid, as well as the field name and the
     * css classes
     * @since   1.0.0
     * @access  public
     * @static
     *
     * @return  void
     */

    public static function regenerate_secret() {
        update_option( WPCF7SC_PREFIX . 'secret', bin2hex( random_bytes( 32 ) ), true );
    }


    /**
     * The valid value for a day and a slot
     * @since   1.0.0
     * @access  protected
     * @static
     *
     * @param   int  $_day   Days since 1970-01-01 (UTC)
     * @param   int  $_slot  0 to Slots - 1
     * @return  string
     */

    protected static function value( $_day, $_slot ) {
        return self::derive( 'value|' . (int) $_day . '|' . (int) $_slot, 20 );
    }


    /**
     * A random hex string
     * @since   1.0.0
     * @access  protected
     * @static
     *
     * @param   int  $_bytes
     * @return  string
     */

    protected static function random_hex( $_bytes = 10 ) {
        return bin2hex( random_bytes( $_bytes ) );
    }


    /**
     * Render the captcha template
     *
     * The radios have no order that can be guessed: the first one is wrong and not selected, the last one is wrong and
     * selected, the right one stands somewhere between. The label points to the right one by id and stands behind it,
     * but in front of the last radio (the checkmark is drawn by the css sibling selector of the checked radio).
     * @since   1.0.0
     * @since   1.1.0  Added the parameter $_class; marks that a captcha was rendered
     * @access  public
     * @static
     *
     * @param   string  $_class  Additional css classes of the wrapper (from the tag), besides the one of the settings
     * @return  string           The captcha HTML
     */

    public static function render( $_class = '' ) {

        $tpl_path = WPCF7SC_ROOT . '/template/captcha.php';

        if ( ! file_exists( $tpl_path ) )
            return '';

        self::$rendered = true;

        $last    = self::Radios - 1;
        $right   = random_int( 1, $last - 1 );
        $label   = random_int( $right, $last - 1 );
        $correct = self::value( (int) floor( time() / DAY_IN_SECONDS ), random_int( 0, self::Slots - 1 ) );

        $name  = self::field_name();
        $items = [];

        for ( $i = 0; $i < self::Radios; $i++ ) {

            $items[] = [
                'type'    => 'radio',
                'id'      => self::opaque( self::random_hex( 6 ) ),
                'value'   => ( $i === $right ) ? $correct : self::random_hex(),
                'checked' => ( $i === $last ),
                'right'   => ( $i === $right ),
            ];

            if ( $i === $label )
                $items[] = [ 'type' => 'label' ];
        }

        $for = '';

        foreach ( $items as $item )
            if ( ! empty( $item['right'] ) )
                $for = $item['id'];

        $args = [
            'name'  => $name,
            'items' => $items,
            'for'   => $for,
            'wrap'  => trim( self::css_class( 'wrap' ) . ' ' . Style::sanitize_classes( Style::get()['class'] . ' ' . $_class ) ),
            'radio' => self::css_class( 'radio' ),
            'label' => self::css_class( 'label' ),
        ];

        extract( $args );

        ob_start();
            include $tpl_path;
        return ob_get_clean();
    }


    /**
     * Find the position where the captcha is inserted: in front of the last submit element
     * @since   1.0.0
     * @access  protected
     * @static
     *
     * Order of preference: explicit submit (<input type="submit"> or <button type="submit">), then a <button> without
     * a type (the browser handles it as submit), then the end of the form.
     *
     * @param   string  $_form  formated HTML string with the form data
     * @return  int             Byte offset in $_form
     */

    protected static function find_position( $_form ) {

        $patterns = [
            '/<input\b[^>]*\stype\s*=\s*(["\']?)submit\1[^>]*>|<button\b[^>]*\stype\s*=\s*(["\']?)submit\2[^>]*>/i',
            '/<button\b(?![^>]*\stype\s*=)[^>]*>/i',
        ];

        foreach ( $patterns as $pattern ) {

            if ( preg_match_all( $pattern, $_form, $matches, PREG_OFFSET_CAPTURE ) )
                return end( $matches[0] )[1];
        }

        return strlen( $_form );
    }


    /**
     * Register the form tag [simple_captcha]: the captcha is placed exactly where the tag stands in the form
     * @since   1.0.0
     * @access  public
     * @static
     *
     * @return  void
     */

    static function add_form_tag() {

        if ( function_exists( 'wpcf7_add_form_tag' ) )
            wpcf7_add_form_tag( 'simple_captcha', [ __CLASS__, 'form_tag_handler' ] );
    }


    /**
     * Form tag handler for [simple_captcha], the option class: of the tag adds css classes to the wrapper
     * @since   1.0.0
     * @since   1.1.0  Added the parameter $_tag, its option class: is added to the wrapper
     * @access  public
     * @static
     *
     * @param   \WPCF7_FormTag|null  $_tag  The form tag
     * @return  string                     The captcha HTML
     */

    static function form_tag_handler( $_tag = null ) {

        $class = ( is_object( $_tag ) && method_exists( $_tag, 'get_class_option' ) ) ? (string) $_tag->get_class_option( '' ) : '';

        return self::render( $class );
    }


    /**
     * Add the captcha if the form does not contain the tag [simple_captcha]: above the submit button, or at the end
     * of the form if there is no submit element
     * @since   1.0.0
     * @access  public
     * @static
     *
     * @param   string  $_form  formated HTML string with the form data
     * @return  string  $form   customized formated HTML string with the extended form data
     */

    static function add_captcha( $_form ) {

        // the tag [simple_captcha] was already replaced by the captcha, its position is set by the form
        if ( false !== strpos( $_form, self::css_class( 'wrap' ) ) )
            return $_form;

        return substr_replace( $_form, self::render(), self::find_position( $_form ), 0 );
    }


    /**
     * Validate the captcha field on form submit
     * @since   1.0.0
     * @access  public
     * @static
     *
     * @param   bool  $_spam  WPCF7 is spam variable
     * @return  bool          Return true if spammer is detected
     */

    static function validate_captcha_field( $_spam ) {

        $name = self::field_name();

        if ( ! isset( $_POST[ $name ] ) || ! is_string( $_POST[ $name ] ) )
            return true;

        $posted = $_POST[ $name ];
        $today  = (int) floor( time() / DAY_IN_SECONDS );
        $valid  = false;

        for ( $day = $today; $day > $today - self::lifetime_days(); $day-- )
            for ( $slot = 0; $slot < self::Slots; $slot++ )
                if ( hash_equals( self::value( $day, $slot ), $posted ) )
                    $valid = true;

        if ( ! $valid )
            return true;

        return $_spam;
    }


    /**
     * Remove the unsafe email warning from the CF7 config validator
     * @since   1.0.0
     * @access  public
     * @static
     *
     * @param   array   $codes         Available error codes
     * @param   object  $contact_form  The contact form
     * @return  array                  Error codes without 'unsafe_email_without_protection'
     */

    static function wpcf7_config_validation( $codes, $contact_form ) {
        return array_diff( $codes, [ 'unsafe_email_without_protection' ] );
    }
}
