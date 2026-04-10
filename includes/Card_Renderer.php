<?php
/**
 * Hookable section-based card renderer.
 *
 * Renders team member cards by firing action hooks for each section,
 * allowing themes and plugins to customize output at any point.
 *
 * @package pikari-team
 */

namespace Pikari\Team;

/**
 * Renders team member cards via hookable section actions.
 *
 * Call render() to produce HTML output. Call register_defaults() during
 * plugin init to register the default section callbacks.
 */
class Card_Renderer {

    /**
     * Sections rendered for standalone card pages.
     */
    private const SINGLE_SECTIONS = [ 'header', 'carousel', 'footer' ];

    /**
     * Sections rendered for embedded/shortcode contexts.
     */
    private const EMBED_SECTIONS = [ 'header', 'contact' ];

    /**
     * Renders a team member card for the given post ID and context.
     *
     * Fires a `pikari_team_card_{section}` action for each active section,
     * passing the member data array and context string as arguments.
     *
     * @param int    $post_id The team member post ID.
     * @param string $context Rendering context: 'single', 'embed', or 'shortcode'.
     * @return string The rendered HTML output.
     */
    public static function render( int $post_id, string $context = 'single' ): string {
        $data = Template_Tags::get_member_data( $post_id );

        $sections = ( 'embed' === $context || 'shortcode' === $context )
        ? self::EMBED_SECTIONS
        : self::SINGLE_SECTIONS;

        ob_start();
        echo '<div class="pikari-team-card">';

        foreach ( $sections as $section ) {
            do_action( 'pikari_team_card_' . $section, $data, $context );
        }

        echo '</div>';

        return (string) ob_get_clean();
    }

    /**
     * Registers the default section callbacks on their respective action hooks.
     *
     * Call this during plugin init. Each section can be overridden by removing
     * this callback and adding a custom one at the same or different priority.
     *
     * @return void
     */
    public static function register_defaults(): void {
        add_action( 'pikari_team_card_header', [ self::class, 'render_header' ], 10, 2 );
        add_action( 'pikari_team_card_contact', [ self::class, 'render_contact' ], 10, 2 );
        add_action( 'pikari_team_card_address', [ self::class, 'render_address' ], 10, 2 );
        add_action( 'pikari_team_card_social', [ self::class, 'render_social' ], 10, 2 );
        add_action( 'pikari_team_card_qr', [ self::class, 'render_qr' ], 10, 2 );
        add_action( 'pikari_team_card_footer', [ self::class, 'render_footer' ], 10, 2 );
        add_action( 'pikari_team_card_carousel', [ self::class, 'render_carousel' ], 10, 2 );
        add_action( 'pikari_team_carousel_slide_qr', [ self::class, 'render_carousel_slide_qr' ], 10, 2 );
        add_action( 'pikari_team_carousel_slide_contact', [ self::class, 'render_carousel_slide_contact' ], 10, 2 );
        add_action( 'pikari_team_contact_phone', [ self::class, 'render_contact_phone' ], 10, 2 );
        add_action( 'pikari_team_contact_cell', [ self::class, 'render_contact_cell' ], 10, 2 );
        add_action( 'pikari_team_contact_email', [ self::class, 'render_contact_email' ], 10, 2 );
        add_action( 'pikari_team_contact_website', [ self::class, 'render_contact_website' ], 10, 2 );
        add_action( 'pikari_team_contact_address', [ self::class, 'render_contact_address' ], 10, 2 );
    }

