# MCP Abilities - Store Locator

Store Locator maintenance abilities for MCP.

[![GitHub release](https://img.shields.io/github/v/release/bjornfix/mcp-abilities-store-locator)](https://github.com/bjornfix/mcp-abilities-store-locator/releases)
[![License: GPL v2](https://img.shields.io/badge/License-GPL%20v2-blue.svg)](https://www.gnu.org/licenses/gpl-2.0)
[![WordPress](https://img.shields.io/badge/WordPress-6.9%2B-blue.svg)](https://wordpress.org)
[![PHP](https://img.shields.io/badge/PHP-8.0%2B-purple.svg)](https://php.net)

**Tested up to:** 7.0
**Stable tag:** 0.1.3
**License:** GPLv2 or later
**License URI:** https://www.gnu.org/licenses/gpl-2.0.html

**Tags:** mcp, ai, automation, abilities-api, store-locator

## What It Does

This plugin is part of the Devenia MCP abilities ecosystem. It gives an MCP-capable agent a focused, authenticated way to work with Store Locator data inside WordPress through MCP.

It adds abilities for Store Locator settings, templates, store records, store categories, and transient cleanup. It also registers a maintained `g1_columns` locator template that keeps maps, search, AJAX results, labels, and store data under the Store Locator plugin instead of duplicating dealer entries into Elementor or static page content.

**Example:** "Put the dealer listings into columns." - The agent can inspect Store Locator settings, confirm available templates, activate the maintained template, clear Store Locator transients, and verify the rendered dealer page.

## The Real Workflow

In practice, the human should not have to memorize every ability name.

The normal pattern is:

1. install the base MCP stack
2. install only the add-ons the site actually needs
3. let the agent discover the available abilities
4. give the agent a clear task with boundaries
5. verify the result in WordPress

The human's job is mostly to describe the goal.
The agent's job is to figure out the mechanics.

## Why This Feels Different

Most WordPress automation still leaves the repetitive part to the human.

This plugin is different because the agent can act inside the site through a narrow, authenticated ability surface:

- inspect current locator settings before changing anything
- list and read real locator store records
- update supported store metadata without touching page-builder content
- switch to a maintained Store Locator template instead of creating manual cards
- clear locator cache after a real data or template change

That changes the experience from:

- `Here is what you should do in wp-admin`

to:

- `Tell the agent what needs doing, and let it carry out the work`

## Before vs After

### Before

- open wp-admin
- find the Store Locator settings
- inspect store records one by one
- copy dealer details into page-builder cards when layout demands change
- remember to clear locator cache

### After

- tell the agent what Store Locator outcome you need
- let it inspect the current locator state
- let it run the targeted ability
- verify the rendered page and move on

## Who It Is For

This is a good fit for:

- agencies managing WordPress sites with Store Locator dealer/location data
- operators who want agents to maintain locator settings and records safely
- teams already using MCP Expose Abilities
- sites where store/dealer/search output should stay dynamic and plugin-owned

It is especially useful when page-builder work would otherwise duplicate data that should remain canonical in Store Locator.

## Documentation

Start with the main plugin page and base stack documentation:

- [MCP Expose Abilities](https://devenia.com/plugins/mcp-expose-abilities/)
- [Plugin Page](https://devenia.com/plugins/mcp-expose-abilities/#add-ons)
- [Getting Started](https://github.com/bjornfix/mcp-expose-abilities/wiki/Getting-Started)
- [Install Order and Dependencies](https://github.com/bjornfix/mcp-expose-abilities/wiki/Install-Order-and-Dependencies)

If you are using an AI agent, the simplest instruction is often just:

- `Read https://github.com/bjornfix/mcp-expose-abilities and figure out the stack before making changes.`

## Start Here

If you are new to the stack, use this order:

1. Install **Abilities API**.
2. Install **MCP Adapter**.
3. Install **MCP Expose Abilities**.
4. Install **MCP Abilities - Store Locator**.
5. Confirm the new abilities appear in discovery.
6. Give the agent a clear Store Locator task.

If you skip base-stack verification and start with add-ons immediately, troubleshooting gets harder than it needs to be.

## Abilities (9)

| Ability | Description |
|---------|-------------|
| `wpsl/get-status` | Read Store Locator availability, settings, templates, and published store count |
| `wpsl/update-settings` | Update supported Store Locator settings with Store Locator-aware validation |
| `wpsl/set-template` | Set the active Store Locator search template by installed template ID |
| `wpsl/list-stores` | List real Store Locator store posts with address/contact/location metadata |
| `wpsl/get-store` | Read one real Store Locator store post and its locator metadata |
| `wpsl/create-store` | Create a Store Locator store post with supported locator metadata |
| `wpsl/update-store` | Update a Store Locator store post, supported metadata, and categories |
| `wpsl/list-categories` | List `wpsl_store_category` terms |
| `wpsl/clear-transients` | Clear Store Locator autoload transients |

## Usage Examples

### Inspect Store Locator Status

```json
{
  "ability_name": "wpsl/get-status",
  "parameters": {}
}
```

### Activate The Maintained Column Template

```json
{
  "ability_name": "wpsl/set-template",
  "parameters": {
    "template_id": "g1_columns",
    "listing_below_no_scroll": true
  }
}
```

### List Store Records

```json
{
  "ability_name": "wpsl/list-stores",
  "parameters": {
    "status": "publish",
    "per_page": 50,
    "orderby": "title",
    "order": "ASC"
  }
}
```

### Update Store Metadata

```json
{
  "ability_name": "wpsl/update-store",
  "parameters": {
    "id": 123,
    "meta": {
      "address": "Exampleveien 1",
      "city": "Oslo",
      "zip": "0150",
      "country": "Norway",
      "lat": "59.9139",
      "lng": "10.7522",
      "phone": "+47 00 00 00 00",
      "email": "post@example.com"
    }
  }
}
```

## Notes

- The plugin intentionally keeps store content in Store Locator posts and metadata.
- It does not create Elementor cards, static dealer listings, or duplicate store records into page content.
- The `g1_columns` template is a maintained Store Locator template registered through the locator template filter.
- The frontend template preserves the Store Locator shortcode, map, search form, AJAX result list, and cache behavior.

## Changelog

### 0.1.3

- Suppressed the final divider line after the last G1 dealer entry.

### 0.1.2

- Adjusted the G1 dealer columns template so dealer entries use only a bottom divider instead of boxed card borders.

### 0.1.1

- Improved the G1 dealer columns template so card padding is not overridden by Store Locator base styles.
- Read the search label and button text directly from Store Locator settings in the maintained template.
- Moved G1 Store Locator Norwegian label and Elementor store-post compatibility handling out of the temporary mu-plugin and into this maintained add-on.

### 0.1.0

- Added Store Locator settings, template, store, category, and transient abilities.
- Added the maintained `g1_columns` Store Locator template for dynamic dealer columns.
