<?php
/**
 * QR code SVG generation via chillerlan/php-qrcode.
 *
 * @package pikari-team
 */

namespace Pikari\Team;

use chillerlan\QRCode\QRCode as QRCodeLib;
use chillerlan\QRCode\QROptions;
use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Output\QROutputInterface;

class QR_Code {

    public function __construct() {
        // No hooks needed — called directly by templates.
    }

    public function generate_qr_svg( int $post_id ): string {
        $vcard = new VCard();

        // Omit photo — base64 image data would exceed QR capacity.
        $vcard_string = $vcard->generate_vcard( $post_id, false );

        if ( empty( $vcard_string ) ) {
            return '';
        }

        $defaults = [
            'outputType'           => QROutputInterface::MARKUP_SVG,
            'eccLevel'             => EccLevel::M,
            'addQuietzone'         => true,
            'outputBase64'         => false,
            'drawCircularModules'  => false,
            'circleRadius'         => 0.45,
            'connectPaths'         => false,
            'addLogoSpace'         => false,
            'logoSpaceWidth'       => null,
            'logoSpaceHeight'      => null,
            'logoSpaceStartX'      => null,
            'logoSpaceStartY'      => null,
            'cssClass'             => 'pikari-team-qr',
            'svgDefs'              => '',
            'moduleValues'         => [],
        ];

        /**
         * Filters the QR code generation options.
         *
         * Allows theme developers to customize the QR code appearance:
         * circular modules, colors, logo space, SVG definitions, etc.
         *
         * @param array $qr_options QR code options (keys map to chillerlan/php-qrcode QROptions properties).
         * @param int   $post_id    The team member post ID.
         */
        $qr_options = apply_filters( 'pikari_team_qr_options', $defaults, $post_id );

        $options = new QROptions();

        // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- third-party library.
        foreach ( $qr_options as $key => $value ) {
            if ( null !== $value ) {
                // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- third-party library.
                $options->{$key} = $value;
            }
        }

        /**
         * Filters the QR code SVG markup after generation.
         *
         * Use this to wrap the SVG with additional markup, e.g. a logo overlay.
         *
         * @param string $svg     The generated QR code SVG markup.
         * @param int    $post_id The team member post ID.
         */
        $svg = ( new QRCodeLib( $options ) )->render( $vcard_string );

        return apply_filters( 'pikari_team_qr_svg', $svg, $post_id );
    }
}