    /**
     * Renders the card header section.
     *
     * Outputs the member's photo (if present), name, designation, job title,
     * and company name.
     *
     * @param array<string, mixed> $data    Member data from Template_Tags::get_member_data().
     * @param string               $context Rendering context.
     * @return void
     */
    public static function render_header( array $data, string $context ): void {
        echo '<div class="pikari-team-card__header">';

        if ( $data['has_photo'] ) {
            echo '<img class="pikari-team-card__headshot" src="' . esc_url( $data['photo_url'] ) . '" alt="' . esc_attr( $data['full_name'] ) . '" width="120" height="120">';
        }

        echo '<h2 class="pikari-team-card__name">' . esc_html( $data['full_name'] ) . '</h2>';

        if ( $data['has_designation'] ) {
            echo '<p class="pikari-team-card__designation">' . esc_html( $data['designation'] ) . '</p>';
        }

        if ( $data['has_job_title'] ) {
            echo '<p class="pikari-team-card__title">' . esc_html( $data['job_title'] ) . '</p>';
        }

        if ( $data['has_company'] ) {
            echo '<p class="pikari-team-card__company">' . esc_html( $data['company'] ) . '</p>';
        }

        echo '</div>';
    }

    /**
     * Renders the card contact section.
     *
     * Outputs phone, cell, email, and website links. Skipped entirely if all
     * contact fields are empty.
     *
     * @param array<string, mixed> $data    Member data from Template_Tags::get_member_data().
     * @param string               $context Rendering context.
     * @return void
     */
    public static function render_contact( array $data, string $context ): void {
        if ( ! $data['has_phone'] && ! $data['has_cell'] && ! $data['has_email'] && ! $data['has_website'] ) {
            return;
        }

        echo '<div class="pikari-team-card__contact">';

        if ( $data['has_phone'] ) {
            echo '<a href="tel:' . esc_attr( $data['phone'] ) . '" class="pikari-team-card__contact-link">';
            echo '<span class="pikari-team-card__label">' . esc_html__( 'Phone', 'pikari-team' ) . '</span>';
            echo '<span class="pikari-team-card__value">' . esc_html( $data['phone'] ) . '</span>';
            echo '</a>';
        }

        if ( $data['has_cell'] ) {
            echo '<a href="tel:' . esc_attr( $data['cell'] ) . '" class="pikari-team-card__contact-link">';
            echo '<span class="pikari-team-card__label">' . esc_html__( 'Cell', 'pikari-team' ) . '</span>';
            echo '<span class="pikari-team-card__value">' . esc_html( $data['cell'] ) . '</span>';
            echo '</a>';
        }

        if ( $data['has_email'] ) {
            echo '<a href="mailto:' . esc_attr( $data['email'] ) . '" class="pikari-team-card__contact-link">';
            echo '<span class="pikari-team-card__label">' . esc_html__( 'Email', 'pikari-team' ) . '</span>';
            echo '<span class="pikari-team-card__value">' . esc_html( $data['email'] ) . '</span>';
            echo '</a>';
        }

        if ( $data['has_website'] ) {
            echo '<a href="' . esc_url( $data['website'] ) . '" class="pikari-team-card__contact-link" target="_blank" rel="noopener noreferrer">';
            echo '<span class="pikari-team-card__label">' . esc_html__( 'Website', 'pikari-team' ) . '</span>';
            echo '<span class="pikari-team-card__value">' . esc_html( $data['website'] ) . '</span>';
            echo '</a>';
        }

        echo '</div>';
    }

    /**
     * Renders the card address section.
     *
     * Skipped if no address fields are populated.
     *
     * @param array<string, mixed> $data    Member data from Template_Tags::get_member_data().
     * @param string               $context Rendering context.
     * @return void
     */
    public static function render_address( array $data, string $context ): void {
        if ( ! $data['has_address'] ) {
            return;
        }

        $formatted = Template_Tags::get_formatted_address_from_data( $data );

        echo '<div class="pikari-team-card__address">';
        echo '<span class="pikari-team-card__label">' . esc_html__( 'Address', 'pikari-team' ) . '</span>';
        echo '<span class="pikari-team-card__value">' . esc_html( $formatted ) . '</span>';
        echo '</div>';
    }

