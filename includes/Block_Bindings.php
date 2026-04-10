<?php
/**
 * Block Bindings API source and editor asset enqueuing.
 *
 * @package pikari-team
 */

namespace Pikari\Team;

class Block_Bindings {

    public function __construct() {
        add_action( 'init', [ $this, 'register' ] );
        add_action( 'enqueue_block_editor_assets', [ $this, 'enqueue_editor_assets' ] );
    }

    public function register(): void {
        register_block_bindings_source(
            'pikari-team/meta',
            [
                'label'              => __( 'Team Member Data', 'pikari-team' ),
                'get_value_callback' => [ $this, 'get_binding_value' ],
                'uses_context'       => [ 'postId', 'postType' ],
            ]
        );
    }

    public function get_binding_value( array $args, $block, string $attribute_name ): string {
        $post_id = $block->context['postId'] ?? 0;
        $key     = $args['key'] ?? '';

        if ( ! $post_id || ! $key ) {
            return '';
        }

        return (string) get_post_meta( $post_id, $key, true );
    }

    public function enqueue_editor_assets(): void {
        $screen = get_current_screen();

        if ( ! $screen || Post_Type::CPT_SLUG !== $screen->post_type ) {
            return;
        }

        $asset_file = PIKARI_TEAM_DIR . 'build/editor/index.asset.php';

        if ( ! file_exists( $asset_file ) ) {
            return;
        }

        $assets = include $asset_file;

        wp_enqueue_script(
            'pikari-team-editor',
            PIKARI_TEAM_URL . 'build/editor/index.js',
            $assets['dependencies'],
            $assets['version'],
            true
        );
    }
}
