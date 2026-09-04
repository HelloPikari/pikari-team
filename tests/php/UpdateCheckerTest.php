<?php
namespace Pikari\Tests\Team;

use Pikari\Tests\TestCase;

/**
 * The updater must never offer a release that has no matching ZIP asset.
 *
 * The release workflow refuses to build a ZIP when the plugin header and
 * the release tag disagree, which leaves a published release with no
 * assets. PUC's default (PREFER_RELEASE_ASSETS) falls back to GitHub's
 * generated source zipball in that case, which would route around the
 * guard entirely.
 */
class UpdateCheckerTest extends TestCase {

    private function get_plugin_source(): string {
        return file_get_contents( dirname( __DIR__, 2 ) . '/pikari-team.php' );
    }

    public function test_release_assets_are_required_not_merely_preferred(): void {
        $source = $this->get_plugin_source();

        $this->assertStringContainsString(
            'REQUIRE_RELEASE_ASSETS',
            $source,
            'enableReleaseAssets() must be passed REQUIRE_RELEASE_ASSETS, otherwise a '
            . 'release with no ZIP falls back to the auto-generated source archive.'
        );
    }

    public function test_asset_filter_matches_the_release_workflow_zip_name(): void {
        $source = $this->get_plugin_source();

        // Pull the regex literal out of the enableReleaseAssets( '...' call so this
        // test verifies the actual pattern in use, not a hand-copied duplicate of it.
        $this->assertMatchesRegularExpression(
            '/enableReleaseAssets\(\s*\'([^\']+)\'/',
            $source,
            'Could not find an enableReleaseAssets() call with a name-filter regex.'
        );
        preg_match( '/enableReleaseAssets\(\s*\'([^\']+)\'/', $source, $matches );
        // $matches[1] is the PHP string literal's contents, e.g. "/pikari-team.*\.zip/",
        // which is already a complete, delimited PCRE pattern.
        $asset_filter_regex = $matches[1];

        // release.yml builds "${SLUG}-v${VERSION}.zip" -> "pikari-team-v1.2.3.zip".
        $this->assertMatchesRegularExpression(
            $asset_filter_regex,
            'pikari-team-v1.2.3.zip',
            'The asset filter must match the pikari-team-vX.Y.Z.zip name that release.yml builds.'
        );
    }
}
