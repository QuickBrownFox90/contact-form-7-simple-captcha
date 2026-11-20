<?php

/**
 * Main Class
 *
 * @author   Patrick Mauersberger
 * @since    1.0.0
 *
 * @version  1.0.0
 *
 * @package  contact-form-7-simple-captcha
 */

namespace WPCF7_SimpleCaptcha;

require_once 'admin/options.php';
require_once 'admin/tag-generator.php';
require_once 'captcha.php';
require_once 'updater.php';

Class Main {
/**
     * Define constant variables
     * @since   1.0.0
     * @access  public
     *
     * @var     string  Prefix  The in plugin use prefix for methods or key or some other stuff like this
     * @var     string  Name    The plugin name
     */

    const Prefix = 'wpcf7sc';
    const Name   = 'WPCF7_SimpleCaptcha';


    /**
     * Define protected variables
     * @since   1.0.0
     * @access  protected
     *
     * @var     string  $url  The URL to the plugin folder, defined in main constructor
     */

    protected $url = '';


    /**
     * Main class constructor
     * @since   1.0.0
     * @access  public
     *
     * @return  void
     *
     * @todo    Find a way to add style only wenn a CF7 form is present
     */

    public function __construct() {

        $SC = $this;

        $SC->url = plugin_dir_url( __DIR__ );

        add_action( 'plugins_loaded', [ $this, 'load_textdomain' ] );
        add_filter( 'load_textdomain_mofile', [ $this, 'fallback_mofile' ], 10, 2 );

        new Admin\Options();
        new Admin\Tag_Generator();

        add_action( 'wpcf7_init', [ 'WPCF7_SimpleCaptcha\\Captcha', 'add_form_tag' ] );

        // GitHub-based self-hosted update check
        ( new Updater( WPCF7SC_UPDATE_URI, WPCF7SC_ROOT . '/contact-form-7-simple-captcha.php' ) )->init();

        if ( ! is_admin() && ! defined( 'DOING_AJAX' ) ) {
            // need a case for cf7 pages
            add_action( 'wp_head', [ $this, 'add_style' ] );

            add_filter( 'wpcf7_spam', [ 'WPCF7_SimpleCaptcha\\Captcha', 'validate_captcha_field' ], 11, 1 );
            add_filter( 'wpcf7_form_elements', [ 'WPCF7_SimpleCaptcha\\Captcha', 'add_captcha' ], 12, 1 );
        }

        if ( is_admin() )
            add_filter( 'wpcf7_config_validator_available_error_codes', [ 'WPCF7_SimpleCaptcha\\Captcha', 'wpcf7_config_validation' ], 11, 2 );
    }


    /**
     * Add action to load the text domain for translations
     * @since   1.0.0
     * @access  public
     *
     * @return  void
     */

    public function load_textdomain() {

        $SC = $this;

        load_plugin_textdomain(
            $SC::Prefix,
            false,
            plugin_basename( WPCF7SC_ROOT ) . '/languages/'
        );
    }


    /**
     * Use the translation of the same language for a regional locale that has no file of its own, e.g. de_CH, fr_BE,
     * nl_BE, es_MX or pt_PT. de_AT and de_CH use the formal German, every other locale the first file of its language.
     * @since   1.0.0
     * @access  public
     *
     * @param   string  $mofile  Path to the .mo file WordPress is looking for
     * @param   string  $domain  Text domain
     * @return  string           The path to the file that is used
     */

    public function fallback_mofile( $mofile, $domain ) {

        if ( self::Prefix !== $domain || file_exists( $mofile ) )
            return $mofile;

        if ( ! preg_match( '/' . self::Prefix . '-([a-z]{2,3})_[A-Za-z_]+\.mo$/', $mofile, $match ) )
            return $mofile;

        $dir      = WPCF7SC_ROOT . '/languages/';
        $locale   = basename( $mofile, '.mo' );
        $locale   = substr( $locale, strlen( self::Prefix ) + 1 );
        $special  = [ 'de_AT' => 'de_DE_formal', 'de_CH' => 'de_DE_formal' ];

        if ( isset( $special[ $locale ] ) && file_exists( $dir . self::Prefix . '-' . $special[ $locale ] . '.mo' ) )
            return $dir . self::Prefix . '-' . $special[ $locale ] . '.mo';

        $files = glob( $dir . self::Prefix . '-' . $match[1] . '_*.mo' );

        if ( $files ) {
            sort( $files );
            return $files[0];
        }

        return $mofile;
    }


    /**
     * Add inline style to the sites <head>
     * @since   1.0.0
     * @access  public
     *
     * @return  void
     */

    public function add_style() {

        $color = sanitize_hex_color( get_option( WPCF7SC_PREFIX . 'highlight_color', '' ) );
        $color = $color ? $color : 'currentcolor';

        $w = '.' . Captcha::css_class( 'wrap' );
        $r = '.' . Captcha::css_class( 'radio' );
        $l = '.' . Captcha::css_class( 'label' );

        $s  = '<style>';
        $s .= $w . ' br { display: none !important; }';
        $s .= $w . ' ~ .wpcf7-submit, ' . $w . ' ~ p > .wpcf7-submit { margin-top: 1rem }';
        $s .= '.multipage__tab:last-of-type .multipage__prev { margin-top: 3.5rem; }';
        $s .= $r . ' { display: none !important; }';
        $s .= $w . ' ' . $l . ' { position: relative; display: inline-block; }';
        $s .= $l . '::before { content: ""; display: inline-block; margin-right: 1ex; width: 1em; height: 1em; border: 1px solid currentcolor; }';
        $s .= $l . '::after { content: ""; display: none; position: absolute; left: .2em; top: .1em; width: 1em; height: .5em; border: 0 solid ' . $color . '; border-width: 0 0 2px 2px; transform: rotate( -45deg ); }';
        $s .= $r . ':checked ~ ' . $l . '::after { display: block; }';
        $s .= '</style>';

        echo $s;
    }


    /**
     * Get the plugin prefix
     * @since   1.0.0
     * @static
     *
     * @return  string  The prefix for option keys, meta keys and other stuff like this
     */

    static function get_prefix() {
        return self::Prefix . '_';
    }


    /**
     * Get the plugin prefix
     * @since   1.0.0
     * @static
     *
     * @return  string  The prefix for option keys, meta keys and other stuff like this
     */

    static function get_name() {
        return self::Name;
    }


    /**
     * Get the plugin path url
     * @since   1.0.0
     * @access  public
     *
     * @return  string  The path to the plugin directory
     */

    public function get_plugin_url() {
        return $this->url;
    }
}