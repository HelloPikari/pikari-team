=== Pikari Team ===
Contributors: pikari
Tags: team, business-card, pwa, vcard, qr-code
Requires at least: 6.8
Tested up to: 7.1
Stable tag: 1.0.1
Requires PHP: 8.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Team member management with digital business cards, PWA-enabled card pages, vCard downloads, and QR code generation.

== Description ==

Pikari Team is a WordPress plugin for managing team members with rich digital business card pages. Each team member gets a standalone, mobile-optimized PWA card page that can be saved to a phone's home screen, shared via QR code, and downloaded as a vCard contact.

**Features:**

* Custom post type for team members with 18 meta fields
* Classic editor meta box with HTML5 input types, required field validation, and settings-based placeholder defaults
* Standalone PWA business card pages with service worker and offline support
* Swipeable carousel between QR code and contact information
* vCard 3.0 download with full contact details
* QR code generation with customizable appearance (circular dots, logo space, colors)
* Configurable URL slugs, default contact information, and brand color
* Fully hookable template system for theme developers
* Theme CSS override via build pipeline integration
* Automatic plugin updates via GitHub releases

**For Theme Developers:**

Pikari Team provides extensive hooks, filters, and a public template tags API:

* Section-based action hooks for card rendering (header, carousel, contact, QR, footer)
* Filterable section order, contact element order, meta fields, and field groups
* `pikari_team_get_member_data()` returns a structured array with fallbacks to settings defaults
* `pikari_team_card_css_file` filter to replace the default CSS with a theme-built stylesheet
* `pikari_team_qr_options` and `pikari_team_qr_svg` filters for QR code customization
* `pikari_team_post_type_args` filter for CPT configuration
* Full template override support for both single post and standalone card templates

See `docs/hooks.md` in the plugin directory for the complete developer reference.

== Installation ==

1. Upload the plugin ZIP via Plugins > Add New > Upload Plugin
2. Activate the plugin through the Plugins screen
3. Configure default settings under Team Members > Settings
4. Create team members and fill in their details
5. Visit /card/{member-slug}/ to see the digital business card

== Changelog ==

= 1.0.1 =
* Updated plugin description and tested up to WordPress 6.9

= 1.0.0 =
* First stable release
* CPT slug constant for refactor safety
* Code deduplication and cleanup

= 0.3.0 =
* GitHub release update checker for automatic updates
* noindex/nofollow on card pages
* Digital Business Card URL meta box
* Carousel infinite-loop fix

= 0.2.0 =
* Classic editor support with hookable templates
* Template Tags public API with settings fallbacks
* Card Renderer with section-based action hooks
* PWA carousel with scroll-snap swipe
* QR code customization filters
* Configurable URL slugs and default contact info
* Extension-free service worker and manifest URLs
