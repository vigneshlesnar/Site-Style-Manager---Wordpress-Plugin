===  Site Style Manager ===
Contributors:      vigneshlesnar
Tags:              css, colors, fonts, typography, style, customizer, page builder, elementor, divi
Requires at least: 5.8
Tested up to:      7.0
Requires PHP:      7.4
Stable tag:        1.0.0
License:           GPLv2 or later
License URI:       https://www.gnu.org/licenses/gpl-2.0.html

Scan, customize and live-preview all colors & fonts across your entire WordPress site. Works with every page builder.

== Description ==

**Site Style Manager** gives WordPress admins a single control panel to visually manage the entire site's typography and color palette — without touching CSS manually.

= Color Management =
* Scans all CSS files (theme, parent theme, registered stylesheets) and extracts every color used
* Detects CSS custom properties (`--variables`) from live DOM computed styles
* Remap any scanned color to a new value — all instances update site-wide
* Custom color palette with named swatches exposed as `--ssm-*` CSS variables

= Typography =
* Per-element font control for Body, H1–H6, Links, Blockquote, Code, Button, Navigation, Caption, Lists, and Labels
* Set font family (Google Fonts auto-loaded), weight, line height, letter spacing, and color per element
* Responsive font sizes — separate values for desktop, tablet (≤1024px), and mobile (≤767px)

= Live Preview =
* Floating badge on the frontend for logged-in admins — preview any change instantly without saving
* DOM scan captures computed colors and CSS variables from the actual rendered page

= Page Builder Support =
* Elementor (CSS files + per-page meta)
* Divi (dynamic CSS + per-page custom CSS)
* Beaver Builder (cache files)
* Any theme or builder via registered stylesheet scanning

= Backup & Restore =
* One-click backup and restore of all styles before making changes

= Custom CSS =
* Freeform custom CSS block appended after all generated styles

== Installation ==

1. Upload the `site-style-manager` folder to the `/wp-content/plugins/` directory
2. Activate the plugin through the **Plugins** menu in WordPress
3. Go to **Style Manager** in the admin sidebar
4. Click **Scan Site** to detect all colors and fonts used on your site
5. Start customizing — changes preview live on your frontend

== Frequently Asked Questions ==

= Does it work with my page builder? =
Yes. It has built-in support for Elementor, Divi, and Beaver Builder, and falls back to scanning all registered stylesheets for any other theme or builder.

= Will it slow down my site? =
No. The plugin only loads a tiny badge script on the frontend for logged-in admins. All style overrides are a single injected `<style>` block with no extra HTTP requests for visitors.

= Does it use Google Fonts? =
Only if you choose a Google Font in the typography settings. Fonts you select are loaded via a single preconnected Google Fonts request.

= Can I undo my changes? =
Yes. Use the **Take Backup** button before making changes, then **Restore Backup** to roll back at any time.

= Does it change my theme files? =
No. All overrides are stored in the WordPress database and injected as a `<style>` block at runtime. Your theme files are never modified.

== Screenshots ==

1. Admin panel — color scanner and palette editor
2. Typography panel — per-element font and size controls
3. Frontend badge — live preview without leaving the page

== Changelog ==

= 1.0.0 =
* Initial release

== Upgrade Notice ==

= 1.0.0 =
Initial release.
