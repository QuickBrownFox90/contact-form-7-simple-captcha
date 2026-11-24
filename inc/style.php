<?php

/**
 * Style of the captcha: the settings (one option as an array) and the inline css that is built from them. The front end
 * and the preview on the options page use the same css.
 *
 * @author   Patrick Mauersberger
 * @since    1.1.0
 *
 * @package  contact-form-7-simple-captcha
 */

namespace WPCF7_SimpleCaptcha;

class Style {

    /**
     * The styles that can be chosen
     * @since   1.1.0
     *
     * box       frame around the captcha, all fields apply
     * minimal   only the box and the text, the highlight color applies
     * unstyled  only what is needed to work, the theme does the rest
     */

    const Presets = [ 'box', 'minimal', 'unstyled' ];


    /**
     * The default settings
     * @since   1.1.0
     * @access  public
     * @static
     *
     * @return  array
     */

    public static function defaults() {
        return [
            'preset'       => 'box',
            'border_color' => '#cccccc',
            'background'   => '#ffffff',
            'border_width' => 1,
            'radius'       => 3,
            'padding'      => 1,
            'margin'       => 0,
            'highlight'    => '',
            'class'        => '',
        ];
    }


    /**
     * Clean the settings: every value is checked and kept in its limits, a value that does not fit gets its default.
     * Used as the sanitize callback of the option and again when it is read.
     * @since   1.1.0
     * @access  public
     * @static
     *
     * @param   mixed  $_input  The settings as they come from the form or the database
     * @return  array
     */

    public static function sanitize( $_input ) {

        $in  = is_array( $_input ) ? $_input : [];
        $def = self::defaults();
        $out = [];

        $out['preset'] = ( isset( $in['preset'] ) && in_array( $in['preset'], self::Presets, true ) ) ? $in['preset'] : $def['preset'];

        $color               = isset( $in['border_color'] ) ? sanitize_hex_color( (string) $in['border_color'] ) : '';
        $out['border_color'] = $color ? $color : $def['border_color'];

        $background        = isset( $in['background'] ) ? sanitize_hex_color( (string) $in['background'] ) : $def['background'];
        $out['background'] = $background ? $background : '';

        $out['border_width'] = isset( $in['border_width'] ) && is_numeric( $in['border_width'] ) ? max( 0, min( 10, (int) $in['border_width'] ) ) : $def['border_width'];
        $out['radius']       = isset( $in['radius'] ) && is_numeric( $in['radius'] ) ? max( 0, min( 50, (int) $in['radius'] ) ) : $def['radius'];
        $out['padding']      = isset( $in['padding'] ) && is_numeric( $in['padding'] ) ? max( 0, min( 3, round( (float) $in['padding'], 2 ) ) ) : $def['padding'];
        $out['margin']       = isset( $in['margin'] ) && is_numeric( $in['margin'] ) ? max( 0, min( 3, round( (float) $in['margin'], 2 ) ) ) : $def['margin'];

        $highlight        = isset( $in['highlight'] ) ? sanitize_hex_color( (string) $in['highlight'] ) : '';
        $out['highlight'] = $highlight ? $highlight : '';

        $out['class'] = self::sanitize_classes( isset( $in['class'] ) ? $in['class'] : '' );

        return $out;
    }


    /**
     * Clean a list of css classes separated by spaces
     * @since   1.1.0
     * @access  public
     * @static
     *
     * @param   string  $_classes
     * @return  string
     */

    public static function sanitize_classes( $_classes ) {

        $classes = preg_split( '/\s+/', trim( (string) $_classes ) );
        $classes = array_filter( array_map( 'sanitize_html_class', $classes ) );

        return implode( ' ', array_unique( $classes ) );
    }


    /**
     * The settings of this site
     * @since   1.1.0
     * @access  public
     * @static
     *
     * @return  array
     */

    public static function get() {
        return self::sanitize( get_option( WPCF7SC_PREFIX . 'style', [] ) );
    }


    /**
     * A number for css: always with a dot, never a comma of the locale, without trailing zeros
     * @since   1.1.0
     * @access  protected
     * @static
     *
     * @param   float  $_number
     * @return  string
     */

    protected static function number( $_number ) {
        return rtrim( rtrim( sprintf( '%.2F', $_number ), '0' ), '.' );
    }


    /**
     * The css of the captcha for the given settings
     * @since   1.1.0
     * @access  public
     * @static
     *
     * @param   array|null  $_style  Settings, the ones of this site if null
     * @return  string               Css without the <style> tag
     */

    public static function css( $_style = null ) {

        $st = ( null === $_style ) ? self::get() : self::sanitize( $_style );

        $w = '.' . Captcha::css_class( 'wrap' );
        $r = '.' . Captcha::css_class( 'radio' );
        $l = '.' . Captcha::css_class( 'label' );

        $unstyled = ( 'unstyled' === $st['preset'] );
        $box      = ( 'box' === $st['preset'] );
        $color    = ( ! $unstyled && $st['highlight'] ) ? $st['highlight'] : 'currentcolor';
        $pad      = $box ? (float) $st['padding'] : 0;

        // what every style needs: the radios are hidden, the label draws the box and the tick
        $s  = $w . ' br { display: none !important; }';
        $s .= $r . ' { display: none !important; }';
        $s .= $w . ' ' . $l . ' { position: relative; display: inline-block; }';
        $s .= $l . '::before { content: ""; display: inline-block; margin-right: 1ex; width: 1em; height: 1em; border: 1px solid currentcolor; }';
        $s .= $l . '::after { content: ""; display: none; position: absolute; left: ' . self::number( $pad + .2 ) . 'em; top: ' . self::number( $pad + .1 ) . 'em; width: 1em; height: .5em; border: 0 solid ' . $color . '; border-width: 0 0 2px 2px; transform: rotate( -45deg ); }';
        $s .= $r . ':checked ~ ' . $l . '::after { display: block; }';

        if ( $unstyled )
            return $s;

        $s .= $w . ' ~ .wpcf7-submit, ' . $w . ' ~ p > .wpcf7-submit { margin-top: 1rem }';
        $s .= '.multipage__tab:last-of-type .multipage__prev { margin-top: 3.5rem; }';

        if ( $box ) {
            $s .= $w . ' { display: inline-block; ' . ( $st['background'] ? 'background-color: ' . $st['background'] . '; ' : '' ) . 'border: ' . (int) $st['border_width'] . 'px solid ' . $st['border_color'] . '; border-radius: ' . (int) $st['radius'] . 'px; margin: ' . self::number( $st['margin'] ) . 'em 0; }';
            $s .= $w . ':focus-within { border-color: ' . $color . '; }';
            $s .= $l . ' { padding: ' . self::number( $pad ) . 'em; }';
        }

        else
            $s .= $l . ':focus-visible { outline: 2px solid ' . $color . '; outline-offset: 2px; }';

        return $s;
    }
}
