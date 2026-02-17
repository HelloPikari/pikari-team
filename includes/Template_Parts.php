<?php
/**
 * Card template part area registration.
 *
 * @package pikari-team
 */

namespace Pikari\Team;

class Template_Parts {

    public function __construct() {
        add_filter( 'default_wp_template_part_areas', [ $this, 'register_card_area' ] );
    }

    public function register_card_area( array $areas ): array {
        $areas[] = [
            'area'        => 'pikari-card',
            'label'       => __( 'Business Cards', 'pikari-team' ),
            'description' => __( 'Card templates for digital business cards.', 'pikari-team' ),
            'icon'        => 'id-alt',
        ];

        return $areas;
    }
}
