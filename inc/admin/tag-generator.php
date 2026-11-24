<?php

/**
 * Form tag generator for [simple_captcha] in the Contact Form 7 form editor
 *
 * @author      Patrick Mauersberger
 * @since       1.0.0
 * @package     contact-form-7-simple-captcha
 * @subpackage  admin
 */

namespace WPCF7_SimpleCaptcha\Admin;

Class Tag_Generator {

    public function __construct() {
        add_action( 'wpcf7_admin_init', [ $this, 'add_tag_generator' ], 55, 0 );
    }


    /**
     * Add the button to the tag generator of the form editor
     * @since   1.0.0
     */

    public function add_tag_generator() {

        if ( ! class_exists( 'WPCF7_TagGenerator' ) || ! class_exists( 'WPCF7_TagGeneratorGenerator' ) )
            return;

        \WPCF7_TagGenerator::get_instance()->add(
            'simple_captcha',
            __( 'simple captcha', 'wpcf7sc' ),
            [ $this, 'panel' ],
            [ 'version' => '2' ]
        );
    }


    /**
     * The panel of the tag generator
     * @since   1.0.0
     * @since   1.1.0  Field for the option class:
     *
     * @param   WPCF7_ContactForm  $contact_form  The form that is edited
     * @param   array              $options       Options of the tag generator, 'content' is the panel id
     */

    public function panel( $contact_form, $options ) {

        $tgg       = new \WPCF7_TagGeneratorGenerator( $options['content'] );
        $formatter = new \WPCF7_HTMLFormatter();

        $formatter->append_start_tag( 'header', [ 'class' => 'description-box' ] );
        $formatter->append_start_tag( 'h3' );
        $formatter->append_preformatted( esc_html__( 'Simple captcha form-tag generator', 'wpcf7sc' ) );
        $formatter->end_tag( 'h3' );
        $formatter->append_start_tag( 'p' );
        $formatter->append_preformatted( esc_html__( 'Generates a form-tag for the "I am not a robot" captcha. The captcha appears exactly where the tag stands. Without the tag it is added in front of the submit button.', 'wpcf7sc' ) );
        $formatter->end_tag( 'header' );

        $formatter->append_start_tag( 'div', [ 'class' => 'control-box' ] );

        $formatter->call_user_func( static function () use ( $tgg ) {
            $tgg->print( 'field_type', [
                'select_options' => [ 'simple_captcha' => __( 'Simple captcha', 'wpcf7sc' ) ],
            ] );

            // class: of the tag, added to the wrapper of the captcha in this form
            $tgg->print( 'class_attr' );
        } );

        $formatter->end_tag( 'div' );

        $formatter->append_start_tag( 'footer', [ 'class' => 'insert-box' ] );

        $formatter->call_user_func( static function () use ( $tgg ) {
            $tgg->print( 'insert_box_content' );
        } );

        $formatter->print();
    }
}
