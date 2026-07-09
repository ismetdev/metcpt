=== MetCPT ===
Contributors: Ismet Fitri
Tags: corporate, events, careers, tenders, listings
Requires at least: 6.0
Tested up to: 6.5
Requires PHP: 7.4
Stable tag: 1.2.0
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
* Built for IIUM Holdings

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

= 1.2.0 =

Housekeeping release: removes non-functional settings and hardens the cron and dummy-data tools. No action required; your content and configuration are unaffected.

= 1.0.0 =

Initial production release of MetCPT.
