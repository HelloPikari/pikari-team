<?php
/**
 * VCard 3.0 generator and download endpoint.
 *
 * @package pikari-team
 */

namespace Pikari\Team;

class VCard {

    public function __construct() {
        add_action( 'pikari_team_card_download', [ $this, 'handle_download' ] );
    }

    public function generate_vcard( int $post_id, bool $include_photo = true ): string {
        $data = Template_Tags::get_member_data( $post_id );

        $first_name = $data['first_name'];
        $last_name  = $data['last_name'];
        $job_title  = $data['job_title'];
        $email      = $data['email'];
        $phone      = $data['phone'];
        $cell       = $data['cell'];
        $company    = $data['company'];
        $website    = $data['website'];
        $street     = $data['address']['street'];
        $city       = $data['address']['city'];
        $state      = $data['address']['state'];
        $zip        = $data['address']['zip'];
        $country    = $data['address']['country'];

        $lines   = [];
        $lines[] = 'BEGIN:VCARD';
        $lines[] = 'VERSION:3.0';
        $lines[] = 'FN:' . $this->escape_value( $first_name . ' ' . $last_name );
        $lines[] = 'N:' . $this->escape_value( $last_name ) . ';' . $this->escape_value( $first_name ) . ';;;';

        if ( $company ) {
            $lines[] = 'ORG:' . $this->escape_value( $company );
        }

        if ( $job_title ) {
            $lines[] = 'TITLE:' . $this->escape_value( $job_title );
        }

        if ( $phone ) {
            $lines[] = 'TEL;TYPE=WORK,VOICE:' . $this->escape_value( $phone );
        }

        if ( $cell ) {
            $lines[] = 'TEL;TYPE=CELL,VOICE:' . $this->escape_value( $cell );
        }

        if ( $email ) {
            $lines[] = 'EMAIL;TYPE=INTERNET:' . $this->escape_value( $email );
        }

        if ( $street || $city || $state || $zip || $country ) {
            $lines[] = 'ADR;TYPE=WORK:;;'
            . $this->escape_value( $street ) . ';'
            . $this->escape_value( $city ) . ';'
            . $this->escape_value( $state ) . ';'
            . $this->escape_value( $zip ) . ';'
            . $this->escape_value( $country );
        }

        if ( $website ) {
            $lines[] = 'URL:' . $website;
        }

        if ( $include_photo ) {
            list( $photo_data, $photo_type ) = $this->get_photo_data( $post_id );
            if ( $photo_data ) {
                $lines[] = 'PHOTO;ENCODING=b;TYPE=' . $photo_type . ':' . $photo_data;
            }
        }

        $lines[] = 'END:VCARD';

        return implode( "\r\n", $lines ) . "\r\n";
    }

    /**
     * Serve the vCard file download.
     *
     * Public access is intentional — business card contact data is expected to
     * be shared. The post is pre-validated as 'publish' status by
     * Template::route_template() before this action fires.
     *
     * @param \WP_Post $post The team member post.
     */
    public function handle_download( $post ): void {
        $vcard_string = $this->generate_vcard( $post->ID, true );
        $first_name   = (string) get_post_meta( $post->ID, 'pikari_team_first_name', true );
        $last_name    = (string) get_post_meta( $post->ID, 'pikari_team_last_name', true );
        $name         = trim( $first_name . ' ' . $last_name ) ?: 'contact';

        nocache_headers();
        header( 'Content-Type: text/vcard; charset=utf-8' );
        header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( $name ) . '.vcf"' );
        // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- vCard format, not HTML.
        echo $vcard_string;
        exit;
    }

    private function escape_value( string $value ): string {
        // vCard spec: escape backslashes first, then semicolons and commas.
        $value = str_replace( '\\', '\\\\', $value );
        $value = str_replace( ';', '\\;', $value );
        $value = str_replace( ',', '\\,', $value );

        return $value;
    }

    private function get_photo_data( int $post_id ): array {
        $thumbnail_id = get_post_thumbnail_id( $post_id );
        if ( ! $thumbnail_id ) {
            return [ '', '' ];
        }

        $file_path = get_attached_file( $thumbnail_id );
        if ( ! $file_path || ! file_exists( $file_path ) ) {
            return [ '', '' ];
        }

        $file_info = wp_check_filetype( $file_path );
        $mime      = $file_info['type'] ?? 'image/jpeg';
        $type      = strtoupper( substr( $mime, strpos( $mime, '/' ) + 1 ) );

        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
        $image_data = file_get_contents( $file_path );
        if ( ! $image_data ) {
            return [ '', '' ];
        }

        // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_encode
        return [ base64_encode( $image_data ), $type ];
    }
}
