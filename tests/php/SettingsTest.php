<?php

namespace Pikari\Tests\Team;

use Pikari\Tests\TestCase;
use Pikari\Team\Settings;
use Brain\Monkey\Functions;
use Brain\Monkey\Actions;

class SettingsTest extends TestCase {

    public function test_get_option_is_called_with_pikari_team_settings(): void {
        Functions\expect( 'get_option' )
            ->once()
            ->with( 'pikari_team_settings', [] )
            ->andReturn( [] );

        $settings = new Settings();
        $settings->get_settings();
    }

    public function test_default_admin_label_is_team_members(): void {
        Functions\when( 'get_option' )->justReturn( [] );

        $settings = new Settings();
        $result   = $settings->get_settings();

        $this->assertSame( 'Team Members', $result['admin_label'] );
    }

    public function test_default_company_name_falls_back_to_bloginfo(): void {
        Functions\when( 'get_option' )->justReturn( [] );
        Functions\expect( 'get_bloginfo' )
            ->once()
            ->with( 'name' )
            ->andReturn( 'My Site' );

        $settings = new Settings();
        $result   = $settings->get_company_name();

        $this->assertSame( 'My Site', $result );
    }

    public function test_default_brand_color_is_0073aa(): void {
        Functions\when( 'get_option' )->justReturn( [] );

        $settings = new Settings();
        $result   = $settings->get_settings();

        $this->assertSame( '#0073aa', $result['brand_color'] );
    }

    public function test_register_setting_is_called_on_admin_init(): void {
        Actions\expectAdded( 'admin_init' )->twice();
        Actions\expectAdded( 'admin_menu' )->once();
        Actions\expectAdded( 'update_option_pikari_team_settings' )->once();

        new Settings();
    }

    public function test_add_submenu_page_is_called_on_admin_menu(): void {
        Functions\expect( 'add_submenu_page' )
            ->once()
            ->with(
                'edit.php?post_type=pikari_team_member',
                \Mockery::type( 'string' ),
                \Mockery::type( 'string' ),
                'manage_options',
                'pikari-team-settings',
                \Mockery::type( 'array' )
            );

        $settings = new Settings();
        $settings->add_settings_page();
    }
}
