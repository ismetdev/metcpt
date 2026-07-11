=== MetCPT ===
Contributors: Ismet Fitri
Tags: corporate, events, careers, tenders, listings
Requires at least: 6.0
Tested up to: 6.5
Requires PHP: 7.4
Stable tag: 1.3.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Corporate Content Hub for WordPress. Manage Events, Tenders, and Careers with structured listings and elegant single-page templates.

== Description ==

MetCPT is a corporate content management plugin designed for enterprise and institutional websites.

Features include:

* Events management
* Tender listings
* Career opportunities
* Structured custom post types
* Beautiful single-page templates

Perfect for corporate websites that need organized public information publishing.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/metcpt` directory
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Configure content from the WordPress admin dashboard

== Frequently Asked Questions ==

= Does this plugin work with any theme? =

Yes, but it works best with themes that support modern WordPress templating.

= Can I customize the templates? =

Yes. Developers can override or customize templates as needed.

== Changelog ==

= 1.3.1 =

* Fixed single Event, Tender, and Career pages rendering a duplicate page structure (two document shells, a repeated page title, and the head section loading twice) — pages now output clean, valid HTML with a single title, improving SEO and load weight. No visual change.
* Fixed the deactivation cleanup so scheduled background tasks are properly unscheduled when the plugin is deactivated (previously the hook was registered against the wrong path and never ran)
* Added a flood guard to the error logger so a repeated error can no longer write thousands of rows in a single request
* Capped the error-log table size (keeps the most recent entries, pruned daily) so it can no longer grow without bound
* Removed a redundant per-request database check on every page load

= 1.3.0 =

* Redesigned the admin Settings page into one unified, consistent house design (header, tabs and body now read as a single card) with a blue accent throughout
* Fixed the Error Log and How-To pages so they stay within the page width and no longer run off to the right on smaller screens
* Renamed all remaining internal "hrk" CSS classes and design tokens (leftover from the old plugin name) to the "mcpt" prefix; no change to functionality or content

= 1.2.2 =

* Rewrote the plugin description to a clearer, standards-based summary of what the plugin does

= 1.2.1 =

* Rewrite rules now flush automatically after a plugin update, so the manual Settings -> Permalinks -> Save step is no longer needed following an update

= 1.2.0 =

* Removed six settings that were registered and rendered but never read (accent colour, organisation name, events default order, events show-excerpt, default submission address, default tender fee)
* Removed the redundant News-Grid preview from the Posts settings tab
* Fixed an undefined-array-key warning in the manual cron trigger nonce check
* Made the dummy-data seeder idempotent to prevent duplicate seeding
* Completed and corrected the uninstall option-cleanup list

= 1.1.0 =

* Rebranded from "Haraka" to "MetCPT" with a one-time automatic data migration
* Switched updates to public-repo GitHub releases (no token required)
* Redesigned settings page and refreshed newsroom and tenders-preview layouts

= 1.0.0 =

* Initial production release
* Events management with archive and single page templates
* Tenders management with Template A and Template B support
* Careers management with company relational system
* News grid shortcode for WordPress posts
* Dashboard widget with content health monitoring
* Email notifications for closing tenders
* Polished archive pages for Events, Tenders, and Careers

== Upgrade Notice ==

= 1.3.1 =

Stability and SEO fix release. Cleans up duplicate HTML on single Event/Tender/Career pages (fixes a doubled page title), hardens the error logger against runaway growth, and fixes deactivation cleanup. No visual changes; your content and configuration are unaffected.

= 1.3.0 =

Visual refresh of the admin Settings page (unified design, blue accent) and a fix so the Error Log and How-To pages display neatly on all screen sizes. No functional changes; your content and configuration are unaffected.

= 1.2.2 =

Metadata-only update: clarifies the plugin description. No functional changes; your content and configuration are unaffected.

= 1.2.1 =

Adds automatic rewrite-rule flushing after updates. Apply this update once via Settings -> Permalinks -> Save (the last manual flush you will need); every update after this one flushes automatically.

= 1.2.0 =

Housekeeping release: removes non-functional settings and hardens the cron and dummy-data tools. No action required; your content and configuration are unaffected.

= 1.0.0 =

Initial production release of MetCPT.
