# MCP Abilities – Store Locator

Keep a branch's address, map coordinates and contact details together in WP Store Locator. An AI assistant can find the existing store record, update supported fields and check the saved result without creating another directory inside a page builder.

[![Release 0.1.23](https://img.shields.io/badge/release-0.1.23-blue.svg)](https://downloads.devenia.com/mcp-abilities-store-locator.zip)
[![License: GPL v2 or later](https://img.shields.io/badge/License-GPL%20v2%2B-blue.svg)](https://www.gnu.org/licenses/gpl-2.0.html)
[![WordPress 6.9+](https://img.shields.io/badge/WordPress-6.9%2B-blue.svg)](https://wordpress.org/)
[![PHP 8.0+](https://img.shields.io/badge/PHP-8.0%2B-purple.svg)](https://www.php.net/)

**Tested up to:** WordPress 7.1
**Stable tag:** 0.1.23
**License:** GPLv2 or later
**License URI:** https://www.gnu.org/licenses/gpl-2.0.html
**Tags:** mcp, ai, automation, abilities-api, store-locator

## What It Does

The add-on exposes 12 authenticated WordPress abilities for WP Store Locator 2.x. They read stores and categories, create or update store records, inspect and change supported settings, select an installed template and clear the locator's autoload cache.

It also registers the `dynamic_columns` template. WP Store Locator continues to own the shortcode, map, search, result data and category filtering. The template arranges the result list into three columns on larger screens, two on tablets and one on smaller screens.

Optional multilingual settings cover custom store URL bases for WPML language-directory URLs, the columns template's search field/button labels, and source-language links for native store menu items. The latter uses existing WPML or Polylang translation relationships; it does not create translations.

## The Real Workflow

A branch moves. Updating the written address alone can leave its marker at the old premises.

1. Ask the assistant to find the branch and show its ID, address, coordinates and contact details.
2. Confirm the record against the branch's current information.
3. Supply the new address and verified latitude/longitude together. The add-on does not geocode an address.
4. Update that record, then read it back.
5. Open the public locator, search for the branch, inspect the marker and follow the contact or store link.

A stored coordinate is evidence of what was saved, not proof of where the entrance is. Check the real destination before relying on the locator.

## Why This Feels Different

The assistant can carry out maintenance on the same records that the locator uses. A map result and a listing do not need separate hand-maintained copies.

Other useful tasks include:

- **Prepare a new branch:** search for an existing entry first, create a draft, add verified location/contact fields and assign existing categories before publication.
- **Review a network of locations:** page through store records to find missing contact details or entries that need confirmation. The assistant must interpret those records; there is no automatic accuracy audit.
- **Improve how visitors choose a location:** preview search radius/result-count settings, choose an installed template and check the public search on mobile.
- **Review a multilingual locator:** adjust the two supported search labels or configured store links, then open each affected language to confirm the result.

## Before vs After

| Task | Manual work | With the add-on |
|---|---|---|
| A branch moves | Find the record and edit several fields | Inspect the record, update the confirmed fields and read them back |
| A new branch opens | Re-enter details in multiple page layouts | Create one native draft for the locator to use |
| Results need columns | Maintain another list in page content | Select the registered `dynamic_columns` template |
| Old search results remain | Locate the relevant cache controls | Request native WP Store Locator autoload invalidation |

## Who It Is For

Retail groups, service networks and agencies maintaining sites that already use WP Store Locator. It suits work on branches, showrooms or collection points where the map and list should share one record.

## Requirements

- WordPress 6.9 or later with the Abilities API available.
- PHP 8.0 or later. Use a maintained PHP release supported by the site.
- An active WP Store Locator 2.x installation. Compatibility was checked with 2.3.23; this does not establish compatibility with WP Store Locator 3 preview releases.
- An authenticated ability connection, such as [MCP Expose Abilities](https://devenia.com/plugins/mcp-expose-abilities/).
- Native WP Store Locator permissions for the affected records or settings. Global multilingual mappings require `manage_options`.

Configure the locator's map service in WP Store Locator. Its [Google Maps setup documentation](https://wpstorelocator.co/document/create-google-api-keys/) explains the required keys and provider setup for version 2. These abilities do not create map credentials, enable billing or validate provider charges.

## Documentation

- [Plugin page](https://devenia.com/plugins/mcp-abilities-store-locator/)
- [WP Store Locator](https://wordpress.org/plugins/wp-store-locator/)
- [WP Store Locator documentation](https://wpstorelocator.co/documentation/)
- [Native template registration](https://wpstorelocator.co/document/wpsl_templates/)
- [MCP Expose Abilities](https://devenia.com/plugins/mcp-expose-abilities/)

## Start Here

1. Confirm that the existing WP Store Locator map and search work.
2. Install this add-on and connect an authenticated assistant.
3. Ask for `wpsl/get-status` and a small `wpsl/list-stores` result.
4. Give the assistant one concrete task, the target store ID and the information it may change.
5. Inspect the saved record and the visitor-facing result.

## Abilities (12)

| Ability | Purpose |
|---|---|
| `wpsl/get-status` | Availability, installed templates, published count, settings and multilingual configuration; map key values are omitted |
| `wpsl/list-stores` | Paginated readable stores with location/contact fields; totals may be null for limited users |
| `wpsl/get-store` | One readable store, its metadata and categories |
| `wpsl/list-categories` | Existing native store categories |
| `wpsl/create-store` | A native store, defaulting to draft, with supported metadata/categories |
| `wpsl/update-store` | Supported fields on one editable store |
| `wpsl/set-template` | Select an installed template, optionally with a dry run |
| `wpsl/update-settings` | Update supported native settings, reporting invalid or unsupported keys in `skipped` |
| `wpsl/update-permalink-base-translations` | Replace the configured WPML language-directory store-base map |
| `wpsl/update-label-translations` | Replace `search_label` and `search_btn_label` overrides for the columns template |
| `wpsl/update-navigation-source-language-links` | Replace the frontend-language to source-language map for native store menu links |
| `wpsl/clear-transients` | Invoke WP Store Locator's native autoload cache invalidation |

## Usage Examples

### Inspect a small set of stores

```json
{
  "ability_name": "wpsl/list-stores",
  "parameters": {"status": "publish", "per_page": 10, "page": 1, "order": "ASC", "orderby": "title"}
}
```

### Change a confirmed contact destination

Replace the example ID and URL with the verified store's values.

```json
{
  "ability_name": "wpsl/update-store",
  "parameters": {"id": 123, "meta": {"url": "https://example.com/branches/north/"}}
}
```

Writable metadata: `address`, `address2`, `city`, `state`, `zip`, `country`, `country_iso`, `lat`, `lng`, `phone`, `fax`, `email` and `url`. Coordinates must be numeric and within geographic ranges. Existing opening hours can be read, but are not writable through this add-on.

An explicit `categories` array replaces the store's categories. Use existing IDs or slugs; an empty array clears them. Unknown categories are rejected. Creation is not a duplicate check: search before creating another store.

### Preview native search choices

The brackets identify the default choice in WP Store Locator's dropdown format.

```json
{
  "ability_name": "wpsl/update-settings",
  "parameters": {
    "settings": {"search_radius": "10,25,[50],100", "max_results": "[25],50,100"},
    "dry_run": true
  }
}
```

Supported settings: `autoload`, `debug`, `hide_country`, `hide_distance`, `hide_hours`, `listing_below_no_scroll`, `permalinks`, `reset_map`, `show_contact_details`, `show_credits`, `store_url`, `height`, `autoload_limit`, `max_results`, `search_radius`, `zoom_level`, `auto_zoom_level`, `template_id`, `start_name`, `start_latlng`, `api_region` and `distance_unit`. The distance unit is `km` or `mi`. Credentials are managed in WP Store Locator, not through this settings ability.

### Preview the columns template

```json
{
  "ability_name": "wpsl/set-template",
  "parameters": {"template_id": "dynamic_columns", "listing_below_no_scroll": true, "dry_run": true}
}
```

### Set the columns template's French search labels

The supplied translation map is a complete replacement. Include any other language overrides that should remain.

```json
{
  "ability_name": "wpsl/update-label-translations",
  "parameters": {
    "translations": {"fr": {"search_label": "Ville ou adresse", "search_btn_label": "Rechercher"}},
    "dry_run": true
  }
}
```

Other locator labels remain under WP Store Locator's native translation system. URL-base and menu-language mappings also replace their complete stored maps; inspect their current values with `wpsl/get-status` first.

## Safety and Ownership

- Store read/write actions respect the native store post type and object permissions. Publishing requires the store publishing capability; category assignment respects the native taxonomy capability.
- Input validation runs before store writes. A storage failure can still leave a partially saved record; the response reports failure and returns the current store for inspection.
- Settings responses report whether map keys are configured without returning the keys.
- Store data stays in native posts, metadata and taxonomy terms. These tools do not delete stores, import CSV files, write opening hours or automatically translate/geocode content.
- Custom URL bases apply to matching WPML language-directory links. Plain, draft, query-language and different-domain links retain native handling.
- Menu link/title changes use WordPress's native menu filters. Existing translations remain owned by WPML or Polylang.
- Cache invalidation uses the native WP Store Locator method. The legacy `deleted` field indicates whether that method ran, not how many cache entries were removed.

## Installation


For update notifications in WordPress, install [Devenia MCP Updater](https://downloads.devenia.com/devenia-mcp-updater.zip). The updater is optional. You choose which plugins update automatically through WordPress.

Download the [plugin ZIP](https://downloads.devenia.com/mcp-abilities-store-locator.zip), upload it in **Plugins → Add New → Upload Plugin**, and activate it alongside WP Store Locator. Confirm discovery through the authenticated connection.

## Changelog

### 0.1.23

- Register native store rewrite rules for the translated base after WPML removes the language directory from the request path. The current language remains owned by WPML, and existing full-prefix routes remain supported.

### 0.1.22

OpenStreetMap direction links now use the current location-search coordinates in the maintained columns template. Store destinations remain unchanged, and no extra geocoding request is added.

### 0.1.21

Valid footer location submissions now start the native WP Store Locator 3 search on the maintained columns template. Native geocoding, radius, sorting and map provider remain authoritative.


### 0.1.20

Add one dismissible Plugins-screen reminder when Devenia MCP Updater is missing or inactive, with persistent install or activate links. Automatic updates remain your choice in WordPress.

### 0.1.19

- Enforced native store/object permissions and preserved literal content and metadata.
- Rejected invalid coordinates and unknown categories before saving; reported storage failures accurately.
- Preserved native search dropdown lists and corrected supported setting names.
- Removed map credentials from settings responses and loaded native cache invalidation for API requests.
- Corrected multilingual lookups, retained native menu/rewrite handling and limited custom labels to the two supported search labels.
- Escaped the maintained template's search input value.

### 0.1.17

- Removed built-in language-specific label overrides in favour of configured template labels.

## Contributing

Describe the affected ability or template, the WP Store Locator version and the expected visitor-facing result. Include a small reproducible example without credentials or real customer data.

## License

GPLv2 or later. See the [GNU licence](https://www.gnu.org/licenses/gpl-2.0.html).

## Author

[basicus](https://profiles.wordpress.org/basicus/)

## Links

- [Product page](https://devenia.com/plugins/mcp-abilities-store-locator/)
- [Download](https://downloads.devenia.com/mcp-abilities-store-locator.zip)
- [WP Store Locator](https://wordpress.org/plugins/wp-store-locator/)
