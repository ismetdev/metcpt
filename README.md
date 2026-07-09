# MetCPT — Corporate Content Hub for WordPress

![Version](https://img.shields.io/badge/version-1.2.1-0056b3.svg)
![WordPress](https://img.shields.io/badge/WordPress-6.0%2B-21759b.svg)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-777bb4.svg)
![License](https://img.shields.io/badge/license-Proprietary-lightgrey.svg)

A WordPress plugin built for **IIUM Holdings Sdn Bhd** to manage corporate **events**, procurement **tenders**, **career** vacancies, and **news** content across multiple subsidiary companies — with structured admin entry, styled public listings, and full single-page templates.

---

## Table of Contents

- [Overview](#overview)
- [Features](#features)
- [Requirements](#requirements)
- [Installation](#installation)
- [Updates](#updates)
- [Configuration](#configuration)
- [Shortcodes](#shortcodes)
- [Recommended Page Setup](#recommended-page-setup)
- [Archive Display](#archive-display)
- [Server Cron Setup](#server-cron-setup-recommended)
- [File Structure](#file-structure)
- [Custom Post Types](#custom-post-types)
- [Data & Storage](#data--storage)
- [Security](#security)
- [Performance](#performance)
- [Changelog](#changelog)
- [Support](#support)
- [License](#license)

---

## Overview

MetCPT registers four custom post types (Events, Tenders, Careers, Companies) and exposes a set of shortcodes that render structured, styled listings on ordinary WordPress pages. It is designed to sit alongside a page builder such as Elementor: listings are placed on pages via shortcodes, while the plugin owns the single-post templates for each content type.

## Features

### Events
- Custom post type with an 8-section meta box (schedule, VIPs, itinerary, attendance, FAQs, guidelines, call-to-action, contact)
- Automatic status detection (Upcoming / Today / Past)
- VIP attendee list with configurable role badges
- Itinerary builder (time / activity / person-in-charge)
- Single event page with Google Calendar integration and WhatsApp sharing
- `[events_list]` shortcode with month dividers and filtering

### Tenders
- Custom post type for procurement opportunities
- Two display templates — **A** (searchable table) and **B** (editorial layout), switchable globally or per-shortcode
- Automatic status calculation (Open / Closing Soon / Closed) from the closing date
- Daily email notifications for tenders nearing their closing date, with de-duplication
- `[tenders_list]` and `[tenders_preview]` shortcodes

### Careers
- Custom post type for job vacancies
- Relational company system via the `metcpt_company` CPT
- Controlled Department dropdown (managed in Settings) and fixed Job Type options
- Application tracking with closing dates; "Apply" hidden automatically once closed
- `[careers_list]` and `[careers_preview]` shortcodes

### News Grid
- Editorial news layout for standard WordPress posts
- Uniform responsive grid (`repeat(3, 1fr)`, minimum 6 cards)
- Optional category filtering
- `[news_grid]` and `[category_posts]` shortcodes

### Admin
- **Settings page** — tabbed configuration for General, Events, Tenders, Careers, Posts, plus an Error Log and How-To reference
- **Dashboard widget** — content-health overview (counts, closing-soon alerts, missing-field detection), transient-cached for 5 minutes
- **Error Log** — captures PHP errors, warnings, notices, uncaught exceptions, and fatals originating from MetCPT's own files, stored in a dedicated table with a filterable UI, detail modal, per-entry resolve, and 30-day auto-purge of resolved entries
- **Dummy data tools** — one-click seed/clear of sample content for local testing (behind a toggle; idempotent seeding)

## Requirements

| | Minimum |
|---|---|
| WordPress | 6.0 |
| PHP | 7.4 |
| MySQL | 5.7 |

## Installation

**Via WordPress admin (recommended)**
1. Download `metcpt.zip` from the [latest release](https://github.com/ismetdev/metcpt/releases).
2. Go to **Plugins → Add New → Upload Plugin**, choose the zip, and click **Install Now**.
3. Click **Activate**.

**Manual**
1. Extract `metcpt.zip` into `/wp-content/plugins/metcpt/`.
2. Activate **MetCPT** from the **Plugins** screen.

After activating, go to **Settings → Permalinks** and click **Save Changes** once to flush rewrite rules.

## Updates

MetCPT self-updates from its GitHub releases via the bundled [Plugin Update Checker](https://github.com/YahnisElsts/plugin-update-checker). When a new version is released, WordPress shows an update on the **Plugins** / **Dashboard → Updates** screen; an administrator clicks **Update** to install it. Updates are always deliberate and admin-initiated — there is no silent or push-based deployment.

## Configuration

Open **MetCPT Settings** and configure, at minimum:

- **Tenders → Page URL** and **Careers → Page URL** — used by the "Back" buttons on single pages and the "View all" links in previews
- **Events → Archive URL** — used by the "Back to Events" button
- **Tenders → Notification Recipient** and **Closing Soon Threshold (days)**
- **Tenders → Categories** and **Careers → Departments** — the dropdown options shown when editing content
- **Posts → News Grid** — header label, headline, view-all link, and default category

Then create your first **Company**, and start adding Events, Tenders, and Careers.

## Shortcodes

### Events
```
[events_list]
[events_list filter="upcoming"]
[events_list filter="past" order="DESC"]
[events_list posts_per_page="5" show_excerpt="no"]
```
| Attribute | Values | Default |
|---|---|---|
| `filter` | all / upcoming / past | all |
| `category` | category slug | — |
| `posts_per_page` | integer (`-1` = all) | -1 |
| `order` | ASC / DESC | ASC |
| `show_excerpt` | yes / no | yes |
| `show_date` | yes / no | yes |

### Tenders
```
[tenders_list]
[tenders_list template="a"]
[tenders_preview]
[tenders_preview template="b" posts_per_page="6"]
```
`template="a"` (table) or `template="b"` (editorial) overrides the global **Active Template** setting for that shortcode. Specify it explicitly on any page where the layout matters.

| Attribute | Values | Default |
|---|---|---|
| `template` | a / b | global setting |
| `posts_per_page` | integer | 5 (preview) |
| `view_all_url` | URL | Tenders Page URL |

### Careers
```
[careers_list]
[careers_list filter="open"]
[careers_list company="iium-holdings" department="ICT" type="Full Time"]
[careers_preview posts_per_page="3"]
```
| Attribute | Values | Default |
|---|---|---|
| `filter` | all / open / closed | all |
| `company` | company post slug | — |
| `department` | exact department name | — |
| `type` | Full Time / Part Time / Contract / Internship | — |
| `posts_per_page` | integer (`-1` = all) | -1 |
| `order` | ASC / DESC | ASC |
| `view_all_url` | URL | Careers Page URL |

### News
```
[news_grid]
[news_grid category="csr"]
[news_grid label="Our Stories" headline="Latest from" headline_italic="the group."]
[category_posts category_slug="news" posts_per_page="5"]
```
`[news_grid]` attributes fall back to the **Posts** settings tab when omitted (`label`, `headline`, `headline_italic`, `view_all_text`, `view_all_url`, `category`, `posts_per_page`, `show_excerpt`, `order`, `orderby`). `[category_posts]` requires `category_slug` and accepts `posts_per_page`, `order`, `orderby`, `show_excerpt`, and `show_date`.

## Recommended Page Setup

| Page | Shortcode |
|---|---|
| Homepage | `[news_grid]`, `[tenders_preview]`, `[careers_preview]` |
| Events | `[events_list filter="upcoming"]` |
| Tenders | `[tenders_list template="a"]` |
| Careers | `[careers_list filter="open"]` |
| Company-specific Careers | `[careers_list company="company-slug"]` |
| CSR / News | `[category_posts category_slug="csr"]` |

## Archive Display

Listings are served by **WordPress pages containing shortcodes**, not by CPT archives. Create a page for each module (slugs `events`, `tenders`, `careers`) holding the relevant list shortcode. These pages use the theme's page template — keeping header, footer, and navigation — and avoid conflicts with page builders that intercept CPT template loading. The archive-URL settings point the single-page "Back" buttons at these pages.

Each module also ships a `template-archive.php` fallback that styles a raw CPT archive URL (e.g. `?post_type=metcpt_event`) if one is reached directly. These are an active fallback, not dead code.

## Server Cron Setup (Recommended)

WordPress `wp_cron` runs on page traffic, which can delay tender notifications. For reliable timing:

1. Disable the pseudo-cron in `wp-config.php`:
   ```php
   define( 'DISABLE_WP_CRON', true );
   ```
2. Add a real daily cron job (e.g. 8:00 AM):
   ```bash
   wget -q -O /dev/null "https://yoursite.com/wp-cron.php?doing_wp_cron"
   ```

## File Structure

```
metcpt/
├── metcpt.php                          # Plugin entry point + auto-updater
├── uninstall.php                       # Cleanup on deletion (preserve-by-default)
├── readme.txt                          # WordPress-format readme
├── libs/plugin-update-checker/         # GitHub release update library (YahnisElsts)
├── includes/
│   ├── core/
│   │   ├── class-metcpt.php            # Main loader class
│   │   ├── post-types.php              # CPT registration
│   │   └── migrate-from-haraka.php     # One-time Haraka → MetCPT data migration
│   ├── admin/
│   │   ├── settings-page.php           # Settings UI + registration
│   │   ├── settings-fields.php         # Field renderers
│   │   ├── cron.php                     # Daily tender-closing email task
│   │   ├── dashboard-widget.php        # Admin content-health widget
│   │   ├── error-log.php               # Error capture, DB, AJAX, purge cron
│   │   ├── error-log-page.php          # Error Log tab UI
│   │   ├── docs-page.php               # How-To tab UI
│   │   └── dummy-data.php              # Seed / clear sample content
│   ├── events/    (meta-boxes, shortcode-list, template-single, template-archive)
│   ├── tenders/   (meta-boxes, shortcode-list, shortcode-preview, shortcode-template-b, template-single, template-archive)
│   ├── careers/   (meta-boxes-company, meta-boxes-career, shortcode-list, shortcode-preview, template-single, template-archive)
│   └── posts/     (shortcode-general, shortcode-news-grid)
└── assets/                             # Per-module CSS (tokens, general, events, tenders, careers, posts, admin, settings, docs)
```

## Custom Post Types

| CPT | Slug | Purpose |
|---|---|---|
| Events | `metcpt_event` | Corporate events |
| Tenders | `metcpt_tender` | Procurement opportunities |
| Careers | `metcpt_career` | Job vacancies |
| Companies | `metcpt_company` | Subsidiary company profiles |

## Data & Storage

MetCPT stores content in native WordPress tables (`wp_posts`, `wp_postmeta`, `wp_options`) and creates **one** custom table, `wp_metcpt_error_log`, for the error-logging feature. On deletion the plugin preserves all data by default; a full-cleanup block in `uninstall.php` can be enabled to remove posts, meta, options, and the log table.

## Security

- Nonce verification on every meta-box save and AJAX action
- Capability checks (`edit_post` for content, `manage_options` for admin actions)
- Input sanitized on save (`sanitize_text_field`, `sanitize_textarea_field`, `esc_url_raw`, `absint`)
- Output escaped (`esc_html`, `esc_attr`, `esc_url`, `esc_textarea`, `wp_kses_post`)
- All database access parameterized via `$wpdb->prepare()`

## Performance

- Dashboard widget cached via transient (5-minute expiry), invalidated on post save/delete
- Asset cache-busting keyed to file modification time
- Lean shortcode queries with optional category narrowing

## Changelog

### 1.2.1 (2026-07-10)
- Rewrite rules now flush automatically once after each plugin update (version-gated, on `admin_init`), so the manual **Settings → Permalinks → Save** step is no longer needed following an update. Fresh installs already flushed on activation.

### 1.2.0 (2026-07-10)
- Removed six settings that were registered and rendered but never read: accent colour, organisation name, events default order, events show-excerpt, default submission address, and default tender fee.
- Removed the redundant News-Grid live preview from the Posts settings tab (the same preview remains in the How-To tab).
- Fixed an undefined-array-key warning in the manual cron trigger's nonce check.
- Made the dummy-data seeder idempotent — it now refuses to seed on top of existing dummy content instead of stacking duplicates.
- Completed and corrected the `uninstall.php` option-cleanup list (fixed two mismatched option names and added all currently registered options).

### 1.1.0 (2026-07-10)
- Rebranded from "Haraka" to "MetCPT" with a one-time automatic data migration for post types, options, post meta, the error-log table, and scheduled cron hooks.
- Switched updates to public-repo GitHub releases (no authentication token required).
- Redesigned the settings page and refreshed the newsroom and tenders-preview layouts onto shared design tokens.

### 1.0.0 (2026-06-14)
- Initial production release: Events, Tenders, and Careers modules; `[news_grid]`; dashboard widget; closing-tender email notifications; styled archive fallbacks; safe uninstall handler.

## Support

- **Internal:** IIUM Holdings Web Development Team
- **Issues:** [github.com/ismetdev/metcpt/issues](https://github.com/ismetdev/metcpt/issues)

## License

Proprietary — for internal use by IIUM Holdings Sdn Bhd and its subsidiaries.

---

Developed for IIUM Holdings Sdn Bhd · v1.2.1 · July 2026
