=== MCP Abilities - Store Locator ===
Contributors: basicus
Tags: mcp, ai, automation, abilities-api, store-locator
Requires at least: 6.9
Tested up to: 7.1
Requires PHP: 8.0
Stable tag: 0.1.20
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.txt

Maintain native WP Store Locator records, search settings and dynamic location listings through authenticated WordPress abilities.

== Description ==

Adds authenticated WordPress Abilities API tools for WP Store Locator maintenance and registers maintained store-locator template support for dynamic column-based location listings.

The abilities cover WPSL status, settings, templates, stores, categories, and transient cleanup. The plugin does not duplicate store content into Elementor or static page content. Store data remains owned by WP Store Locator.

Requires WP Store Locator 2.x, WordPress 6.9 or later with the Abilities API, and PHP 8.0 or later. An authenticated ability connection such as MCP Expose Abilities makes the tools available to an assistant. The locator's map service remains configured in WP Store Locator.

The 12 abilities inspect status, stores and categories; create or update store records; choose an installed template; update supported settings; configure translated URL bases, the two columns-template search labels and source-language menu links; and clear the native autoload cache. This add-on does not geocode addresses, import CSV files, delete stores or write opening hours.

Documentation: https://devenia.com/plugins/mcp-abilities-store-locator/

== Update notifications ==

For update notifications in WordPress, install [Devenia MCP Updater](https://downloads.devenia.com/devenia-mcp-updater.zip). The updater is optional. You choose which plugins update automatically through WordPress.

== Changelog ==

= 0.1.20 =
* Add one dismissible Plugins-screen reminder when Devenia MCP Updater is missing or inactive, with persistent install or activate links. Automatic updates remain your choice in WordPress.

= 0.1.19 =
* Enforced native store and per-object permissions, preserved literal content and metadata, and rejected invalid coordinates and unknown categories before saving.
* Preserved native dropdown lists and corrected supported setting names; map credentials are no longer returned by status or settings responses.
* Verified saved settings and exposed unavailable cache cleanup accurately, including native cache loading for API requests.
* Corrected WPML and Polylang lookups, retained native menu rendering and rewrite routing, and limited custom label updates to the two labels used by the columns template.
* Escaped the search input value in the maintained template.


= 0.1.17 =
* Removed built-in language-specific label overrides; frontend labels remain configurable through the public translation ability.

= 0.1.16 =
* Cached source-language native store menu render data per request to avoid repeated WPML and menu lookups during frontend rendering.

= 0.1.15 =
* Resolved source-language store menu permalinks under WPML by switching language while reading source menu data.

= 0.1.14 =
* Added URL-prefix language detection and final native walker output handling for multilingual store menus.

= 0.1.13 =
* Applied source-language native menu rendering at the final menu title and link output stage.

= 0.1.12 =
* Added configurable native menu source-language rendering for WPSL store menu items.

= 0.1.11 =
* Added configurable language-specific frontend label translations for the maintained Store Locator template.

= 0.1.10 =
* Renamed the maintained columns template, frontend classes, and internal identifiers to generic public-plugin names.

= 0.1.9 =
* Replaced site-specific store URL handling with configurable translated WPSL store permalink bases.
* Added an ability for updating translated store permalink base mappings.

= 0.1.8 =
* Added a request router for configured translated store URL bases when WPML/WPSL rewrite matching misses the custom base.

= 0.1.7 =
* Fixed translated store permalink generation and routing for custom translated bases.

= 0.1.6 =
* Added translated permalink base support for WP Store Locator store translations.

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
