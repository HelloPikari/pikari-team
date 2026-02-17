<?php

namespace Pikari\Tests\Team;

use Pikari\Tests\TestCase;
use Pikari\Team\Template_Parts;
use Brain\Monkey\Filters;

class Template_PartsTest extends TestCase {

    public function test_default_wp_template_part_areas_filter_is_registered(): void {
        Filters\expectAdded( 'default_wp_template_part_areas' )->once();

        new Template_Parts();
    }

    public function test_filter_adds_pikari_card_area(): void {
        $template_parts = new Template_Parts();
        $areas          = $template_parts->register_card_area( [] );

        $this->assertCount( 1, $areas );
        $this->assertSame( 'pikari-card', $areas[0]['area'] );
    }

    public function test_filter_preserves_existing_areas(): void {
        $existing = [
            [ 'area' => 'header', 'label' => 'Header' ],
        ];

        $template_parts = new Template_Parts();
        $areas          = $template_parts->register_card_area( $existing );

        $this->assertCount( 2, $areas );
        $this->assertSame( 'header', $areas[0]['area'] );
        $this->assertSame( 'pikari-card', $areas[1]['area'] );
    }
}
