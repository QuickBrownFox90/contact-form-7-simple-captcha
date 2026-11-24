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
use WPCF7_SimpleCaptcha\Style;

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
     * @since   1.1.0  Preview of the captcha
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

            <h2><?php esc_html_e( 'Preview', 'wpcf7sc' ); ?></h2>
            <p class="description"><?php esc_html_e( 'The captcha with the saved settings. Click it to see the tick; the colors of your theme may differ.', 'wpcf7sc' ); ?></p>
            <style><?php echo Style::css(); ?></style>
            <div style="padding: 1em 0;"><?php echo Captcha::render(); ?></div>

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
     * @since   1.1.0  Style settings in one option, they replace the highlight color
     */

    public function options_setup() {

        add_settings_section( self::Group, __( 'Captcha', 'wpcf7sc' ), null, self::Page );

        add_settings_section( 'wpcf7sc_style', __( 'Style', 'wpcf7sc' ), null, self::Page );

        $fields = [
            'preset'    => __( 'Style', 'wpcf7sc' ),
            'frame'     => __( 'Frame', 'wpcf7sc' ),
            'spacing'   => __( 'Spacing', 'wpcf7sc' ),
            'highlight' => __( 'Highlight color', 'wpcf7sc' ),
            'class'     => __( 'Additional class', 'wpcf7sc' ),
        ];

        foreach ( $fields as $field => $title )
            add_settings_field( WPCF7SC_PREFIX . 'style_' . $field, $title, [ $this, 'style_field' ], self::Page, 'wpcf7sc_style', $field );

        register_setting( self::Group, WPCF7SC_PREFIX . 'style', [
            'type'              => 'array',
            'sanitize_callback' => [ 'WPCF7_SimpleCaptcha\\Style', 'sanitize' ],
            'default'           => Style::defaults(),
        ] );

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
     * The fields of the style settings, all stored in one option as an array
     * @since   1.1.0
     *
     * @param   string  $field  Which field: preset, frame, spacing, highlight or class
     */

    public function style_field( $field ) {

        $st   = Style::get();
        $name = WPCF7SC_PREFIX . 'style';

        $text = function ( $key, $class = 'small-text', $placeholder = '' ) use ( $st, $name ) {
            return sprintf( '<input type="text" class="%1$s" name="%2$s[%3$s]" id="%2$s-%3$s" value="%4$s" placeholder="%5$s" />', esc_attr( $class ), esc_attr( $name ), esc_attr( $key ), esc_attr( $st[ $key ] ), esc_attr( $placeholder ) );
        };

        $number = function ( $key, $max, $step ) use ( $st, $name ) {
            return sprintf( '<input type="number" class="small-text" min="0" max="%4$s" step="%5$s" name="%1$s[%2$s]" id="%1$s-%2$s" value="%3$s" />', esc_attr( $name ), esc_attr( $key ), esc_attr( $st[ $key ] ), esc_attr( $max ), esc_attr( $step ) );
        };

        $label = function ( $key, $text ) use ( $name ) {
            return sprintf( '<label for="%1$s-%2$s">%3$s</label> ', esc_attr( $name ), esc_attr( $key ), esc_html( $text ) );
        };

        switch ( $field ) {

            case 'preset':

                $presets = [
                    'box'      => [ __( 'Box', 'wpcf7sc' ), __( 'a frame around the captcha, all fields below apply', 'wpcf7sc' ) ],
                    'minimal'  => [ __( 'Minimal', 'wpcf7sc' ), __( 'only the box and the text, the highlight color and the class apply', 'wpcf7sc' ) ],
                    'unstyled' => [ __( 'Unstyled', 'wpcf7sc' ), __( 'only what is needed to work, your theme does the rest; only the class applies', 'wpcf7sc' ) ],
                ];

                echo '<fieldset>';

                foreach ( $presets as $key => $preset )
                    printf( '<label><input type="radio" name="%1$s[preset]" value="%2$s"%3$s /> <strong>%4$s</strong> – %5$s</label><br />', esc_attr( $name ), esc_attr( $key ), checked( $st['preset'], $key, false ), esc_html( $preset[0] ), esc_html( $preset[1] ) );

                echo '</fieldset>';
                break;

            case 'frame':
                echo $label( 'border_color', __( 'Color', 'wpcf7sc' ) ) . $text( 'border_color', 'medium-text', '#cccccc' ) . ' &nbsp; ';
                echo $label( 'border_width', __( 'Width', 'wpcf7sc' ) ) . $number( 'border_width', 10, 1 ) . ' px &nbsp; ';
                echo $label( 'radius', __( 'Radius', 'wpcf7sc' ) ) . $number( 'radius', 50, 1 ) . ' px &nbsp; ';
                echo $label( 'background', __( 'Background', 'wpcf7sc' ) ) . $text( 'background', 'medium-text', '#ffffff' );
                echo '<p class="description">' . esc_html__( 'Colors as hexadecimal values with a leading #. An empty background is transparent. Only for the style "Box".', 'wpcf7sc' ) . '</p>';
                break;

            case 'spacing':
                echo $label( 'padding', __( 'Inside', 'wpcf7sc' ) ) . $number( 'padding', 3, .25 ) . ' em &nbsp; ';
                echo $label( 'margin', __( 'Above and below', 'wpcf7sc' ) ) . $number( 'margin', 3, .25 ) . ' em';
                echo '<p class="description">' . esc_html__( 'Only for the style "Box".', 'wpcf7sc' ) . '</p>';
                break;

            case 'highlight':
                echo $text( 'highlight', 'medium-text', '#' );
                echo '<p class="description">' . esc_html__( 'Color of the tick and of the focus, a hexadecimal value with a leading #. Empty means the color of the text. Not for the style "Unstyled".', 'wpcf7sc' ) . '</p>';
                break;

            case 'class':
                echo $text( 'class', 'regular-text', 'wpcf7sc__wrap' );
                echo '<p class="description">' . esc_html__( 'Css classes for the wrapper of the captcha, separated by spaces, for themes with their own css. Empty by default, so that the captcha has no name that is the same on every site. A single form can get a class with the option class: of the tag [simple_captcha].', 'wpcf7sc' ) . '</p>';
                break;
        }
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