    /**
     * Renders the card social links section.
     *
     * Outputs LinkedIn and Twitter/X links. Skipped if no social fields are populated.
     *
     * @param array<string, mixed> $data    Member data from Template_Tags::get_member_data().
     * @param string               $context Rendering context.
     * @return void
     */
    public static function render_social( array $data, string $context ): void {
        if ( ! $data['has_social'] ) {
            return;
        }

        echo '<div class="pikari-team-card__social">';

        if ( $data['has_linkedin'] ) {
            echo '<a href="' . esc_url( $data['linkedin'] ) . '" class="pikari-team-card__social-link" target="_blank" rel="noopener noreferrer">';
            echo esc_html__( 'LinkedIn', 'pikari-team' );
            echo '</a>';
        }

        if ( $data['has_twitter'] ) {
            echo '<a href="' . esc_url( $data['twitter'] ) . '" class="pikari-team-card__social-link" target="_blank" rel="noopener noreferrer">';
            echo esc_html__( 'Twitter/X', 'pikari-team' );
            echo '</a>';
        }

        echo '</div>';
    }

    /**
     * Renders the QR code section.
     *
     * Skipped if the QR_Code class is unavailable or post_id is missing.
     *
     * @param array<string, mixed> $data    Member data from Template_Tags::get_member_data().
     * @param string               $context Rendering context.
     * @return void
     */
    public static function render_qr( array $data, string $context ): void {
        if ( ! class_exists( QR_Code::class ) || empty( $data['post_id'] ) ) {
            return;
        }

        $qr  = new QR_Code();
        $svg = $qr->generate_qr_svg( (int) $data['post_id'] );

        if ( '' === $svg ) {
            return;
        }

        echo '<div class="pikari-team-card__qr">';
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- SVG generated by plugin.
        echo $svg;
        echo '</div>';
    }

    /**
     * Renders the card footer/actions section.
     *
     * Outputs a "Save Contact" button linking to the vCard download URL.
     * Skipped if the vCard URL is empty.
     *
     * @param array<string, mixed> $data    Member data from Template_Tags::get_member_data().
     * @param string               $context Rendering context.
     * @return void
     */
    public static function render_footer( array $data, string $context ): void {
        if ( empty( $data['vcard_url'] ) ) {
            return;
        }

        echo '<div class="pikari-team-card__actions">';
        echo '<a href="' . esc_url( $data['vcard_url'] ) . '" class="pikari-team-card__save-btn">';
        echo esc_html__( 'Save Contact', 'pikari-team' );
        echo '</a>';
        echo '</div>';
    }

    /**
     * Renders the carousel section containing QR and contact slides.
     *
     * Fires `pikari_team_carousel_slide_qr` for slide 0 and
     * `pikari_team_carousel_slide_contact` for slide 1. Outputs dot
     * navigation with ARIA tab semantics.
     *
     * @param array<string, mixed> $data    Member data from Template_Tags::get_member_data().
     * @param string               $context Rendering context.
     * @return void
     */
    public static function render_carousel( array $data, string $context ): void {
        echo '<div class="pikari-team-card__carousel">';
        echo '<div class="pikari-team-card__carousel-track">';

        echo '<div class="pikari-team-card__slide" data-slide="0">';
        do_action( 'pikari_team_carousel_slide_qr', $data, $context );
        echo '</div>';

        echo '<div class="pikari-team-card__slide" data-slide="1">';
        do_action( 'pikari_team_carousel_slide_contact', $data, $context );
        echo '</div>';

        echo '</div>';

        echo '<div class="pikari-team-card__carousel-dots" role="tablist">';
        echo '<button class="pikari-team-card__dot active" role="tab" aria-selected="true" aria-label="' . esc_attr__( 'QR Code', 'pikari-team' ) . '" data-slide="0"></button>';
        echo '<button class="pikari-team-card__dot" role="tab" aria-selected="false" aria-label="' . esc_attr__( 'Contact Info', 'pikari-team' ) . '" data-slide="1"></button>';
        echo '</div>';

        echo '</div>';
    }

    /**
     * Renders the QR code carousel slide. Delegates to render_qr.
     *
     * @param array<string, mixed> $data    Member data from Template_Tags::get_member_data().
     * @param string               $context Rendering context.
     * @return void
     */
    public static function render_carousel_slide_qr( array $data, string $context ): void {
        self::render_qr( $data, $context );
    }

