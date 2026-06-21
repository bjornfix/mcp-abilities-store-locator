=== MCP Abilities - Store Locator ===
Contributors: devenia
Tags: mcp, ai, automation, abilities-api, store-locator
Requires at least: 6.9
Tested up to: 7.0
Requires PHP: 8.0
Stable tag: 0.1.5
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.txt

Narrow MCP abilities and maintained frontend template support for WP Store Locator.

== Description ==

Adds authenticated WordPress Abilities API tools for WP Store Locator maintenance and registers maintained store-locator template support for dynamic column-based location listings.

The abilities cover WPSL status, settings, templates, stores, categories, and transient cleanup. The plugin does not duplicate store content into Elementor or static page content. Store data remains owned by WP Store Locator.

== Changelog ==

= 0.1.5 =
* Removed the default bottom margin from Store Locator map canvases rendered inside Elementor Shortcode widgets.

= 0.1.4 =
* Tightened the mobile top gap above the Store Locator search label.

= 0.1.3 =
* Suppressed the final divider line after the last location entry.

= 0.1.2 =
* Adjusted the maintained columns template so location entries use only a bottom divider instead of boxed card borders.

= 0.1.1 =
* Improved the maintained columns template so card padding is not overridden by Store Locator base styles.
* Read the search label and button text directly from Store Locator settings in the maintained template.
* Added maintained label and Elementor store-post compatibility handling.

= 0.1.0 =
* Initial release with WPSL settings/template/store/category/transient abilities and maintained template support.
