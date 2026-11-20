<?php

/**
 * Options page
 *
 * @author      Patrick Mauersberger
 * @since       1.0.0
 * @package     contact-form-7-simple-captcha
 * @subpackage  admin
 */

namespace WPCF7_SimpleCaptcha\Admin;

use WPCF7_SimpleCaptcha\Captcha;

Class Options {

    const Group = 'wpcf7sc_settings';
    const Page  = 'wpcf7sc_options';

    public function __construct() {
        add_action( 'admin_menu', [ $this, 'add_to_menu' ], 20 );
        add_action( 'admin_init', [ $this, 'options_setup' ] );
        add_action( 'admin_post_wpcf7sc_regenerate_secret', [ $this, 'handle_regenerate' ] );
    }


    /**
     * Add the options page below the menu "Contact" of Contact Form 7
     * @since   1.0.0
     */

    public function add_to_menu() {

        // the parent menu exists only while Contact Form 7 is active
        if ( ! defined( 'WPCF7_VERSION' ) )
            return;

        // the top level menu of Contact Form 7 is "wpcf7-dashboard", older versions use "wpcf7"
        $parent = 'wpcf7';

        foreach ( (array) $GLOBALS['menu'] as $item ) {
            if ( isset( $item[2] ) && 'wpcf7-dashboard' === $item[2] )
                $parent = 'wpcf7-dashboard';
        }

        add_submenu_page(
            $parent,
            __( 'Contact Form 7 Simple Captcha', 'wpcf7sc' ),
            __( 'CF7 Simple Captcha', 'wpcf7sc' ),
            'manage_options',
            self::Page,
            [ $this, 'add_page' ]
        );
    }


    /**
     * The options page template
     * @since   1.0.0
     */

    public function add_page() {
        if ( ! current_user_can( 'manage_options' ) )
            wp_die( esc_html__( 'You did not have the permission to edit this page', 'wpcf7sc' ) );

        ?>
        <div class="wrap">
            <h1><?php esc_html_e( 'Contact Form 7 › Simple Captcha', 'wpcf7sc' ); ?></h1>
            <?php settings_errors(); ?>
            <?php if ( ! empty( $_GET['wpcf7sc_regenerated'] ) ) : ?>
                <div class="notice notice-success is-dismissible"><p><?php esc_html_e( 'A new secret was created. All values that were handed out so far are invalid now.', 'wpcf7sc' ); ?></p></div>
            <?php endif; ?>
            <form action="options.php" method="post">
                <?php
                    settings_fields( self::Group );
                    do_settings_sections( self::Page );
                    submit_button();
                ?>
            </form>

            <hr />

            <h2><?php esc_html_e( 'Secret', 'wpcf7sc' ); ?></h2>
            <p><?php esc_html_e( 'The captcha values, the field name and the css classes are derived from a random secret that is stored in the database only. A new secret makes all of them invalid at once. Pages in a cache keep the old values until the cache is cleared.', 'wpcf7sc' ); ?></p>
            <form action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" method="post" onsubmit="return confirm( '<?php echo esc_js( __( 'Create a new secret? Cached pages with a captcha stop working until the cache is cleared.', 'wpcf7sc' ) ); ?>' );">
                <input type="hidden" name="action" value="wpcf7sc_regenerate_secret" />
                <?php wp_nonce_field( 'wpcf7sc_regenerate_secret' ); ?>
                <?php submit_button( __( 'Create a new secret', 'wpcf7sc' ), 'secondary', 'submit', false ); ?>
            </form>
        </div>
        <?php
    }


    /**
     * Create a new secret, then go back to the options page
     * @since   1.0.0
     */

    public function handle_regenerate() {

        if ( ! current_user_can( 'manage_options' ) )
            wp_die( esc_html__( 'You did not have the permission to edit this page', 'wpcf7sc' ) );

        check_admin_referer( 'wpcf7sc_regenerate_secret' );

        Captcha::regenerate_secret();

        wp_safe_redirect( add_query_arg( [ 'page' => self::Page, 'wpcf7sc_regenerated' => '1' ], admin_url( 'admin.php' ) ) );
        exit;
    }


    /**
     * The options page fields setup
     * @since   1.0.0
     */

    public function options_setup() {

        add_settings_section( self::Group, __( 'Captcha', 'wpcf7sc' ), null, self::Page );

        add_settings_field(
            WPCF7SC_PREFIX . 'highlight_color',
            __( 'Highlight color', 'wpcf7sc' ),
            [ $this, 'setting_input' ],
            self::Page,
            self::Group,
            [ WPCF7SC_PREFIX . 'highlight_color', __( 'hexadecimal color definition with leading #', 'wpcf7sc' ) ]
        );

        register_setting( self::Group, WPCF7SC_PREFIX . 'highlight_color', [ 'sanitize_callback' => 'sanitize_hex_color' ] );

        add_settings_field(
            WPCF7SC_PREFIX . 'lifetime_days',
            __( 'Validity in days', 'wpcf7sc' ),
            [ $this, 'setting_number' ],
            self::Page,
            self::Group,
            [ WPCF7SC_PREFIX . 'lifetime_days', __( 'How long a captcha value stays valid, between 2 and 90 days. A page in a cache must be younger than this value minus one day.', 'wpcf7sc' ) ]
        );

        register_setting( self::Group, WPCF7SC_PREFIX . 'lifetime_days', [
            'sanitize_callback' => static function ( $value ) {
                return max( 2, min( 90, (int) $value ) );
            },
            'default' => 7,
        ] );
    }


    /**
     * Text input field
     * @since   1.0.0
     *
     * @param   array  $args  [ field name, description ]
     */

    public function setting_input( $args ) {
        printf(
            '<input type="text" class="regular-text" name="%1$s" id="%1$s" value="%2$s" /><p class="description">%3$s</p>',
            esc_attr( $args[0] ),
            esc_attr( get_option( $args[0], '' ) ),
            esc_html( $args[1] )
        );
    }


    /**
     * Number input field
     * @since   1.0.0
     *
     * @param   array  $args  [ field name, description ]
     */

    public function setting_number( $args ) {
        printf(
            '<input type="number" min="2" max="90" step="1" class="small-text" name="%1$s" id="%1$s" value="%2$d" /><p class="description">%3$s</p>',
            esc_attr( $args[0] ),
            (int) get_option( $args[0], 7 ),
            esc_html( $args[1] )
        );
    }
}