    /**
     * Renders the contact and address carousel slide.
     *
     * Combines contact links (phone, cell, email, website) and address into a
     * single slide. Each contact link uses label and value spans. Skipped sections
     * are omitted if the corresponding data fields are empty.
     *
     * @param array<string, mixed> $data    Member data from Template_Tags::get_member_data().
     * @param string               $context Rendering context.
     * @return void
     */
    public static function render_carousel_slide_contact( array $data, string $context ): void {
        /**
         * Filters the order of contact elements in the carousel slide.
         *
         * Each element corresponds to a `pikari_team_contact_{element}` action hook.
         *
         * @param string[] $elements Contact element names in render order.
         */
        $default_elements = [
            'phone',
            'cell',
            'email',
            'website',
            'address',
        ];
        $elements         = apply_filters( 'pikari_team_contact_elements', $default_elements );

        foreach ( $elements as $element ) {
            do_action( 'pikari_team_contact_' . $element, $data, $context );
        }
    }

    /**
     * Default phone element.
     *
     * @param array  $data    Member data.
     * @param string $context Rendering context.
     */
    public static function render_contact_phone( array $data, string $context ): void {
        if ( ! $data['has_phone'] ) {
            return;
        }
        echo '<a href="tel:' . esc_attr( $data['phone'] ) . '" class="pikari-team-card__link">';
        echo '<span class="pikari-team-card__label">' . esc_html__( 'Phone', 'pikari-team' ) . '</span>';
        echo '<span class="pikari-team-card__value">' . esc_html( $data['phone'] ) . '</span>';
        echo '</a>';
    }

    /**
     * Default cell element.
     *
     * @param array  $data    Member data.
     * @param string $context Rendering context.
     */
    public static function render_contact_cell( array $data, string $context ): void {
        if ( ! $data['has_cell'] ) {
            return;
        }
        echo '<a href="tel:' . esc_attr( $data['cell'] ) . '" class="pikari-team-card__link">';
        echo '<span class="pikari-team-card__label">' . esc_html__( 'Cell', 'pikari-team' ) . '</span>';
        echo '<span class="pikari-team-card__value">' . esc_html( $data['cell'] ) . '</span>';
        echo '</a>';
    }

    /**
     * Default email element.
     *
     * @param array  $data    Member data.
     * @param string $context Rendering context.
     */
    public static function render_contact_email( array $data, string $context ): void {
        if ( ! $data['has_email'] ) {
            return;
        }
        echo '<a href="mailto:' . esc_attr( $data['email'] ) . '" class="pikari-team-card__link">';
        echo '<span class="pikari-team-card__label">' . esc_html__( 'Email', 'pikari-team' ) . '</span>';
        echo '<span class="pikari-team-card__value">' . esc_html( $data['email'] ) . '</span>';
        echo '</a>';
    }

    /**
     * Default website element.
     *
     * @param array  $data    Member data.
     * @param string $context Rendering context.
     */
    public static function render_contact_website( array $data, string $context ): void {
        if ( ! $data['has_website'] ) {
            return;
        }
        echo '<a href="' . esc_url( $data['website'] ) . '" class="pikari-team-card__link" target="_blank" rel="noopener noreferrer">';
        echo '<span class="pikari-team-card__label">' . esc_html__( 'Website', 'pikari-team' ) . '</span>';
        echo '<span class="pikari-team-card__value">' . esc_html( $data['website'] ) . '</span>';
        echo '</a>';
    }

    /**
     * Default address element.
     *
     * @param array  $data    Member data.
     * @param string $context Rendering context.
     */
    public static function render_contact_address( array $data, string $context ): void {
        if ( ! $data['has_address'] ) {
            return;
        }
        $formatted = Template_Tags::get_formatted_address_from_data( $data );

        echo '<div class="pikari-team-card__address">';
        echo '<span class="pikari-team-card__label">' . esc_html__( 'Address', 'pikari-team' ) . '</span>';
        echo '<span class="pikari-team-card__value">' . esc_html( $formatted ) . '</span>';
        echo '</div>';
    }
}
