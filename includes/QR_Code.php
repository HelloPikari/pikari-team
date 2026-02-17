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

        $options = new QROptions();

        // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- third-party library.
        $options->outputType = QROutputInterface::MARKUP_SVG;
        // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- third-party library.
        $options->eccLevel = EccLevel::M;
        // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- third-party library.
        $options->addQuietzone = true;
        // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- third-party library.
        $options->outputBase64 = false;

        $qr = new QRCodeLib( $options );

        return $qr->render( $vcard_string );
    }
}
